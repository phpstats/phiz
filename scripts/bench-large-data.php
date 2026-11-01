<?php

declare(strict_types=1);

/*
 * Times the PHP binning geoms on a million-row phibble table and writes site/benchmarks/large-data-binning.json. Each
 * case runs in its own process under peakmem; the process builds the table, then times seven runs of building the
 * plot and encoding its specification. Needs phibble (a development dependency).
 *
 *     php scripts/bench-large-data.php
 */

use Composer\InstalledVersions;
use Phibble\DataFrame;
use Phiz\Plot;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/evidence/bench.php';

const REPEATS = 7;

/** @return array<string, Closure(DataFrame): Plot> */
function large_data_cases(): array
{
    return [
        'hex(bins: 40)' => fn ($table) => Plot::of($table)->aes(x: 'x', y: 'y')->hex(bins: 40),
        'bin2d(bins: 50)' => fn ($table) => Plot::of($table)->aes(x: 'x', y: 'y')->bin2d(bins: 50),
        'histogram(bins: 60)' => fn ($table) => Plot::of($table)->aes(x: 'x')->histogram(bins: 60),
    ];
}

/** One million correlated normal pairs from a seeded generator, so every process bins the same table. */
function large_data_table(): DataFrame
{
    mt_srand(1);
    $x = [];
    $y = [];
    for ($i = 0; $i < 1_000_000; $i++) {
        $radius = sqrt(-2 * log(mt_rand() / mt_getrandmax() + 1e-12));
        $angle = 2 * M_PI * mt_rand() / mt_getrandmax();
        $x[] = $radius * cos($angle);
        $y[] = 0.6 * $x[$i] + 0.8 * $radius * sin($angle);
    }

    return DataFrame::fromColumns(['x' => $x, 'y' => $y]);
}

// A case's process: time it and print its runs as JSON.
if (isset($argv[1]) && str_starts_with($argv[1], '--case=')) {
    $build = large_data_cases()[substr($argv[1], 7)];
    $table = large_data_table();
    $json = '';
    $runs = bench_time(function () use ($build, $table, &$json) {
        $json = $build($table)->toJson();
    }, REPEATS, 0, 's');
    echo json_encode(['runs' => $runs, 'bytes' => strlen($json), 'php_peak_mib' => round(memory_get_peak_usage(true) / 1048576, 1)]);
    exit(0);
}

$rows = [];
foreach (array_keys(large_data_cases()) as $case) {
    $result = bench_run(__FILE__, $case);
    $rows[] = [
        'case' => $case,
        'implementation' => 'phiz',
        'value' => bench_round(bench_median($result['runs'])),
        'reps' => array_map(bench_round(...), $result['runs']),
        'peak_memory_mib' => $result['peak_memory_mib'],
        'peak_rss_mib' => $result['peak_rss_mib'],
        'php_peak_mib' => $result['php_peak_mib'],
        'note' => sprintf('Specification: %d KB.', round($result['bytes'] / 1024)),
    ];
}

$phiz = trim((string) shell_exec('git -C '.escapeshellarg(dirname(__DIR__)).' describe --tags --always'));
bench_write('large-data-binning', [
    'title' => 'Large data: binning a million-row table in PHP',
    'label' => 'Large data',
    'description' => 'Bin one million (x, y) points from a phibble table into hexagons, rectangles, or a histogram, and encode the specification as JSON. Only the bins are embedded.',
    'workload' => '1,000,000 rows, 2 numeric columns',
    'dataset' => ['rows' => 1_000_000, 'columns' => 2, 'detail' => 'correlated standard normal pairs from mt_rand seeded with 1, built in the measured process'],
    'implementations' => ['phiz' => "phiz {$phiz}, plain PHP, reading a phibble ".InstalledVersions::getPrettyVersion('phpstats/phibble').' table'],
    'measure' => 'Elapsed time',
    'unit' => 's',
    'statistic' => 'median',
    'method' => 'From building the Plot to holding the JSON specification: compiling, reading the table column by column, binning, and encoding. Building the table and starting PHP are excluded.',
    'time_spent_in' => 'the PHP process (building the specification only; no browser rendering or vl-convert)',
    'memory_measure' => 'peak anonymous memory',
    'memory_method' => 'RssAnon of the whole PHP process, including the generated table, sampled every millisecond by peakmem (phpolars bench/csv-pipeline/peakmem.c). php_peak_mib is memory_get_peak_usage(true).',
    'environment' => bench_environment(REPEATS, 0) + ['versions' => ['phibble' => InstalledVersions::getPrettyVersion('phpstats/phibble')]],
    'rows' => $rows,
    'reproduce' => ['composer install', 'php scripts/bench-large-data.php'],
    'source' => 'scripts/bench-large-data.php',
    'group' => 'phiz in PHP',
]);

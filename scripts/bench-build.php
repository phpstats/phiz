<?php

declare(strict_types=1);

/*
 * Times building and encoding ordinary small plots, the per-request cost of a chart in a web page, and writes
 * site/benchmarks/small-plot-build.json. Each case runs in its own process under peakmem; each of seven runs builds
 * the plot and encodes its specification 100 times, and the time is per plot.
 *
 *     php scripts/bench-build.php
 */

use Phiz\Datasets;
use Phiz\Plot;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/evidence/bench.php';

const REPEATS = 7;
const ITERATIONS = 100;

/** @return array<string, Closure(): Plot> */
function build_cases(): array
{
    return [
        'scatter, 234 rows' => fn () => Plot::of(Datasets::mpg())->aes(x: 'displ', y: 'hwy', color: 'class')->point(),
        'scatter with lm band, 234 rows' => fn () => Plot::of(Datasets::mpg())->aes(x: 'displ', y: 'hwy')->point()->smooth(method: 'lm'),
        'scatter with loess band, 234 rows' => fn () => Plot::of(Datasets::mpg())->aes(x: 'displ', y: 'hwy')->point()->smooth(),
        'histogram, 272 rows' => fn () => Plot::of(Datasets::faithful())->aes(x: 'waiting')->histogram(bins: 30),
        'time series, 574 rows' => fn () => Plot::of(Datasets::economics())->aes(x: 'date', y: 'unemploy')->line(),
        'facets, 7 panels, 234 rows' => fn () => Plot::of(Datasets::mpg())->aes(x: 'displ', y: 'hwy')->point()->facetWrap('class', ncol: 4),
    ];
}

// A case's process: time it and print its runs as JSON.
if (isset($argv[1]) && str_starts_with($argv[1], '--case=')) {
    $build = build_cases()[substr($argv[1], 7)];
    $json = '';
    $runs = bench_time(function () use ($build, &$json) {
        $json = $build()->toJson();
    }, REPEATS, 1, 'ms', ITERATIONS);
    echo json_encode(['runs' => $runs, 'bytes' => strlen($json), 'php_peak_mib' => round(memory_get_peak_usage(true) / 1048576, 1)]);
    exit(0);
}

$rows = [];
foreach (array_keys(build_cases()) as $case) {
    $result = bench_run(__FILE__, $case);
    $rows[] = [
        'case' => $case,
        'implementation' => 'phiz',
        'value' => bench_round(bench_median($result['runs'])),
        'reps' => array_map(bench_round(...), $result['runs']),
        'peak_memory_mib' => $result['peak_memory_mib'],
        'peak_rss_mib' => $result['peak_rss_mib'],
        'php_peak_mib' => $result['php_peak_mib'],
        'note' => sprintf('%.1f KB specification.', $result['bytes'] / 1024),
    ];
}

$phiz = trim((string) shell_exec('git -C '.escapeshellarg(dirname(__DIR__)).' describe --tags --always'));
bench_write('small-plot-build', [
    'title' => 'Small plots',
    'label' => 'Small plots',
    'description' => 'Building of an ordinary plot from a bundled dataset and encoding of its specification as JSON, as for each chart a page serves.',
    'workload' => 'Small plots, 234 to 574 inline rows',
    'implementations' => ['phiz' => "phiz {$phiz}, plain PHP"],
    'measure' => 'Elapsed time per plot',
    'unit' => 'ms',
    'statistic' => 'median',
    'method' => 'Mean time per plot over 100 builds and encodings per run. One warmup run. Dataset loading and PHP startup excluded.',
    'time_spent_in' => 'The PHP process; specification building only, without browser rendering or vl-convert.',
    'memory_measure' => 'peak anonymous memory',
    'memory_method' => 'Peak anonymous memory (RssAnon) of the whole PHP process, sampled every millisecond by peakmem (phpolars bench/csv-pipeline/peakmem.c). Also reported: peak resident set size, and PHP allocator peak (memory_get_peak_usage(true)).',
    'environment' => bench_environment(REPEATS, 1),
    'rows' => $rows,
    'reproduce' => ['composer install', 'php scripts/bench-build.php'],
    'source' => 'scripts/bench-build.php',
    'group' => 'Specification building',
]);

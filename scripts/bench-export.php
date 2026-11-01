<?php

declare(strict_types=1);

/*
 * Times static export through vl-convert and writes site/benchmarks/vl-convert-export.json. Each case, a plot and a
 * format, runs in its own PHP process under peakmem: one warmup export, then seven timed exports. vl-convert runs as a
 * child process for each export; its peak resident set size is the largest of its runs, from getrusage(RUSAGE_CHILDREN)
 * in the PHP process. Needs vl-convert (PHIZ_VL_CONVERT, VL_CONVERT, or PATH).
 *
 *     php scripts/bench-export.php
 */

use Phiz\Datasets;
use Phiz\Export;
use Phiz\Plot;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/evidence/bench.php';

const REPEATS = 7;
const WARMUPS = 1;

/** @return array<string, Closure(): Plot> */
function export_plots(): array
{
    return [
        'typical' => fn () => Plot::of(Datasets::mpg())->aes(x: 'displ', y: 'hwy')->point(alpha: 0.5)->smooth(method: 'lm'),
        'large' => function () {
            mt_srand(1);
            $rows = [];
            for ($i = 0; $i < 20_000; $i++) {
                $rows[] = ['x' => mt_rand() / mt_getrandmax(), 'y' => mt_rand() / mt_getrandmax()];
            }

            return Plot::of($rows)->aes(x: 'x', y: 'y')->point(alpha: 0.3);
        },
    ];
}

/** @return array<string, Closure(Plot): string> */
function export_formats(): array
{
    return ['SVG' => fn ($plot) => $plot->svg(), 'PNG, scale 2' => fn ($plot) => $plot->png(scale: 2.0), 'PDF' => fn ($plot) => $plot->pdf()];
}

// A case's process: time it and print its runs as JSON.
if (isset($argv[1]) && str_starts_with($argv[1], '--case=')) {
    [$plotName, $format] = explode('|', substr($argv[1], 7), 2);
    $plot = export_plots()[$plotName]();
    $export = export_formats()[$format];
    $bytes = 0;
    $runs = bench_time(function () use ($plot, $export, &$bytes) {
        $bytes = strlen($export($plot));
    }, REPEATS, WARMUPS, 'ms');
    $spec = bench_median(bench_time(fn () => $plot->toJson(), REPEATS, WARMUPS, 'ms'));
    $children = getrusage(1);
    $childRss = PHP_OS_FAMILY === 'Darwin' ? $children['ru_maxrss'] / 1048576 : $children['ru_maxrss'] / 1024;
    echo json_encode(['runs' => $runs, 'bytes' => $bytes, 'spec_ms' => $spec, 'spec_bytes' => strlen($plot->toJson()), 'child_peak_rss_mib' => round($childRss, 1), 'php_peak_mib' => round(memory_get_peak_usage(true) / 1048576, 1)]);
    exit(0);
}

if (! Export::available()) {
    fwrite(STDERR, "vl-convert is not installed; set PHIZ_VL_CONVERT or put it on PATH\n");
    exit(1);
}

$rows = [];
foreach (array_keys(export_plots()) as $plotName) {
    foreach (array_keys(export_formats()) as $format) {
        $result = bench_run(__FILE__, "{$plotName}|{$format}");
        $rows[] = [
            'case' => ($plotName === 'typical' ? '234 points with lm band' : '20,000 points').": {$format}",
            'implementation' => 'phiz + vl-convert',
            'value' => bench_round(bench_median($result['runs'])),
            'reps' => array_map(bench_round(...), $result['runs']),
            'peak_memory_mib' => $result['peak_memory_mib'],
            'php_peak_mib' => $result['php_peak_mib'],
            'child_peak_rss_mib' => $result['child_peak_rss_mib'],
            'note' => sprintf('Output: %d KB. Building the %d KB specification in PHP: %s ms of the total.', round($result['bytes'] / 1024), round($result['spec_bytes'] / 1024), bench_round($result['spec_ms'])),
        ];
    }
}

$binary = (string) (getenv('PHIZ_VL_CONVERT') ?: getenv('VL_CONVERT') ?: 'vl-convert');
$version = preg_replace('/^vl-convert\s*/', '', trim((string) shell_exec(escapeshellarg($binary).' --version 2>/dev/null')));
$phiz = trim((string) shell_exec('git -C '.escapeshellarg(dirname(__DIR__)).' describe --tags --always'));
bench_write('vl-convert-export', [
    'title' => 'Static export: SVG, PNG, and PDF through vl-convert',
    'label' => 'Static export',
    'description' => 'Export a plot with svg(), png(scale: 2), or pdf(): phiz builds the specification and runs vl-convert, which renders it without a browser.',
    'workload' => 'A 234-point scatterplot with a least-squares band, and a 20,000-point scatterplot',
    'dataset' => ['detail' => 'ggplot2\'s mpg (234 rows); 20,000 uniform points from mt_rand seeded with 1'],
    'implementations' => ['phiz + vl-convert' => "phiz {$phiz}, running vl-convert {$version} once per export"],
    'measure' => 'Elapsed time per export',
    'unit' => 'ms',
    'statistic' => 'median',
    'method' => 'From the export call to holding the image bytes: building the specification in PHP, starting vl-convert, rendering, and reading its output. One export is a warmup. Building the plot\'s data and starting PHP are excluded.',
    'time_spent_in' => 'vl-convert\'s executable, launched by phiz, plus building the specification in the PHP process (shown in each row\'s note)',
    'memory_measure' => 'peak anonymous memory',
    'memory_method' => 'peak_memory_mib is RssAnon of the PHP process alone, sampled every millisecond by peakmem (phpolars bench/csv-pipeline/peakmem.c). child_peak_rss_mib is the largest peak resident set size of the vl-convert processes, from getrusage(RUSAGE_CHILDREN) in the PHP process. php_peak_mib is memory_get_peak_usage(true).',
    'environment' => bench_environment(REPEATS, WARMUPS) + ['versions' => ['vl-convert' => $version]],
    'rows' => $rows,
    'reproduce' => ['composer install', 'PHIZ_VL_CONVERT=/path/to/vl-convert php scripts/bench-export.php'],
    'source' => 'scripts/bench-export.php',
    'group' => 'Static export',
]);

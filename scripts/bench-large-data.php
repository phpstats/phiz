<?php

declare(strict_types=1);

/*
 * Times the PHP binning geoms on a million-row phibble table, for the timing table in docs/large-data.qmd. Prints the
 * environment and a Markdown table. Each time is the best of three runs of building the plot and encoding its spec.
 * Needs phibble (a development dependency) and enough memory for the table.
 *
 *     php -d memory_limit=-1 scripts/bench-large-data.php
 */

use Composer\InstalledVersions;
use Phibble\DataFrame;
use Phiz\Plot;

require __DIR__.'/../vendor/autoload.php';

// Correlated normal pairs from a seeded generator, so every run bins the same table.
mt_srand(1);
$n = 1_000_000;
$x = [];
$y = [];
for ($i = 0; $i < $n; $i++) {
    $radius = sqrt(-2 * log(mt_rand() / mt_getrandmax() + 1e-12));
    $angle = 2 * M_PI * mt_rand() / mt_getrandmax();
    $x[] = $radius * cos($angle);
    $y[] = 0.6 * $x[$i] + 0.8 * $radius * sin($angle);
}
$table = DataFrame::fromColumns(['x' => $x, 'y' => $y]);
unset($x, $y);

$cpu = PHP_OS_FAMILY === 'Darwin'
    ? trim((string) shell_exec('sysctl -n machdep.cpu.brand_string'))
    : trim((string) shell_exec("grep -m1 'model name' /proc/cpuinfo | cut -d: -f2"));
printf("%s, %s, PHP %s, phiz %s, phibble %s\n\n", php_uname('s').' '.php_uname('r'), $cpu, PHP_VERSION, trim((string) shell_exec('git -C '.escapeshellarg(dirname(__DIR__)).' describe --tags')), InstalledVersions::getPrettyVersion('phpstats/phibble'));

echo "| Plot | Time | Spec size |\n| --- | --- | --- |\n";
$plots = [
    'hex(bins: 40)' => fn () => Plot::of($table)->aes(x: 'x', y: 'y')->hex(bins: 40),
    'bin2d(bins: 50)' => fn () => Plot::of($table)->aes(x: 'x', y: 'y')->bin2d(bins: 50),
    'histogram(bins: 60)' => fn () => Plot::of($table)->aes(x: 'x')->histogram(bins: 60),
];
foreach ($plots as $label => $build) {
    $best = INF;
    for ($run = 0; $run < 3; $run++) {
        $start = hrtime(true);
        $json = $build()->toJson();
        $best = min($best, (hrtime(true) - $start) / 1e9);
    }
    printf("| `%s` | %.1f s | %d KB |\n", $label, $best, round(strlen($json) / 1024));
}
printf("\nPeak memory: %d MB\n", memory_get_peak_usage() / 1048576);

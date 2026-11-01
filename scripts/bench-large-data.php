<?php

declare(strict_types=1);

/*
 * Times the PHP binning geoms on a million-row phibble table, for the timing table in docs/large-data.qmd.
 *
 * Each case runs in its own PHP process, wrapped in `peakmem -l` (phpolars' bench/csv-pipeline/peakmem.c) on Linux or
 * `/usr/bin/time -l` on macOS. The process builds the table, then times seven runs of building the plot and encoding
 * its spec. The script prints the machine, the median and every run, the spec size, and the process's peak anonymous
 * memory, which includes building the table. Needs phibble (a development dependency).
 *
 *     php scripts/bench-large-data.php
 */

use Composer\InstalledVersions;
use Phibble\DataFrame;
use Phiz\Plot;

require __DIR__.'/../vendor/autoload.php';

const RUNS = 7;

/** @return array<string, Closure(DataFrame): Plot> */
function bench_cases(): array
{
    return [
        'hex(bins: 40)' => fn ($table) => Plot::of($table)->aes(x: 'x', y: 'y')->hex(bins: 40),
        'bin2d(bins: 50)' => fn ($table) => Plot::of($table)->aes(x: 'x', y: 'y')->bin2d(bins: 50),
        'histogram(bins: 60)' => fn ($table) => Plot::of($table)->aes(x: 'x')->histogram(bins: 60),
    ];
}

/** One million correlated normal pairs from a seeded generator, so every process bins the same table. */
function bench_table(): DataFrame
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

/** A file's contents, or '' when it cannot be read. */
function bench_read(string $path): string
{
    return is_readable($path) ? (string) file_get_contents($path) : '';
}

/** The operating system, CPU, core count, and memory, from /proc and /etc/os-release on Linux. */
function bench_machine(): string
{
    if (PHP_OS_FAMILY === 'Linux') {
        preg_match('/^PRETTY_NAME="?([^"\n]*)/m', bench_read('/etc/os-release'), $os);
        $cpuinfo = bench_read('/proc/cpuinfo');
        preg_match('/^model name\s*:\s*(.+)$/m', $cpuinfo, $cpu);
        preg_match('/^MemTotal:\s*(\d+)/m', bench_read('/proc/meminfo'), $memory);

        return sprintf('%s, kernel %s, %s, %d logical CPUs, %.1f GiB memory', $os[1] ?? 'Linux', php_uname('r'), trim($cpu[1] ?? php_uname('m')), preg_match_all('/^processor\s*:/m', $cpuinfo), ($memory[1] ?? 0) / 1048576);
    }

    return sprintf('%s %s, %s, %.1f GiB memory', PHP_OS_FAMILY, php_uname('r'), trim((string) shell_exec('sysctl -n machdep.cpu.brand_string')), (int) shell_exec('sysctl -n hw.memsize') / 1073741824);
}

/** The middle value of a list, or the mean of the two middle values. */
function bench_median(array $values): float
{
    sort($values);
    $middle = intdiv(count($values), 2);

    return count($values) % 2 === 1 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
}

// A child process: time one case and print its runs as JSON.
if (isset($argv[1]) && str_starts_with($argv[1], '--case=')) {
    $build = bench_cases()[substr($argv[1], 7)];
    $table = bench_table();
    $runs = [];
    for ($run = 0; $run < RUNS; $run++) {
        $start = hrtime(true);
        $json = $build($table)->toJson();
        $runs[] = (hrtime(true) - $start) / 1e9;
    }
    echo json_encode(['runs' => $runs, 'bytes' => strlen($json)]);
    exit(0);
}

$wrapper = PHP_OS_FAMILY === 'Linux' ? trim((string) shell_exec('command -v peakmem')) : '/usr/bin/time';
if ($wrapper === '' || ! is_executable($wrapper)) {
    fwrite(STDERR, "peakmem is not on PATH; build it from phpolars' bench/csv-pipeline/peakmem.c\n");
    exit(1);
}

printf("Machine: %s\n", bench_machine());
printf("Software: PHP %s, phiz %s, phibble %s\n", PHP_VERSION, trim((string) shell_exec('git -C '.escapeshellarg(dirname(__DIR__)).' describe --tags --always')), InstalledVersions::getPrettyVersion('phpstats/phibble'));
printf("Method: %d runs per case in one process; median shown; memory is the process's peak anonymous memory (%s)\n\n", RUNS, basename($wrapper));

echo "| Plot | Median | Runs (s) | Spec size | Peak memory |\n| --- | --- | --- | --- | --- |\n";
foreach (array_keys(bench_cases()) as $case) {
    $command = array_map(escapeshellarg(...), [$wrapper, '-l', PHP_BINARY, '-d', 'memory_limit=-1', __FILE__, "--case={$case}"]);
    $process = proc_open(implode(' ', $command), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    if (proc_close($process) !== 0 || ! preg_match('/(\d+)\s+peak memory footprint/', $stderr, $peak)) {
        fwrite(STDERR, "{$case} failed:\n{$stderr}");
        exit(1);
    }
    $result = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
    printf(
        "| `%s` | %.2f s | %s | %d KB | %d MB |\n",
        $case,
        bench_median($result['runs']),
        implode(', ', array_map(fn ($t) => sprintf('%.2f', $t), $result['runs'])),
        round($result['bytes'] / 1024),
        round($peak[1] / 1048576),
    );
}

<?php

declare(strict_types=1);

/*
 * Shared parts of the benchmark scripts: the machine, one case run in its own process under peakmem, and the median.
 * A case's process prints JSON with its runs; peakmem (phpolars' bench/csv-pipeline/peakmem.c) reports the process's
 * peak anonymous memory (RssAnon, sampled every millisecond) and peak resident set size. On macOS, /usr/bin/time -l
 * stands in for local runs; records are published only from the Linux benchmark machine.
 */

require_once __DIR__.'/environment.php';

/** The machine and software, as a benchmark record's environment. */
function bench_environment(int $repeats, int $warmups): array
{
    $environment = evidence_environment();
    if (PHP_OS_FAMILY === 'Linux') {
        $cpuinfo = (string) @file_get_contents('/proc/cpuinfo');
        preg_match('/^model name\s*:\s*(.+)$/m', $cpuinfo, $cpu);
        preg_match('/^MemTotal:\s*(\d+)/m', (string) @file_get_contents('/proc/meminfo'), $memory);
        [$cpu, $cores, $gib] = [trim($cpu[1] ?? php_uname('m')), preg_match_all('/^processor\s*:/m', $cpuinfo), ($memory[1] ?? 0) / 1048576];
    } else {
        [$cpu, $cores, $gib] = [trim((string) shell_exec('sysctl -n machdep.cpu.brand_string')), (int) shell_exec('sysctl -n hw.logicalcpu'), (int) shell_exec('sysctl -n hw.memsize') / 1073741824];
    }

    return [
        'cpu' => $cpu,
        'cores' => $cores,
        'memory_gb' => round($gib, 1),
        'os' => $environment['os'],
        'php' => PHP_VERSION,
        'threads' => 1,
        'cache' => 'warm',
        'repeats' => $repeats,
        'warmups' => $warmups,
        'date' => $environment['date'],
        'commit' => $environment['commit'],
    ];
}

/** The memory wrapper: peakmem on Linux, /usr/bin/time on macOS. */
function bench_wrapper(): string
{
    $wrapper = PHP_OS_FAMILY === 'Linux' ? trim((string) shell_exec('command -v peakmem')) : '/usr/bin/time';
    if ($wrapper === '' || ! is_executable($wrapper)) {
        fwrite(STDERR, "peakmem is not on PATH; build it from phpolars' bench/csv-pipeline/peakmem.c\n");
        exit(1);
    }

    return $wrapper;
}

/**
 * Runs one case of a benchmark script in its own process under the memory wrapper.
 *
 * @return array<string, mixed> The case's JSON, with peak_memory_mib (peak anonymous memory) and peak_rss_mib.
 */
function bench_run(string $script, string $case): array
{
    $command = array_map(escapeshellarg(...), [bench_wrapper(), '-l', PHP_BINARY, '-d', 'memory_limit=-1', $script, "--case={$case}"]);
    $process = proc_open(implode(' ', $command), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    if (proc_close($process) !== 0 || ! preg_match('/(\d+)\s+peak memory footprint/', $stderr, $anon) || ! preg_match('/(\d+)\s+maximum resident set size/', $stderr, $rss)) {
        fwrite(STDERR, "{$case} failed:\n{$stderr}");
        exit(1);
    }

    return json_decode($stdout, true, flags: JSON_THROW_ON_ERROR) + [
        'peak_memory_mib' => round($anon[1] / 1048576, 1),
        'peak_rss_mib' => round($rss[1] / 1048576, 1),
    ];
}

/** Times a callable: warmup runs, then timed runs, in the given unit ('s' or 'ms'). */
function bench_time(callable $run, int $repeats, int $warmups, string $unit, int $iterations = 1): array
{
    for ($i = 0; $i < $warmups; $i++) {
        $run();
    }
    $times = [];
    for ($i = 0; $i < $repeats; $i++) {
        $start = hrtime(true);
        for ($j = 0; $j < $iterations; $j++) {
            $run();
        }
        $times[] = (hrtime(true) - $start) / $iterations / ($unit === 's' ? 1e9 : 1e6);
    }

    return $times;
}

/** The middle value of a list, or the mean of the two middle values. */
function bench_median(array $values): float
{
    sort($values);
    $middle = intdiv(count($values), 2);

    return count($values) % 2 === 1 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
}

/** Rounds times for a record: three significant digits. */
function bench_round(float $value): float
{
    return $value == 0 ? 0.0 : round($value, max(0, 2 - (int) floor(log10(abs($value)))));
}

/** Writes a benchmark record to site/benchmarks/{id}.json and prints its rows. */
function bench_write(string $id, array $record): void
{
    $root = dirname(__DIR__, 2);
    @mkdir("{$root}/site/benchmarks", 0777, true);
    file_put_contents("{$root}/site/benchmarks/{$id}.json", json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n");
    fwrite(STDERR, "Wrote site/benchmarks/{$id}.json\n");
    foreach ($record['rows'] as $row) {
        fwrite(STDERR, sprintf("  %-34s %-22s %8s %s\n", $row['case'], $row['implementation'], $row['value'], $record['unit']));
    }
}

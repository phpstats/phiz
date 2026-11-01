<?php

declare(strict_types=1);

/** The run's date, commit, PHP version, and operating system, for an evidence record's environment. */
function evidence_environment(): array
{
    $commit = trim((string) shell_exec('git -C '.escapeshellarg(dirname(__DIR__, 2)).' rev-parse --short HEAD 2>/dev/null'));
    if (PHP_OS_FAMILY === 'Linux') {
        preg_match('/^PRETTY_NAME="?([^"\n]*)/m', is_readable('/etc/os-release') ? (string) file_get_contents('/etc/os-release') : '', $name);
        $os = 'Linux, '.($name[1] ?? 'unknown distribution').' ('.php_uname('m').')';
    } else {
        $os = PHP_OS_FAMILY.' '.php_uname('r').' ('.php_uname('m').')';
    }

    return ['date' => gmdate('Y-m-d'), 'commit' => $commit ?: null, 'php' => PHP_VERSION, 'os' => $os];
}

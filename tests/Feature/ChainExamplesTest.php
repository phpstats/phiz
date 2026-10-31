<?php

declare(strict_types=1);

use Phiz\Chart;

/*
 * The method-chain versions of the pipe examples, which run on every supported PHP version. tests/Pipes checks on
 * PHP 8.5 that each builds the same specification as its pipe version.
 */

dataset('chain examples', array_map(fn ($file) => [$file], glob(__DIR__.'/../../docs/examples/*.chain.php')));

it('builds a valid spec for every method-chain documentation example', function (string $file) {
    $chart = (static fn () => require $file)();

    expect($chart)->toBeInstanceOf(Chart::class)->toBeValidVegaLite();
})->with('chain examples');

it('builds the dashboard with method chains', function () {
    $charts = dashboardCharts(__DIR__.'/../../examples/dashboard.chain.php');

    expect($charts['classes'])->toBe(['compact', 'subcompact', 'midsize', 'minivan'])
        ->and($charts['bars'])->toBeValidVegaLite()
        ->and($charts['scatter'])->toBeValidVegaLite()
        ->and($charts['trend'])->toBeValidVegaLite();
});

/**
 * The dashboard's specifications and class order from one of its versions, built in a separate process because both
 * versions declare fuel_dashboard().
 *
 * @return array{classes: list<string>, bars: array, scatter: array, trend: array}
 */
function dashboardCharts(string $file): array
{
    $script = 'require '.var_export(dirname(__DIR__, 2).'/vendor/autoload.php', true).'; require '.var_export($file, true).';'
        .' $c = fuel_dashboard(Phibble\DataFrame::fromRows(Phiz\Datasets::mpg()), drive: "f", year: 2008, selected: "compact");'
        .' echo json_encode(["classes" => array_column($c["summary"]->toRows(), "class"), "bars" => $c["bars"]->toArray(), "scatter" => $c["scatter"]->toArray(), "trend" => $c["trend"]->toArray()]);';
    exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($script).' 2>&1', $lines, $status);
    expect($status)->toBe(0, implode("\n", $lines));

    return json_decode(implode("\n", $lines), true, flags: JSON_THROW_ON_ERROR);
}

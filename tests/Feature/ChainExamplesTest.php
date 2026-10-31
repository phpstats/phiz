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

<?php

declare(strict_types=1);

use Phibble\DataFrame;
use Phiz\Datasets;

require_once __DIR__.'/../../examples/dashboard.php';

it('builds the dashboard charts from phplyr summaries of a phibble table', function () {
    $charts = fuel_dashboard(DataFrame::fromRows(Datasets::mpg()), drive: 'f', year: 2008, selected: 'compact');
    $classes = array_column($charts['summary']->toRows(), 'class');

    expect($classes)->toBe(['compact', 'subcompact', 'midsize', 'minivan'])
        ->and($charts['bars'])->toBeValidVegaLite()
        ->and($charts['scatter'])->toBeValidVegaLite()
        ->and($charts['trend'])->toBeValidVegaLite()
        ->and(array_unique(array_column($charts['trend']->data->rows, 'year')))->toEqualCanonicalizing([1999, 2008]);
});

it('builds the same dashboard with pipes and with method chains', function () {
    $examples = __DIR__.'/../../examples';

    expect(dashboardCharts("{$examples}/dashboard.php"))->toBe(dashboardCharts("{$examples}/dashboard.chain.php"));
});

it('builds the same documentation examples with pipes and with method chains', function () {
    foreach (glob(__DIR__.'/../../docs/examples/*.chain.php') as $chain) {
        $pipe = (static fn () => require str_replace('.chain.php', '.php', $chain))();

        expect((static fn () => require $chain)()->toArray())->toBe($pipe->toArray());
    }
});

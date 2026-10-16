<?php

declare(strict_types=1);

use Phibble\Frame;
use Phiz\Datasets;

require_once __DIR__.'/../../examples/dashboard.php';

it('builds the dashboard charts from phplyr summaries of a phibble table', function () {
    $charts = fuel_dashboard(Frame::fromRows(Datasets::mpg()), drive: 'f', year: 2008, selected: 'compact');
    $classes = array_column($charts['summary']->toRows(), 'class');

    expect($classes)->toBe(['compact', 'subcompact', 'midsize', 'minivan'])
        ->and($charts['bars'])->toBeValidVegaLite()
        ->and($charts['scatter'])->toBeValidVegaLite()
        ->and($charts['trend'])->toBeValidVegaLite()
        ->and(array_unique(array_column($charts['trend']->data->rows, 'year')))->toEqualCanonicalizing([1999, 2008]);
});

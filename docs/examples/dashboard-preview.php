<?php

use Phibble\DataFrame;
use Phiz\Compose;
use Phiz\Datasets;

require_once __DIR__.'/../../examples/dashboard.php';

// The page shows only this chart: the dashboard with front-wheel drive, 2008, and the compact class selected. In the
// application each chart fills a grid cell; here they get fixed widths so the composition fits the page.
$charts = fuel_dashboard(DataFrame::fromRows(Datasets::mpg()), drive: 'f', year: 2008, selected: 'compact');

// The bars' selection colors and the scatterplot's class colors are separate scales, as they are in separate elements.
$independent = ['scale' => ['color' => 'independent']];

return Compose::stack(
    Compose::beside($charts['bars']->width(250), $charts['scatter']->width(250))->resolve($independent),
    $charts['trend']->width(250),
)->resolve($independent);

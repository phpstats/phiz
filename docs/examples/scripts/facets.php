<?php

require 'vendor/autoload.php';

use Phiz\Datasets;

use function Phiz\{
    aes, facet_wrap, geom_point, ggplot, labs,
};

$mtcars = Datasets::mtcars();

$plot = ggplot($mtcars, aes(x: 'wt', y: 'mpg')) |>
    geom_point() |>
    facet_wrap('cyl', ncol: 3) |>
    labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('facets.html');

<?php

require 'vendor/autoload.php';

use Phiz\Datasets;

use function Phiz\{aes, geom_point, ggplot, labs, size};

$mtcars = Datasets::mtcars();

$plot = ggplot($mtcars, aes(x: 'wt', y: 'mpg')) |>
    geom_point() |>
    labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon') |>
    size(640, 360);

// Drawn in the browser.
$plot->save('chart.html');

// Rendered by vl-convert.
$plot->save('chart.svg');
$plot->save('chart.png', scale: 2);

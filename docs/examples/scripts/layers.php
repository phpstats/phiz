<?php

require 'vendor/autoload.php';

use Phiz\Datasets;

use function Phiz\{
    aes, geom_point, geom_smooth, ggplot, labs,
};

$mpg = Datasets::mpg();

$plot = ggplot($mpg, aes(x: 'displ', y: 'hwy')) |>
    geom_point(alpha: 0.5) |>
    geom_smooth(method: 'lm') |>
    labs(
        x: 'Engine displacement (L)',
        y: 'Highway miles per gallon',
    );

$plot->save('layers.html');

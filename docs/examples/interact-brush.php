<?php

use Phit\Compose;
use Phit\Datasets;
use Phit\Plot;

$mpg = Datasets::mpg();

return Compose::stack(
    Plot::of($mpg)->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->brush(encodings: ['x', 'y'])->size(420, 220),
    Plot::of($mpg)->aes(x: 'class', fill: 'drv')->bar(filter: ['param' => 'brush'])->size(420, 140),
);

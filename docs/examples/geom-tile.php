<?php

use Phit\Datasets;
use Phit\Plot;

use function Phit\factor;

return Plot::of(Datasets::mpg())
    ->aes(x: 'class', y: factor('cyl'), fill: 'mean(hwy)')
    ->tile(color: 'white')
    ->scaleFill(scheme: 'viridis')
    ->labs(x: null, y: 'Cylinders', fill: 'Mean hwy');

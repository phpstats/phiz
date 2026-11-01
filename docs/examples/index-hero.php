<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm')
    ->labs(
        x: 'Displacement (L)',
        y: 'Highway mpg',
        color: 'Drive',
    );

<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm')
    ->labs(title: 'Larger engines travel fewer highway miles per gallon', x: 'Displacement (L)', y: 'Highway mpg', color: 'Drive');

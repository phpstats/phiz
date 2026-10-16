<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.4)
    ->smooth(span: 0.75)
    ->labs(x: 'Displacement (L)', y: 'Highway mpg');

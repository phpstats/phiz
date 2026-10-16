<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.4)
    ->smooth(span: 0.6)
    ->labs(x: 'Displacement (L)', y: 'Highway mpg');

<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy', color: 'class')
    ->point(size: 40, alpha: 0.7);

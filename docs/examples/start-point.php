<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point();

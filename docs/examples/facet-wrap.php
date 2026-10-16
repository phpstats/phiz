<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm', color: '#D62728')
    ->facetWrap('drv', ncol: 3)
    ->size(180, 160);

<?php

use Phit\Datasets;
use Phit\Plot;

use function Phit\calc;

return Plot::of(Datasets::mpg())
    ->aes(x: 'cty', y: calc('datum.hwy - datum.cty', as: 'gap'))
    ->jitter(width: 0.3, height: 0.3, alpha: 0.5)
    ->labs(x: 'City mpg', y: 'Highway minus city mpg');

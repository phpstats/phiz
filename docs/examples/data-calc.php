<?php

use Phiz\Datasets;
use Phiz\Plot;

use function Phiz\calc;

return Plot::of(Datasets::mpg())
    ->aes(x: 'cty', y: calc('datum.hwy - datum.cty', as: 'gap'))
    ->jitter(width: 0.3, height: 0.3, alpha: 0.5, seed: 1)
    ->labs(x: 'City mpg', y: 'Highway minus city mpg');

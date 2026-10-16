<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'cty', y: 'hwy')
    ->point(alpha: 0.5)
    ->abline(color: 'gray', linetype: 'dashed')
    ->coordFixed()
    ->labs(x: 'City mpg', y: 'Highway mpg');

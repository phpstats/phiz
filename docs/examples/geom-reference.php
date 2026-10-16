<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'cty', y: 'hwy')
    ->point(alpha: 0.4)
    ->abline(0, 1, linetype: 'dashed')
    ->hline(30, color: '#D62728')
    ->vline(20, color: '#1F77B4')
    ->labs(x: 'City mpg', y: 'Highway mpg');

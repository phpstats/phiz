<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'class', y: 'hwy')
    ->point(alpha: 0.25)
    ->summary(funData: 'mean_cl_normal', color: '#D62728')
    ->labs(x: null, y: 'Highway mpg', title: 'Means with 95% t intervals');

<?php

use Phiz\Datasets;
use Phiz\Plot;

use function Phiz\factor;

return Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg', color: factor('cyl'), shape: 'am:N')
    ->point(size: 60)
    ->labs(color: 'Cylinders', shape: 'Manual');

<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::faithful())
    ->aes(x: 'eruptions', y: 'waiting')
    ->point(size: 10, alpha: 0.6)
    ->density2d()
    ->labs(x: 'Eruption (minutes)', y: 'Waiting time (minutes)');

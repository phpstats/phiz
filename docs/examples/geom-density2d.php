<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::faithful())
    ->aes(x: 'eruptions', y: 'waiting')
    ->point(size: 10, alpha: 0.6)
    ->density2d()
    ->labs(x: 'Eruption (minutes)', y: 'Waiting time (minutes)');

<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'drv', y: 'hwy')
    ->boxplot(outliers: false)
    ->jitter(width: 0.25, alpha: 0.35, seed: 1)
    ->labs(x: 'Drive', y: 'Highway mpg');

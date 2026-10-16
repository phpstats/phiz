<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'drv', y: 'hwy')
    ->boxplot(outliers: false, fill: '#DDDDDD', color: '#333333')
    ->jitter(width: 0.25, alpha: 0.35)
    ->labs(x: 'Drive', y: 'Highway mpg');

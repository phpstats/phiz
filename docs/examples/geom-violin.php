<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'drv', y: 'hwy', fill: 'drv')
    ->violin(alpha: 0.35, adjust: 1.2)
    ->jitter(width: 0.08, alpha: 0.8, seed: 1)
    ->theme('gray')
    ->labs(x: 'Drive', y: 'Highway mpg', fill: 'Drive');

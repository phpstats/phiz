<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'drv', y: 'hwy', fill: 'drv')
    ->violin(alpha: 0.6, adjust: 1.2)
    ->jitter(width: 0.08, alpha: 0.3)
    ->theme('gray')
    ->labs(x: 'Drive', y: 'Highway mpg', fill: 'Drive');

<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'hwy', fill: 'drv')
    ->density(alpha: 0.4, adjust: 1.5)
    ->labs(x: 'Highway mpg', fill: 'Drive');

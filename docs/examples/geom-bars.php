<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'class', fill: 'drv')
    ->bar(position: 'dodge')
    ->labs(x: null, y: 'Cars', fill: 'Drive');

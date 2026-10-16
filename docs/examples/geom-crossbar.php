<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'class', y: 'hwy', fill: 'drv')
    ->summary(funData: 'median_hilow', funArgs: ['conf' => 0.5], geom: 'crossbar', position: 'dodge')
    ->labs(x: null, y: 'Highway mpg', title: 'Medians and quartiles');

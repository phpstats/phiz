<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point(size: 40)
    ->scaleColor(values: ['f' => '#1B9E77', 'r' => '#7570B3', '4' => '#D95F02'], name: 'Drive');

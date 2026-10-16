<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::economics())
    ->aes(x: 'pop', y: 'pce')
    ->point(alpha: 0.4)
    ->scaleY(type: 'log', breaks: [500, 1000, 2000, 5000, 10000], name: 'Personal consumption ($bn, log scale)')
    ->scaleX(format: '.3s', name: 'Population (thousands)');

<?php

use Phiz\Plot;

$points = require __DIR__.'/_cloud.php';

return Plot::of($points)
    ->aes(x: 'x', fill: 'group')
    ->histogram(bins: 60, position: 'identity', alpha: 0.6)
    ->labs(fill: null);

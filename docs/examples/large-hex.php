<?php

use Phit\Plot;

$points = require __DIR__.'/_cloud.php'; // 200,000 rows as columns

return Plot::of($points)
    ->aes(x: 'x', y: 'y')
    ->hex(bins: 40)
    ->scaleFill(scheme: 'viridis')
    ->labs(title: '200,000 points in hexagonal bins');

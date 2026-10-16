<?php

use Phit\Plot;

$points = require __DIR__.'/_cloud.php';

// Above Config::$serverRows rows, bin2d() counts in PHP; the spec holds only the cells.
return Plot::of($points)
    ->aes(x: 'x', y: 'y')
    ->bin2d(bins: 40)
    ->facetWrap('group')
    ->size(240, 220)
    ->scaleFill(scheme: 'blues');

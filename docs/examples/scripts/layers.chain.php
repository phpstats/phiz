<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm')
    ->labs(x: 'Engine displacement (L)', y: 'Highway miles per gallon');

$plot->save('layers.html');

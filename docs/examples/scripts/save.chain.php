<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('chart.html');            // no extra software needed
$plot->save('chart.svg');             // needs vl-convert
$plot->save('chart.png', scale: 2);   // needs vl-convert

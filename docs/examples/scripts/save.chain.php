<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('chart.html');            // drawn by the browser
$plot->save('chart.svg');             // rendered by vl-convert
$plot->save('chart.png', scale: 2);   // rendered by vl-convert

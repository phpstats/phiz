<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$mtcars = Datasets::mtcars();

$plot = Plot::of($mtcars)
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon')
    ->size(640, 360);

// Drawn in the browser.
$plot->save('chart.html');

// Rendered by vl-convert.
$plot->save('chart.svg');
$plot->save('chart.png', scale: 2);

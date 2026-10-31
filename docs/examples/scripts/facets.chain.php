<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->facetWrap('cyl', ncol: 3)
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('facets.html');

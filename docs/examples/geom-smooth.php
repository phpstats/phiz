<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mtcars())
    ->aes(x: 'hp', y: 'mpg')
    ->point()
    ->smooth(method: 'poly', order: 2, level: 0.9)
    ->labs(x: 'Horsepower', y: 'mpg');

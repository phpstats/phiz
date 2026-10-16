<?php

use Phit\Datasets;
use Phit\Plot;

$fast = array_values(array_filter(Datasets::mtcars(), fn ($car) => $car['hp'] > 180));

return Plot::of($fast)
    ->aes(x: 'hp', y: 'qsec', label: 'model')
    ->point()
    ->text(hjust: 0, nudgeX: 6, size: 10)
    ->labs(x: 'Horsepower', y: 'Quarter-mile time (s)');

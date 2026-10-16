<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'manufacturer', y: 'hwy')
    ->boxplot(fill: '#C6DBEF')
    ->coordFlip()
    ->labs(x: null, y: 'Highway mpg');

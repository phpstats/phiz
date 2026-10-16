<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'hwy')
    ->histogram(binwidth: 2)
    ->facetGrid(rows: 'year', cols: 'drv', scales: 'free_y')
    ->size(160, 100);

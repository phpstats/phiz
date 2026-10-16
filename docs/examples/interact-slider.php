<?php

use Phiz\Datasets;
use Phiz\Plot;

use function Phiz\expr;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->slider('cutoff', 15, 40, step: 1, value: 30, label: 'Highway mpg cutoff')
    ->point(filter: 'datum.hwy >= cutoff', color: '#D62728')
    ->point(filter: 'datum.hwy < cutoff', color: '#BBBBBB')
    ->hline(expr('cutoff'), linetype: 'dashed');

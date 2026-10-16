<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::faithful())
    ->aes(x: 'waiting')
    ->histogram(binwidth: 2, fill: '#4C78A8')
    ->labs(x: 'Minutes between eruptions');

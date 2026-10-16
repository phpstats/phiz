<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::faithful())
    ->aes(x: 'eruptions', y: 'waiting')
    ->density2dFilled()
    ->labs(x: 'Eruption (minutes)', y: 'Waiting time (minutes)', fill: 'density');

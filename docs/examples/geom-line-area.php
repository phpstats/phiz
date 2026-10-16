<?php

use Phiz\Datasets;
use Phiz\Plot;

$recent = array_slice(Datasets::economics(), -180);

return Plot::of($recent)
    ->aes(x: 'date', y: 'uempmed')
    ->area(fill: '#9ECAE1', alpha: 0.5)
    ->line(color: '#08519C')
    ->labs(x: null, y: 'Median weeks unemployed');

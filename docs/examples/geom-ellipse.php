<?php

use Phiz\Datasets;
use Phiz\Plot;

$faithful = array_map(fn ($row) => $row + ['wait' => $row['waiting'] > 70 ? 'long' : 'short'], Datasets::faithful());

return Plot::of($faithful)
    ->aes(x: 'eruptions', y: 'waiting', color: 'wait')
    ->point(alpha: 0.6)
    ->ellipse()
    ->ellipse(type: 'norm', linetype: 'dashed')
    ->labs(x: 'Eruption (minutes)', y: 'Waiting time (minutes)', color: 'Wait');

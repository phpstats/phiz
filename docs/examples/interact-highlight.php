<?php

use Phiz\Datasets;
use Phiz\Plot;

$long = [];
foreach (Datasets::economics() as $row) {
    if ($row['date'] >= '2000-01-01') {
        $long[] = ['date' => $row['date'], 'series' => 'Savings rate (%)', 'value' => $row['psavert']];
        $long[] = ['date' => $row['date'], 'series' => 'Median weeks unemployed', 'value' => $row['uempmed']];
    }
}

return Plot::of($long)
    ->aes(x: 'date', y: 'value', color: 'series')
    ->line(linewidth: 2)
    ->highlight()
    ->labs(x: null, y: null, color: 'Click a series');

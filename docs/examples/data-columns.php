<?php

use Phit\Plot;

$columns = [
    'month' => ['2026-01-01', '2026-02-01', '2026-03-01', '2026-04-01', '2026-05-01', '2026-06-01'],
    'visits' => [1200, 1350, 1290, 1610, 1820, 1765],
];

return Plot::of($columns)
    ->aes(x: 'month', y: 'visits')
    ->line()
    ->point(size: 50);

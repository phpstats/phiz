<?php

use Phit\Plot;

$estimates = [
    ['site' => 'North', 'mean' => 4.1, 'lower' => 3.2, 'upper' => 5.0],
    ['site' => 'East', 'mean' => 5.3, 'lower' => 4.6, 'upper' => 6.1],
    ['site' => 'South', 'mean' => 3.4, 'lower' => 2.1, 'upper' => 4.6],
    ['site' => 'West', 'mean' => 4.8, 'lower' => 4.2, 'upper' => 5.5],
];

return Plot::of($estimates)
    ->aes(x: 'site', y: 'mean', ymin: 'lower', ymax: 'upper')
    ->errorbar(width: 10)
    ->point(size: 60)
    ->hline(4.5, linetype: 'dashed', color: '#888888')
    ->labs(x: null, y: 'Mean score (95% CI)');

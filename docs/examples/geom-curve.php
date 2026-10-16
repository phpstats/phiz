<?php

use Phiz\Datasets;
use Phiz\Plot;

$eruptions = array_column(Datasets::faithful(), 'eruptions');
$mean = array_sum($eruptions) / count($eruptions);
$sd = sqrt(array_sum(array_map(fn ($x) => ($x - $mean) ** 2, $eruptions)) / (count($eruptions) - 1));
$normal = fn (float $x) => exp(-0.5 * (($x - $mean) / $sd) ** 2) / ($sd * sqrt(2 * M_PI));

return Plot::of(Datasets::faithful())
    ->aes(x: 'eruptions')
    ->histogram(binwidth: 0.2, density: true, fill: '#DDDDDD')
    ->curve($normal, 1.5, 5.5, aes: ['x' => 'eruptions', 'y' => 'density'], color: '#D62728')
    ->labs(x: 'Eruption length (min)', subtitle: 'A single normal curve fits this bimodal distribution poorly');

<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point()
    ->theme('minimal', legendPosition: 'bottom', font: 'Georgia, serif', config: ['axis' => ['labelColor' => '#666666']])
    ->labs(title: 'A minimal theme with the legend below');

<?php

use Phit\Datasets;
use Phit\Plot;

use function Phit\guide_axis;
use function Phit\label_percent;
use function Phit\scale_fill_viridis_d;

return Plot::of(Datasets::mpg())
    ->aes(x: 'class', fill: 'drv')
    ->bar(position: 'fill')
    ->scaleY(labels: label_percent(), name: 'Share of cars')
    ->apply(scale_fill_viridis_d(begin: 0.2, end: 0.8))
    ->guides(x: guide_axis(angle: 45))
    ->labs(x: null, fill: 'Drive');

<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mpg())
    ->aes(x: 'cty', y: 'hwy')
    ->annotate('rect', xmin: 15, xmax: 20, ymin: -INF, ymax: INF, fill: 'orange', alpha: 0.2)
    ->count(alpha: 0.6)
    ->annotate('segment', x: 30, y: 25, xend: 33.5, yend: 42.5, color: '#D62728')
    ->annotate('text', x: 30, y: 23, label: 'Most efficient', color: '#D62728')
    ->labs(x: 'City mpg', y: 'Highway mpg');

<?php

use Phiz\Compose;
use Phiz\Datasets;
use Phiz\Plot;

use function Phiz\field;

$mpg = Datasets::mpg();

return Compose::beside(
    Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(alpha: 0.7)
        ->brush(encodings: ['x', 'y'])
        ->size(300, 240),
    Plot::of($mpg)
        ->aes(
            x: 'mean(hwy)',
            y: field('class', sort: '-x'),
        )
        ->col(filter: ['param' => 'brush'], fill: '#4C78A8')
        ->labs(x: 'Mean highway mpg', y: null)
        ->size(240, 240),
);

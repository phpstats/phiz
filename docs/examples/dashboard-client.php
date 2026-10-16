<?php

use Phiz\Compose;
use Phiz\Datasets;
use Phiz\Plot;

$mpg = Datasets::mpg();

// Linked in the browser: brushing the scatterplot filters the bars, with no request to the server.
return Compose::beside(
    Plot::of($mpg)->aes(x: 'displ', y: 'hwy', color: 'drv')->point(alpha: 0.7)->brush(encodings: ['x', 'y'])->size(320, 240),
    Plot::of($mpg)->aes(x: 'mean(hwy)', y: \Phiz\field('class', sort: '-x'))->col(filter: ['param' => 'brush'], fill: '#4C78A8')->labs(x: 'Mean highway mpg', y: null)->size(260, 240),
);

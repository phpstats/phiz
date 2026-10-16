<?php

use Phit\Datasets;
use Phit\Plot;

return Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg', color: 'hp')
    ->point(size: 70)
    ->tooltip(['model', 'mpg', 'wt', 'hp'])
    ->interactive()
    ->scaleColor(scheme: 'plasma');

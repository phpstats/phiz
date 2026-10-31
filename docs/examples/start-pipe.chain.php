<?php

use Phiz\Datasets;
use Phiz\Plot;

use function Phiz\aes;

return Plot::of(Datasets::mpg(), aes(x: 'displ', y: 'hwy', color: 'drv'))
    ->point(alpha: 0.5)
    ->smooth(method: 'lm', se: false)
    ->labs(x: 'Displacement (L)', y: 'Highway mpg', color: 'Drive')
    ->theme('minimal');

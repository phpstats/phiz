<?php

use Phiz\Datasets;
use Phiz\Plot;

use function Phiz\scale_color_grey;
use function Phiz\scale_x_discrete;

return Plot::of(Datasets::mpg())
    ->aes(x: 'drv', y: 'hwy', color: 'drv')
    ->boxplot()
    ->apply(scale_x_discrete(limits: ['f', 'r', '4'], labels: ['f' => 'Front', 'r' => 'Rear', '4' => '4WD']))
    ->apply(scale_color_grey())
    ->guides(color: 'none')
    ->labs(x: 'Drive', y: 'Highway mpg')
    ->theme('bw');

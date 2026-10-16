<?php

use Phit\Datasets;

use function Phit\marginal_means;

// stats_lm('mpg ~ factor(cyl) + factor(am) + wt', $mtcars)
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm-factors.json'), true);

return marginal_means($fit, Datasets::mtcars(), 'cyl', by: 'am')
    ->labs(x: 'Cylinders', color: 'Manual');

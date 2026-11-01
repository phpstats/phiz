<?php

use Phiz\Datasets;

use function Phiz\marginal_means;

// Phit\fit_ols('mpg ~ factor(cyl) + factor(am) + wt', $mtcars)
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm-factors.json'), true);

return marginal_means($fit, Datasets::mtcars(), 'cyl', by: 'am')
    ->labs(x: 'Cylinders', color: 'Manual');

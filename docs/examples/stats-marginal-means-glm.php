<?php

use Phit\Datasets;

use function Phit\marginal_means;

// stats_glm('am ~ factor(cyl) + wt', $mtcars, 'binomial'): means on the probability scale.
$glm = json_decode(file_get_contents(__DIR__.'/fits/mtcars-glm-factor.json'), true);

return marginal_means($glm, Datasets::mtcars(), 'cyl')
    ->labs(x: 'Cylinders', y: 'P(manual), marginal mean (95% CI)');

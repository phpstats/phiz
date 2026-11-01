<?php

use function Phiz\coefplot;

// The fit as phit returns it: Phit\fit_ols('mpg ~ wt + hp + am', $mtcars).
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm.json'), true);

return coefplot($fit)->labs(title: 'mpg ~ wt + hp + am');

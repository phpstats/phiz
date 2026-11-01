<?php

use function Phiz\diagnostic_plots;

// The fit as phit returns it: Phit\fit_ols('mpg ~ wt + hp + am', $mtcars).
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm.json'), true);

return diagnostic_plots($fit, width: 240, height: 180);

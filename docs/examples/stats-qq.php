<?php

use function Phiz\qq_plot;

// The fit as phit returns it: Phit\fit_lm('mpg ~ wt + hp + am', $mtcars).
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm.json'), true);

return qq_plot($fit);

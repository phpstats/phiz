<?php

use function Phit\qq_plot;

// The fit as php-stats returns it: stats_lm('mpg ~ wt + hp + am', $mtcars).
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm.json'), true);

return qq_plot($fit);

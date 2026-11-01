<?php

use Phiz\Datasets;

use function Phiz\random_effects_plot;

// Phit\fit_mixed_gaussian('hwy ~ displ + (1 + displ | manufacturer)', $mpg)
$mixed = json_decode(file_get_contents(__DIR__.'/fits/mpg-lmer-slopes.json'), true);

return random_effects_plot($mixed, Datasets::mpg());

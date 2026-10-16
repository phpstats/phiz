<?php

use Phit\Datasets;

use function Phit\random_effects_plot;

// stats_lmer('hwy ~ displ + (1 + displ | manufacturer)', $mpg)
$mixed = json_decode(file_get_contents(__DIR__.'/fits/mpg-lmer-slopes.json'), true);

return random_effects_plot($mixed, Datasets::mpg());

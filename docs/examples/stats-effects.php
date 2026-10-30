<?php

use Phiz\Datasets;

use function Phiz\effect_plot;

// The fit as phit returns it: Phit\fit_lm('mpg ~ wt + hp + am', $mtcars).
$fit = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm.json'), true);

// Formula fits build the prediction design with Phit\model_matrix(); here we pass it directly.
return effect_plot($fit, Datasets::mtcars(), 'wt', by: 'am', design: fn (array $row) => [1, $row['wt'], $row['hp'], $row['am']])
    ->labs(x: 'Weight (1000 lbs)', y: 'mpg', color: 'Manual', fill: 'Manual');

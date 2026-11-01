<?php

use Phiz\Datasets;

use function Phiz\effect_plot;

// Phit\fit_generalized_linear('am ~ wt + hp', $mtcars, 'binomial'): predictions on the probability scale.
$glm = json_decode(file_get_contents(__DIR__.'/fits/mtcars-glm.json'), true);

return effect_plot($glm, Datasets::mtcars(), 'wt', design: fn (array $row) => [1, $row['wt'], $row['hp']])
    ->labs(x: 'Weight (1000 lbs)', y: 'P(manual transmission)');

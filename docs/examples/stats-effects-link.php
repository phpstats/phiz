<?php

use Phit\Datasets;

use function Phit\effect_plot;

// The same logistic fit, on the log-odds scale, where the model is linear.
$glm = json_decode(file_get_contents(__DIR__.'/fits/mtcars-glm.json'), true);

return effect_plot($glm, Datasets::mtcars(), 'wt', design: fn (array $row) => [1, $row['wt'], $row['hp']], scale: 'link')
    ->labs(x: 'Weight (1000 lbs)');

<?php

use function Phiz\coefplot;

// stats_glm('am ~ wt + hp', $mtcars, 'binomial')
$glm = json_decode(file_get_contents(__DIR__.'/fits/mtcars-glm.json'), true);

return coefplot($glm, exponentiate: true)->labs(x: 'Odds ratio (95% CI)');

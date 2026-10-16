<?php

use function Phit\coefplot;

$full = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm.json'), true);
$reduced = json_decode(file_get_contents(__DIR__.'/fits/mtcars-lm-reduced.json'), true);

return coefplot(['mpg ~ wt + hp + am' => $full, 'mpg ~ wt + hp' => $reduced]);

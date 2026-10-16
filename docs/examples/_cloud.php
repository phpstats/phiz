<?php

// 200,000 correlated points, generated with a fixed seed, as columns.
$rng = new Random\Randomizer(new Random\Engine\Mt19937(42));
$columns = ['x' => [], 'y' => [], 'group' => []];
for ($i = 0; $i < 200_000; $i++) {
    $r = sqrt(-2 * log($rng->getFloat(1e-12, 1)));
    $t = 2 * M_PI * $rng->getFloat(0, 1);
    $shift = $i % 3 === 0 ? 2.5 : 0.0;
    $columns['x'][] = $r * cos($t) + $shift;
    $columns['y'][] = 0.6 * $r * cos($t) + 0.8 * $r * sin($t) - $shift / 2;
    $columns['group'][] = $i % 3 === 0 ? 'shifted' : 'base';
}

return $columns;

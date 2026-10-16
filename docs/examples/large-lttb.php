<?php

use Phiz\Plot;

// A 100,000-point random walk, drawn from 800 points chosen by Largest-Triangle-Three-Buckets.
$rng = new Random\Randomizer(new Random\Engine\Mt19937(7));
$walk = [];
$level = 0.0;
for ($t = 0; $t < 100_000; $t++) {
    $level += $rng->getFloat(-1, 1);
    $walk[] = ['t' => $t, 'level' => $level];
}

return Plot::of($walk)
    ->aes(x: 't', y: 'level')
    ->line(downsample: 800, color: '#1F77B4')
    ->size(560, 220);

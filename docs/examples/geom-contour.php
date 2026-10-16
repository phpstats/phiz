<?php

use Phit\Plot;

// z on a regular grid of x and y.
$grid = [];
foreach (range(-3, 3, 0.2) as $x) {
    foreach (range(-3, 3, 0.2) as $y) {
        $grid[] = ['x' => $x, 'y' => $y, 'z' => sin($x) * cos($y) + 0.1 * $x * $y];
    }
}

return Plot::of($grid)
    ->aes(x: 'x', y: 'y', z: 'z')
    ->contourFilled(bins: 7)
    ->contour(bins: 7, color: 'white', linewidth: 0.5);

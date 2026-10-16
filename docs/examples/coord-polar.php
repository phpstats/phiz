<?php

use Phit\Compose;
use Phit\Datasets;
use Phit\Plot;

$cars = Plot::of(Datasets::mpg());

return Compose::beside(
    $cars->aes(fill: 'class')->bar()->coordPolar(theta: 'y')->title('Pie'),
    $cars->aes(x: 'class', fill: 'class')->bar()->coordPolar()->title('Rose'),
)->resolve(['scale' => ['theta' => 'independent', 'radius' => 'independent']]);

<?php

use Phit\Compose;
use Phit\Datasets;
use Phit\Plot;

$faithful = Datasets::faithful();

return Compose::beside(
    Plot::of($faithful)->aes(x: 'eruptions', y: 'waiting')->point(alpha: 0.5)->size(280, 240),
    Plot::of($faithful)->aes(x: 'waiting')->histogram(binwidth: 3)->coordFlip()->size(120, 240),
)->title('Old Faithful: eruption length and waiting time');

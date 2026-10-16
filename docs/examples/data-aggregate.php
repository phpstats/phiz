<?php

use Phiz\Datasets;
use Phiz\Plot;

return Plot::of(Datasets::economics())
    ->aes(x: 'year(date):O', y: 'mean(psavert)')
    ->col(fill: '#4C78A8')
    ->labs(x: null, y: 'Mean personal savings rate (%)');

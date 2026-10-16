<?php

use Phibble\Frame;
use Phit\Compose;
use Phit\Datasets;

require_once __DIR__.'/../../examples/dashboard.php';

// The dashboard with drive = 'f', year = 2008, and the compact class selected.
$charts = fuel_dashboard(Frame::fromRows(Datasets::mpg()), drive: 'f', year: 2008, selected: 'compact');

return Compose::beside($charts['bars'], $charts['scatter'], $charts['trend']);

<?php

use function Phiz\interval_plot;

// Draws as CmdStanPHP's $fit->draws_columns() returns them: variable => draws, plus .chain, .iteration, .draw.
$draws = json_decode(file_get_contents(__DIR__.'/fits/eight-schools-draws.json'), true);

return interval_plot($draws, variables: ['mu', 'tau', 'theta']);

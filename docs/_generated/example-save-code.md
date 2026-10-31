::: {.panel-tabset}

#### PHP 8.5+

```php
<?php

require 'vendor/autoload.php';

use Phiz\Datasets;

use function Phiz\{aes, geom_point, ggplot, labs};

$plot = ggplot(Datasets::mtcars(), aes(x: 'wt', y: 'mpg')) |>
    geom_point() |>
    labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('chart.html');            // no extra software needed
$plot->save('chart.svg');             // needs vl-convert
$plot->save('chart.png', scale: 2);   // needs vl-convert
```

#### PHP 8.3–8.4

```php
<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('chart.html');            // no extra software needed
$plot->save('chart.svg');             // needs vl-convert
$plot->save('chart.png', scale: 2);   // needs vl-convert
```

:::

::: {.panel-tabset}

#### PHP 8.5+

```php
<?php

require 'vendor/autoload.php';

use Phiz\Datasets;

use function Phiz\{aes, geom_point, geom_smooth, ggplot, labs};

$plot = ggplot(Datasets::mpg(), aes(x: 'displ', y: 'hwy')) |>
    geom_point(alpha: 0.5) |>
    geom_smooth(method: 'lm') |>
    labs(x: 'Engine displacement (L)', y: 'Highway miles per gallon');

$plot->save('layers.html');
```

#### PHP 8.3–8.4

```php
<?php

require 'vendor/autoload.php';

use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm')
    ->labs(x: 'Engine displacement (L)', y: 'Highway miles per gallon');

$plot->save('layers.html');
```

:::

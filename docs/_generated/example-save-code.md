::: {.panel-tabset}

#### PHP 8.5+

```php
use Phiz\Datasets;

use function Phiz\{aes, geom_point, ggplot, labs, size};

$mtcars = Datasets::mtcars();

$plot = ggplot($mtcars, aes(x: 'wt', y: 'mpg')) |>
    geom_point() |>
    labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon') |>
    size(640, 360);

// Drawn in the browser.
$plot->save('chart.html');

// Rendered by vl-convert.
$plot->save('chart.svg');
$plot->save('chart.png', scale: 2);
```

#### PHP 8.3–8.4

```php
use Phiz\Datasets;
use Phiz\Plot;

$mtcars = Datasets::mtcars();

$plot = Plot::of($mtcars)
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon')
    ->size(640, 360);

// Drawn in the browser.
$plot->save('chart.html');

// Rendered by vl-convert.
$plot->save('chart.svg');
$plot->save('chart.png', scale: 2);
```

:::

::: {.panel-tabset}

#### PHP 8.5+

```php
use Phiz\Datasets;

use function Phiz\{aes, facet_wrap, geom_point, ggplot, labs};

$plot = ggplot(Datasets::mtcars(), aes(x: 'wt', y: 'mpg')) |>
    geom_point() |>
    facet_wrap('cyl', ncol: 3) |>
    labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('facets.html');
```

#### PHP 8.3–8.4

```php
use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mtcars())
    ->aes(x: 'wt', y: 'mpg')
    ->point()
    ->facetWrap('cyl', ncol: 3)
    ->labs(x: 'Weight (1,000 lb)', y: 'Miles per gallon');

$plot->save('facets.html');
```

:::

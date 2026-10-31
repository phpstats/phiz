# phiz

A ggplot2-flavored grammar of graphics for PHP that emits [Vega-Lite](https://vega.github.io/vega-lite/).

phiz describes a chart as data, mappings from columns to visual properties, and layers of marks, as R's ggplot2 does. It compiles the description to a Vega-Lite specification. The browser draws the specification with vega-embed, and the [vl-convert](https://github.com/vega/vl-convert) binary renders it to SVG, PNG, or PDF.

PHP 8.5+:

```php
use Phiz\Datasets;

use function Phiz\{aes, geom_point, geom_smooth, ggplot, labs};

$plot = ggplot(Datasets::mpg(), aes(x: 'displ', y: 'hwy')) |>
    geom_point(alpha: 0.6) |>
    geom_smooth(method: 'lm') |>
    labs(x: 'Displacement (L)', y: 'Highway mpg');

$plot->save('chart.html');
```

PHP 8.3–8.4:

```php
use Phiz\Datasets;
use Phiz\Plot;

$plot = Plot::of(Datasets::mpg())
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.6)
    ->smooth(method: 'lm')
    ->labs(x: 'Displacement (L)', y: 'Highway mpg');

$plot->save('chart.html');
```

## Installation

```bash
composer require phpstats/phiz
```

phiz supports PHP 8.3, 8.4, and 8.5 (composer.json allows `>=8.3 <8.6`), and CI tests all three. It has no required dependencies beyond PHP. These are optional:

- the vl-convert binary, for SVG, PNG, and PDF export
- Laravel, for the `<x-phiz>` Blade component, whose service provider is discovered automatically
- phit, for predictions from formula fits in the statistics helpers

## Documentation

The documentation is at <https://phiz.phpstats.org/docs>:

- [Get started](https://phiz.phpstats.org/docs/get-started) builds and saves a first chart.
- [Coming from ggplot2](https://phiz.phpstats.org/docs/ggplot2) translates ggplot2 code and lists the differences.
- [Gallery](https://phiz.phpstats.org/docs/gallery) shows the 46 examples in `examples/catalog.php` with their code.
- [API reference](https://phiz.phpstats.org/docs/reference) lists every method and function with its signature.

## Development

```bash
composer test
composer lint
```

[Development](https://phiz.phpstats.org/docs/development) covers the test layers, the gallery, and how the documentation is rendered.

## License

MIT

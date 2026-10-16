# phiz

A ggplot2-flavoured grammar of graphics for PHP that emits [Vega-Lite](https://vega.github.io/vega-lite/).

phiz builds the spec in PHP. Vega-Lite handles the grammar, layout, scales, interaction and rendering, so you don't write any JavaScript. For static output (PDF reports, email), the [vl-convert](https://github.com/vega/vl-convert) binary turns the same spec into SVG, PNG or PDF.

```php
use Phiz\Plot;

$plot = Plot::of($rentals)
    ->aes(x: 'area_m2', y: 'rent', color: 'neighborhood')
    ->point(alpha: 0.6)
    ->smooth(method: 'lm')
    ->facetWrap('bedrooms', ncol: 3)
    ->scaleY(type: 'log')
    ->labs(title: 'Rent by floor area', x: 'Area (m²)', y: 'Monthly rent');

$plot->toJson();        // the Vega-Lite spec
$plot->html();          // a standalone page
$plot->svg();           // static SVG through vl-convert
```

The same plot also works with PHP 8.5's pipe operator:

```php
use function Phiz\{ggplot, aes, geom_point, geom_smooth, labs};

ggplot($mpg, aes(x: 'displ', y: 'hwy'))
    |> geom_point(alpha: 0.6)
    |> geom_smooth(method: 'lm')
    |> labs(x: 'Displacement (L)', y: 'Highway mpg');
```

## Installation

```bash
composer require erikwestlund/phiz
```

phiz needs PHP 8.5 and has no other dependencies. Static export needs the `vl-convert` binary on `PATH`, or its path in `PHIZ_VL_CONVERT`. Laravel support is optional and auto-discovered.

## Data

`Plot::of()` takes:

- a list of row arrays
- an array of equal-length columns
- a phibble table (anything with `toRows()` or `toColumns()`)
- a Laravel Collection
- any iterable of rows or objects
- `Data::url('cars.csv')`, which Vega-Lite fetches itself

Field types are inferred from the data: numbers are quantitative, ISO date strings are temporal, and everything else is nominal. To override the inferred type, use Altair-style shorthand (`'year:O'`, `'cyl:N'`, `'date:T'`) or `factor('cyl')`. Aggregates use the same shorthand: `'mean(hwy)'`, `'count()'`.

## Geoms

| Geom | Method | Notes |
| --- | --- | --- |
| points | `point()`, `jitter()` | `jitter:` displaces points within the band or in data units |
| lines | `line()`, `path()`, `step()` | `path` keeps data order; `step` takes a `direction` |
| areas | `area()`, `ribbon()` | `position:` stack, fill, identity |
| bars | `bar()`, `col()` | `bar` counts when y is unmapped; `position:` stack, dodge, fill, identity |
| distributions | `histogram()`, `freqpoly()`, `density()`, `boxplot()`, `ecdf()` | `density()` uses Vega-Lite's KDE; `adjust:` scales R's `bw.nrd0` |
| intervals | `errorbar()`, `linerange()`, `pointrange()` | `ymin`/`ymax`, or `xmin`/`xmax` for horizontal |
| reference lines | `hline()`, `vline()`, `abline()`, `rule()`, `segment()` | fixed values, mapped fields, or `expr()` parameters |
| text | `text()`, `label()` | ggplot2's `angle`, `hjust`, `vjust`, `nudgeX`, `nudgeY` |
| heatmaps | `tile()`, `raster()`, `rect()`, `bin2d()` | |
| fits | `smooth()` | `loess`, `lm`, `poly`, `quad`, `log`, `exp`, `pow`; see below |
| functions | `curve(fn, from, to)` | ggplot2's `geom_function` |
| QQ | `qq()`, `qqLine()` | maps the `sample` aesthetic |
| other | `rug()`, `arc()`/`pie()` | |
| raw | `layer([...])` | any Vega-Lite layer |

For `smooth(method: 'lm')` (and `quad` and `poly`) with inline data, phiz fits the model in PHP and draws the confidence band (`se`, `level`), as ggplot2 does. Otherwise Vega-Lite's regression or loess transform fits the curve in the browser.

Each geom accepts:

- its own mappings (`aes:`) and data (`data:`)
- `inheritAes: false`, so the layer ignores the plot's mappings
- `filter:`, a Vega expression or a selection
- fixed styles, by ggplot2 name (`alpha`, `color`, `fill`, `size`, `linewidth`, `linetype`, `shape`) or by any Vega-Lite mark property (`strokeWidth: 2`)

As in ggplot2, `color` is the outline of bars and areas and `fill` is their interior.

## Scales, coordinates, facets, labels, themes

```php
$plot->scaleX(type: 'log', breaks: [1, 10, 100], name: 'Dose')
    ->scaleColor(scheme: 'viridis')
    ->scaleFill(values: ['a' => '#E41A1C', 'b' => '#377EB8'])
    ->coordFlip()
    ->coordCartesian(ylim: [0, 50])
    ->facetGrid(rows: 'drv', cols: 'cyl', scales: 'free_y')
    ->labs(title: '...', subtitle: '...', x: '...', color: '...')
    ->theme('minimal', baseSize: 13, legendPosition: 'bottom')
    ->size(480, 320);
```

The theme presets are `gray`, `bw`, `minimal`, `classic`, `light`, `dark`, `void` and `vega`. Each preset is inlined into the spec as Vega-Lite config, so HTML and static output look the same.

## Interaction

```php
$plot->tooltip();                                   // tooltips on every layer
$plot->interactive();                               // pan and zoom
$plot->brush();                                     // drag to select; unselected points turn gray
$plot->highlight('color');                          // click legend entries
$plot->slider('cutoff', 0, 10)->hline(expr('cutoff'));
```

To link two plots, compose them and filter one plot by the other's selection:

```php
use Phiz\Compose;

Compose::stack(
    Plot::of($cars)->aes(x: 'hp', y: 'mpg')->point()->brush(),
    Plot::of($cars)->aes(x: 'cyl:N')->bar(filter: ['param' => 'brush']),
);
```

`Compose::beside()`, `Compose::stack()` and `Compose::grid()` lay plots out like patchwork does.

## Escape hatches

- `toArray()` and `toJson()` return the raw spec.
- `raw([...])` deep-merges arbitrary Vega-Lite into the top level.
- `transform(...)` adds Vega-Lite transforms.
- `layer([...])` adds a raw layer.
- `param()` and `select()` add raw parameters.

## Rendering

- **HTML:** `$plot->embed()` returns a container plus script tags, and `$plot->html()` returns a full page. Both use vega 6.4.0, vega-lite 6.4.3 and vega-embed 7.3.0, pinned and loaded from jsDelivr with subresource integrity. For other sources, pass `Assets::local('/js')` or `Assets::inline($dir)` for self-hosted copies, or `Assets::none()` when your bundle already provides `window.vegaEmbed`.
- **Static:** `$plot->svg()`, `->png(scale: 2)`, `->pdf()` and `->save('plot.pdf')` run vl-convert, which renders with Vega-Lite 6.1. If the binary is missing, phiz throws `VlConvertNotFound` with install instructions.

## Laravel and Livewire

```blade
<x-phiz :plot="$plot" class="h-80" :options="['theme' => 'auto']" />
```

The component adds the script tags once per page. To place them yourself, use `@phizScripts`. Inside a Livewire component, the chart re-renders whenever the component does:

- The spec is stored in an attribute that Livewire morphs.
- The drawn chart sits in a `wire:ignore` child.
- phiz's small loader script re-embeds the chart when the spec changes.

If a component renders several charts, give each one a `wire:key`. To change the asset source and the default embed options, publish the config with `php artisan vendor:publish --tag=phiz-config`.

## Testing

```bash
composer test
```

The tests snapshot the spec for every geom and feature and validate each one against the pinned Vega-Lite 6.1.0 JSON schema. When vl-convert is available, they also render every example to SVG.

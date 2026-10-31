# Plan: CmdStanPHP figures on phiz (2026-10-16)

This note was a page of the phiz documentation until 2026-10-31. It proposes how CmdStanPHP's figures can use phiz. It is written as an issue for CmdStanPHP and changes nothing there. It builds on CmdStanPHP's planning note, `docs/notes/phiz-readiness.md`. Whether and when to proceed is the CmdStanPHP maintainer's decision.

## Background

`CmdStan\Plot` computes each figure's statistics in PHP and returns a `CmdStan\Figure`: a kind, a title, data rows, and metadata. `Figure` renders the rows as ASCII text, as a Vega-Lite 5 spec, as an HTML page, and as SVG or PNG through vl-convert. The R comparisons check the data rows, so they stay valid as long as the rows do not change.

phiz now provides what the readiness note lists as prerequisites:

| Prerequisite | phiz |
| --- | --- |
| Inline, precomputed data with identity stats | every geom accepts rows; `col()`, `line()`, `point()`, and `rule()` draw values as given |
| Bars with explicit bin edges | `col(aes(xmin: 'x0', xmax: 'x1', y: 'count'))` |
| Rules at constant values | `hline()`, `vline()`, and mapped `yintercept` |
| Points with conditional color | a nominal color mapping with a fixed domain and range |
| Row facets with shared y scales and header labels | `facetGrid(rows: 'chain', labeller: 'both')` or `header: ['labelExpr' => ...]` |
| Spec, JSON, HTML, SVG, and PNG with pinned versions | `toArray()`, `toJson()`, `html()`, `svg()`, `png()`; vega 6.4.0, vega-lite 6.4.3, vega-embed 7.3.0, vl-convert with Vega-Lite 6.1 |

## Proposed steps

1. **Optional `Figure::toPhiz()`.** Add a method that returns a `Phiz\Chart` when phiz is installed, and throws a clear error otherwise. Detect phiz with `class_exists(\Phiz\Plot::class)`, as `to_frame()` detects phibble. Keep `vegaLite()`, `html()`, `svg()`, and `png()` unchanged for PHP 8.2 users.
2. **Tests.** For each figure kind, assert that `toPhiz()` produces a spec that validates against the Vega-Lite schema and renders with vl-convert. Compare its data values with `Figure::$data`. Exact spec equality with `VegaLite::spec()` is not a goal, because phiz targets Vega-Lite 6 and applies its own defaults.
3. **Move shared rendering.** When CmdStanPHP requires PHP 8.3, replace `Figure::html()` and the vl-convert code in `Figure::convert()` with phiz's `Html` and `Export`. The binary discovery already honors the `VL_CONVERT` variable.
4. **Rebuild the kinds.** Replace `VegaLite::spec()` with the `toPhiz()` translations and keep the `Figure` data contract and the R comparisons.

## Translations

Each kind maps to a short phiz plot over the existing `Figure::$data` rows. These translations were compiled and rendered with vl-convert, using rows shaped like each kind's data.

```php
use Phiz\Plot;
use function Phiz\{aes, factor};

$rows = $figure->data;
$m = $figure->meta;

$plot = match ($figure->kind) {
    'trace' => Plot::of($rows)
        ->aes(x: 'iteration', y: 'value', color: factor('chain'))
        ->line(linewidth: 0.8, alpha: 0.8)
        ->labs(y: $m['variable'], color: 'chain'),

    'hist' => Plot::of($rows)
        ->col(aes(xmin: 'x0', xmax: 'x1', y: 'count'))
        ->labs(x: $m['variable']),

    'dens' => Plot::of($rows)
        ->aes(x: 'x', y: 'density', color: $m['by_chain'] ? factor('chain') : null)
        ->line(linewidth: 1.5)
        ->labs(x: $m['variable']),

    'intervals' => Plot::of($rows)
        ->aes(y: \Phiz\field('variable', 'nominal', sort: array_column($rows, 'variable')))
        ->rule(aes(x: 'll', xend: 'hh'), linewidth: 1.5)
        ->rule(aes(x: 'l', xend: 'h'), linewidth: 5)
        ->point(aes(x: 'm'), size: 70, fill: '#FFFFFF', stroke: '#000000')
        ->labs(x: 'value', y: null),

    'rank_hist' => Plot::of($rows)
        ->col(aes(xmin: 'x0', xmax: 'x1', y: 'count'))
        ->hline(aes: aes(yintercept: 'mean(expected)'), linetype: 'dashed')
        ->facetGrid(rows: 'chain', labeller: 'both')
        ->labs(x: 'rank', y: 'count'),

    'scatter' => Plot::of($rows)
        ->aes(x: 'x', y: 'y', color: 'divergent:N')
        ->point(alpha: 0.4)
        ->scaleColor(domain: [false, true], range: ['#4C78A8', '#D62728'], guide: false)
        ->labs(x: $m['x_variable'], y: $m['y_variable']),

    'acf' => Plot::of($rows)
        ->aes(x: 'lag:O', y: 'acf')
        ->col()
        ->hline(0)
        ->facetGrid(rows: 'chain', labeller: 'both')
        ->labs(y: 'autocorrelation'),

    'pareto_k' => Plot::of($rows)
        ->aes(x: 'observation', y: 'k')
        ->hline($m['threshold'], linetype: 'dashed', color: 'gray')
        ->hline(1, color: '#D62728')
        ->point()
        ->labs(y: 'Pareto k'),
};

return $plot->title($figure->title);
```

The `intervals` kind uses the same column names (`ll`, `l`, `m`, `h`, `hh`) as phiz's `interval_plot()`, which computes the rows itself from `draws_columns()` output. A CmdStanPHP user can call `interval_plot($fit->draws_columns())` directly.

## Open questions

- **PHP floor.** phiz requires PHP 8.3. Until CmdStanPHP raises its floor from 8.2 to 8.3, phiz stays an optional dependency, and steps 3 and 4 wait.
- **Thinning.** `VegaLite::thin()` keeps every k-th draw and every divergent draw. It belongs in CmdStanPHP as a row filter applied before `toPhiz()`.
- **ASCII output.** phiz has no text backend. `AsciiChart` stays in CmdStanPHP.

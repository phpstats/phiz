# Changelog

## v0.6.3

- `Contour::pretty()`, behind contour levels and binned-axis ticks, and the multivariate t fit behind `stat_ellipse(type: 't')` are rewritten from R's documentation, Kent, Tyler & Vardi (1994), MASS's documented defaults, and observed output. Results are unchanged: they match R's `pretty()` on 1,512 random and edge-case ranges and ggplot2's ellipses on three datasets.
- THIRD_PARTY_NOTICES.md lists the projects whose behavior parts of phiz follow, the sources of independently written routines, and the bundled datasets' licenses.

## v0.6.2

### Statistics

- The statistics helpers accept phit's fit objects (`Phit\Fit`, from phit 0.6) as well as fit arrays: `coefplot()`, including a labelled list of fits, `residual_plot()`, `qq_plot()`, `fitted_plot()`, `scale_location_plot()`, `diagnostic_plots()`, `effect_plot()`, `predictions()`, `marginal_means()`, and `random_effects_plot()`. An object converts with its `toArray()`; any other object throws an `InvalidArgumentException`.

### Documentation

- Plain headings (Features, Installation, and noun labels), and the README states the MIT license with a link and the bundled datasets' sources.

### Development

- Developed against phibble 0.5 to 0.8.

## v0.6.1

### Charts

- **Text sizes.** `theme(baseSize:)` is in points, as ggplot2's `base_size` is, converted at 96 dpi: the default of 11 gives axis labels of 11.7 pixels and titles of 14.7. Charts without a theme have 11-pixel labels and 12-pixel titles, in place of Vega-Lite's 10 and 11.
- **Discrete labels.** Labels on a discrete x axis stay horizontal unless they would collide. With a container width, the browser decides from the chart's width. Flipped plots and angles set through a theme or scale are left alone.
- **Ticks.** Binned axes get R's `pretty()` breaks in place of a tick at every bin, and date axes about six ticks.
- **Tooltips.** Rules, such as reference lines and interval bars, have no tooltip. A layer shows only the plot's tooltip fields it has. Numbers are formatted to four significant digits, and `coefplot()` lists standard errors and p-values only when the fit reports them.
- **Dark mode.**
  - The gray theme's panel is a faint lift, with grid lines like the other themes'.
  - Category colors are lighter.
  - Sequential schemes lose their darkest end.
  - Faint points are more opaque.
  - Boxplot whiskers, the brush rectangle, crossbars, ellipses, intervals, and `interval_plot()` and `effect_plot()` lines take light colors.
- **Styles.** Interval, outline, fill, and inner-interval colors come from named styles in the config (`phiz-interval`, `phiz-outline`, `phiz-fill`, and `phiz-inner`), and errorbars from `config.errorbar`. Light colors are unchanged.

### Documentation

- The violin and boxplot examples use theme colors with visible points, the landing example's output is regenerated, and the project is named PHPstats in prose.

## v0.6.0

### Charts

- **Dark mode.** Charts follow a page's dark mode: `<html class="dark">`, or the vega-embed option `['theme' => 'auto']` with a dark system color scheme. Each top-level specification carries a dark variant of its theme in `usermeta.phiz.dark`: a transparent background, muted grid lines, light text, and lighter default marks, fitted lines, and bands. The loader merges it over the spec's config on dark pages, uses dark tooltips, and redraws charts when the page switches. Colors set on a layer or scale are kept, and `theme('dark')` charts are unchanged.
- **Ticks.** Continuous axes ask for about five ticks (`config.axisQuantitative.tickCount`), as R's `pretty()` gives, instead of Vega-Lite's one tick per 40 pixels.
- **Tooltips on derived layers.** A fitted line or band, a summary, a density, or any layer whose rows are computed or aggregated no longer inherits the plot's tooltip fields, which do not exist there. It shows its own x, y, and band bounds to four significant digits.
- **Fit and band colors.** `smooth()` lines, bands, and density contours take their colors from the named styles `phiz-fit` and `phiz-band` in the config, so themes can recolor them. Light colors are unchanged.
- Every specification now has a `config` with these defaults.

### Statistics

- The statistics helpers' docblocks, errors, and documentation use phit 0.4's function names: `fit_ols`, `fit_generalized_linear`, `fit_mixed_gaussian`, `fit_mixed_logistic`, `fit_ols_matrix`, and `fit_logistic_matrix`. Fits from phit 0.3's names have the same shape and still work.

### Documentation

- A Functions and methods index lists every function and method with its purpose and a link, and each reference page lists its entries in its table of contents. Thin reference entries have descriptions.
- Benchmark and verification records in `site/`: agreement with R (with an R script that regenerates the references), Vega-Lite schema validity, snapshots, pipe and method-chain equivalence, vl-convert export, and benchmarks of large-data binning, small plots, and static export.
- Every example uses real data, and the guides are shorter, with tables for options and conversions.

### Development

- Developed against phit-polyfill 0.4 and phibble 0.5 to 0.7. Runtime requirements are unchanged: PHP 8.3 to 8.5 and the json extension.

Earlier releases: <https://github.com/phpstats/phiz/releases>.

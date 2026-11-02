# Changelog

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

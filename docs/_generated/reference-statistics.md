**Functions:** [`coefplot()`](#coefplot-function) · [`residual_plot()`](#residual_plot) · [`qq_plot()`](#qq_plot) · [`fitted_plot()`](#fitted_plot) · [`scale_location_plot()`](#scale_location_plot) · [`diagnostic_plots()`](#diagnostic_plots) · [`effect_plot()`](#effect_plot) · [`predictions()`](#predictions-function) · [`interval_plot()`](#interval_plot) · [`marginal_means()`](#marginal_means) · [`random_effects_plot()`](#random_effects_plot)

### `coefplot()` {#coefplot-function}

```php
coefplot(array $fits, mixed ...$options): Plot
```

Coefficient plot of one fit, or of several keyed by label (dodged side by side).

### `residual_plot()` {#residual_plot}

```php
residual_plot(array $fit): Plot
```

### `qq_plot()` {#qq_plot}

```php
qq_plot(array $fitOrValues, bool $standardize = false): Plot
```

### `fitted_plot()` {#fitted_plot}

```php
fitted_plot(array $fit, ?array $observed = null): Plot
```

### `scale_location_plot()` {#scale_location_plot}

```php
scale_location_plot(array $fit): Plot
```

### `diagnostic_plots()` {#diagnostic_plots}

```php
diagnostic_plots(array $fit, mixed ...$options): Compose
```

### `effect_plot()` {#effect_plot}

```php
effect_plot(array $fit, mixed $data, string $x, mixed ...$options): Plot
```

Predicted values with confidence bands as x varies (by levels of $by), other variables held at typical values.

### `predictions()` {#predictions-function}

```php
predictions(array $fit, array $grid, mixed ...$options): array
```

### `interval_plot()` {#interval_plot}

```php
interval_plot(array $draws, mixed ...$options): Plot
```

Posterior intervals from draws columns or a summary, as bayesplot's mcmc_intervals.

### `marginal_means()` {#marginal_means}

```php
marginal_means(array $fit, mixed $data, string $factor, mixed ...$options): Plot
```

Estimated marginal means of a factor with confidence intervals, as emmeans.

### `random_effects_plot()` {#random_effects_plot}

```php
random_effects_plot(array $fit, mixed $data = null, mixed ...$options): Plot
```

A caterpillar plot of a mixed model's random effects with conditional intervals, as lme4's dotplot(ranef()).


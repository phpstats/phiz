**Coordinates and facets:** [`coordFlip()`](#coord-flip) · [`coordPolar()`](#coord-polar) · [`coordFixed()`](#coord-fixed) · [`coordCartesian()`](#coord-cartesian) · [`facetWrap()`](#facet-wrap) · [`facetGrid()`](#facet-grid)

**Compose:** [`Compose::beside()`](#compose-beside) · [`Compose::stack()`](#compose-stack) · [`Compose::grid()`](#compose-grid) · [`$compose->title()`](#compose-title) · [`$compose->theme()`](#compose-theme) · [`$compose->resolve()`](#compose-resolve) · [`$compose->raw()`](#compose-raw) · [`$compose->toArray()`](#compose-to-array) · [`$compose->toSpec()`](#compose-to-spec)

**Functions:** [`coord_flip()`](#coord_flip) · [`coord_polar()`](#coord_polar) · [`coord_fixed()`](#coord_fixed) · [`coord_equal()`](#coord_equal) · [`coord_cartesian()`](#coord_cartesian) · [`facet_wrap()`](#facet_wrap) · [`facet_grid()`](#facet_grid)

## Coordinates and facets

### `coordFlip()` {#coord-flip}

```php
->coordFlip(bool $flip = true): Plot
```

Swaps the x and y channels, so mappings stay as written while the plot turns sideways.

Function: [`coord_flip()`](#coord_flip).

### `coordPolar()` {#coord-polar}

```php
->coordPolar(
    string $theta = 'x',
    float $start = 0.0,
    int $direction = 1,
    int|float $innerRadius = 0,
): Plot
```

Polar coordinates for bar() and col() layers, as ggplot2's coord_polar. theta: 'y' turns stacked bars into a pie (map x to a constant, or leave it out; innerRadius in pixels makes a donut), and 'x' turns bars over a discrete x into a rose of equal angles with radius y. start rotates the first slice clockwise from twelve o'clock, in radians; direction -1 runs anticlockwise.

Function: [`coord_polar()`]{#coord_polar}.

### `coordFixed()` {#coord-fixed}

```php
->coordFixed(int|float $ratio = 1): Plot
```

A fixed aspect ratio, as ggplot2's coord_fixed: one unit of y takes ratio times the length of one unit of x. The panel's height follows from the width (300 pixels unless set) and the data ranges, which are kept exact.

Functions: [`coord_fixed()`](#coord_fixed), [`coord_equal()`](#coord_equal).

### `coordCartesian()` {#coord-cartesian}

```php
->coordCartesian(?array $xlim = null, ?array $ylim = null): Plot
```

Zooms to fixed domains, clipping marks outside them.

Function: [`coord_cartesian()`]{#coord_cartesian}.

### `facetWrap()` {#facet-wrap}

```php
->facetWrap(
    array|string $facets,
    ?int $ncol = null,
    ?int $nrow = null,
    string $scales = 'fixed',
    array|string $labeller = 'value',
    array $header = [],
): Plot
```

Small multiples wrapped into rows. scales: fixed, free, free_x, free_y. With several fields the panels are their combinations. labeller: 'both' labels panels "field: value", as ggplot2's label_both.

Function: [`facet_wrap()`]{#facet_wrap}.

### `facetGrid()` {#facet-grid}

```php
->facetGrid(
    ?string $rows = null,
    ?string $cols = null,
    string $scales = 'fixed',
    array|string $labeller = 'value',
    array $header = [],
): Plot
```

A grid of panels by rows and/or cols fields.

Function: [`facet_grid()`]{#facet_grid}.

## Compose {#class-compose}

### `Compose::beside()` {#compose-beside}

```php
Compose::beside(Chart ...$charts): Compose
```

Charts side by side in one row, as patchwork's p1 + p2 (a Vega-Lite hconcat).

### `Compose::stack()` {#compose-stack}

```php
Compose::stack(Chart ...$charts): Compose
```

Charts stacked in one column, as patchwork's p1 / p2 (a Vega-Lite vconcat).

### `Compose::grid()` {#compose-grid}

```php
Compose::grid(array $charts, int $columns = 2): Compose
```

A grid filled row by row with the given number of columns.

### `$compose->title()` {#compose-title}

```php
->title(string $title, ?string $subtitle = null): Compose
```

A title and subtitle over the whole composition.

### `$compose->theme()` {#compose-theme}

```php
->theme(?string $preset = null, mixed ...$options): Compose
```

A theme preset and options for the whole composition, as Plot::theme().

### `$compose->resolve()` {#compose-resolve}

```php
->resolve(array $resolve): Compose
```

Shares or separates scales, axes, and legends across the plots, e.g. ['scale' => ['color' => 'independent']].

### `$compose->raw()` {#compose-raw}

```php
->raw(array $spec): Compose
```

Deep-merges raw Vega-Lite into the composition's top level, as Plot::raw() does.

### `$compose->toArray()` {#compose-to-array}

```php
->toArray(): array
```

The composition's Vega-Lite specification.

### `$compose->toSpec()` {#compose-to-spec}

```php
->toSpec(): array
```

## Functions

### `coord_flip()` {#coord_flip}

```php
coord_flip(): Closure
```

The pipe form of [`->coordFlip()`](#coord-flip).

### `coord_fixed()` {#coord_fixed}

```php
coord_fixed(int|float $ratio = 1): Closure
```

The pipe form of [`->coordFixed()`](#coord-fixed).

### `coord_equal()` {#coord_equal}

```php
coord_equal(int|float $ratio = 1): Closure
```

The pipe form of [`->coordFixed()`](#coord-fixed).


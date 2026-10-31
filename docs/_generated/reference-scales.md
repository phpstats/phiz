**Methods:** [`scale()`](#scale) · [`scaleX()`](#scale-x) · [`scaleY()`](#scale-y) · [`scaleColor()`](#scale-color) · [`scaleFill()`](#scale-fill) · [`scaleSize()`](#scale-size) · [`scaleShape()`](#scale-shape) · [`scaleOpacity()`](#scale-opacity) · [`scaleLinetype()`](#scale-linetype) · [`guides()`](#guides) · [`xlim()`](#xlim) · [`ylim()`](#ylim)

**Functions:** [`scale_x()`](#scale_x) · [`scale_y()`](#scale_y) · [`scale_x_continuous()`](#scale_x_continuous) · [`scale_y_continuous()`](#scale_y_continuous) · [`scale_x_log10()`](#scale_x_log10) · [`scale_y_log10()`](#scale_y_log10) · [`scale_x_sqrt()`](#scale_x_sqrt) · [`scale_y_sqrt()`](#scale_y_sqrt) · [`scale_x_reverse()`](#scale_x_reverse) · [`scale_y_reverse()`](#scale_y_reverse) · [`scale_x_date()`](#scale_x_date) · [`scale_y_date()`](#scale_y_date) · [`scale_color()`](#scale_color) · [`scale_fill()`](#scale_fill) · [`scale_size()`](#scale_size) · [`scale_shape()`](#scale_shape) · [`scale_alpha()`](#scale_alpha) · [`scale_linetype()`](#scale_linetype) · [`scale_color_manual()`](#scale_color_manual) · [`scale_fill_manual()`](#scale_fill_manual) · [`scale_color_scheme()`](#scale_color_scheme) · [`scale_fill_scheme()`](#scale_fill_scheme) · [`scale_color_viridis()`](#scale_color_viridis) · [`scale_fill_viridis()`](#scale_fill_viridis) · [`scale_color_brewer()`](#scale_color_brewer) · [`scale_fill_brewer()`](#scale_fill_brewer) · [`scale_color_gradient()`](#scale_color_gradient) · [`scale_fill_gradient()`](#scale_fill_gradient) · [`scale_color_gradient2()`](#scale_color_gradient2) · [`scale_fill_gradient2()`](#scale_fill_gradient2) · [`viridis_scheme()`](#viridis_scheme) · [`brewer_scheme()`](#brewer_scheme) · [`scale_color_viridis_d()`](#scale_color_viridis_d) · [`scale_fill_viridis_d()`](#scale_fill_viridis_d) · [`scale_color_viridis_c()`](#scale_color_viridis_c) · [`scale_fill_viridis_c()`](#scale_fill_viridis_c) · [`scale_color_viridis_b()`](#scale_color_viridis_b) · [`scale_fill_viridis_b()`](#scale_fill_viridis_b) · [`scale_color_distiller()`](#scale_color_distiller) · [`scale_fill_distiller()`](#scale_fill_distiller) · [`scale_color_fermenter()`](#scale_color_fermenter) · [`scale_fill_fermenter()`](#scale_fill_fermenter) · [`scale_color_gradientn()`](#scale_color_gradientn) · [`scale_fill_gradientn()`](#scale_fill_gradientn) · [`scale_color_grey()`](#scale_color_grey) · [`scale_fill_grey()`](#scale_fill_grey) · [`scale_color_hue()`](#scale_color_hue) · [`scale_fill_hue()`](#scale_fill_hue) · [`scale_color_identity()`](#scale_color_identity) · [`scale_fill_identity()`](#scale_fill_identity) · [`scale_x_discrete()`](#scale_x_discrete) · [`scale_y_discrete()`](#scale_y_discrete) · [`guides()`](#guides-function) · [`guide_legend()`](#guide_legend) · [`guide_colorbar()`](#guide_colorbar) · [`guide_colourbar()`](#guide_colourbar) · [`guide_axis()`](#guide_axis) · [`guide_none()`](#guide_none) · [`label_number()`](#label_number) · [`label_comma()`](#label_comma) · [`label_percent()`](#label_percent) · [`label_dollar()`](#label_dollar) · [`label_scientific()`](#label_scientific) · [`label_date()`](#label_date) · [`xlim()`](#xlim-function) · [`ylim()`](#ylim-function)

### `scale()` {#scale}

```php
->scale(string $aesthetic, mixed ...$options): Plot
```

Sets the scale for an aesthetic; see Scale::make() for the options.

### `scaleX()` {#scale-x}

```php
->scaleX(mixed ...$options): Plot
```

x scale: type (linear, log, sqrt, time, ...), domain, range, zero, nice, reverse, name, breaks, labels, format.

Functions: [`scale_x()`]{#scale_x}, [`scale_x_continuous()`]{#scale_x_continuous}, [`scale_x_log10()`]{#scale_x_log10}, [`scale_x_sqrt()`]{#scale_x_sqrt}, [`scale_x_reverse()`]{#scale_x_reverse}, [`scale_x_date()`]{#scale_x_date}, [`scale_x_discrete()`](#scale_x_discrete).

### `scaleY()` {#scale-y}

```php
->scaleY(mixed ...$options): Plot
```

Functions: [`scale_y()`]{#scale_y}, [`scale_y_continuous()`]{#scale_y_continuous}, [`scale_y_log10()`]{#scale_y_log10}, [`scale_y_sqrt()`]{#scale_y_sqrt}, [`scale_y_reverse()`]{#scale_y_reverse}, [`scale_y_date()`]{#scale_y_date}, [`scale_y_discrete()`](#scale_y_discrete).

### `scaleColor()` {#scale-color}

```php
->scaleColor(mixed ...$options): Plot
```

Color scale: scheme ('viridis', 'blues', 'category10', ...), range, values (manual palette), domainMid, ...

Functions: [`scale_color()`]{#scale_color}, [`scale_color_manual()`](#scale_color_manual), [`scale_color_scheme()`](#scale_color_scheme), [`scale_color_viridis()`](#scale_color_viridis), [`scale_color_brewer()`](#scale_color_brewer), [`scale_color_gradient()`](#scale_color_gradient), [`scale_color_gradient2()`](#scale_color_gradient2), [`scale_color_viridis_d()`](#scale_color_viridis_d), [`scale_color_viridis_c()`](#scale_color_viridis_c), [`scale_color_viridis_b()`](#scale_color_viridis_b), [`scale_color_distiller()`](#scale_color_distiller), [`scale_color_fermenter()`](#scale_color_fermenter), [`scale_color_gradientn()`](#scale_color_gradientn), [`scale_color_grey()`](#scale_color_grey), [`scale_color_hue()`](#scale_color_hue), [`scale_color_identity()`](#scale_color_identity).

### `scaleFill()` {#scale-fill}

```php
->scaleFill(mixed ...$options): Plot
```

Functions: [`scale_fill()`]{#scale_fill}, [`scale_fill_manual()`](#scale_fill_manual), [`scale_fill_scheme()`](#scale_fill_scheme), [`scale_fill_viridis()`](#scale_fill_viridis), [`scale_fill_brewer()`](#scale_fill_brewer), [`scale_fill_gradient()`](#scale_fill_gradient), [`scale_fill_gradient2()`](#scale_fill_gradient2), [`scale_fill_viridis_d()`](#scale_fill_viridis_d), [`scale_fill_viridis_c()`](#scale_fill_viridis_c), [`scale_fill_viridis_b()`](#scale_fill_viridis_b), [`scale_fill_distiller()`](#scale_fill_distiller), [`scale_fill_fermenter()`](#scale_fill_fermenter), [`scale_fill_gradientn()`](#scale_fill_gradientn), [`scale_fill_grey()`](#scale_fill_grey), [`scale_fill_hue()`](#scale_fill_hue), [`scale_fill_identity()`]{#scale_fill_identity}.

### `scaleSize()` {#scale-size}

```php
->scaleSize(mixed ...$options): Plot
```

Function: [`scale_size()`]{#scale_size}.

### `scaleShape()` {#scale-shape}

```php
->scaleShape(mixed ...$options): Plot
```

Function: [`scale_shape()`]{#scale_shape}.

### `scaleOpacity()` {#scale-opacity}

```php
->scaleOpacity(mixed ...$options): Plot
```

Function: [`scale_alpha()`]{#scale_alpha}.

### `scaleLinetype()` {#scale-linetype}

```php
->scaleLinetype(mixed ...$options): Plot
```

Function: [`scale_linetype()`]{#scale_linetype}.

### `guides()` {#guides}

```php
->guides(mixed ...$guides): Plot
```

Guides per aesthetic, as ggplot2's guides(): 'none' or false hides one, and an array sets axis or legend properties (see guide_legend(), guide_colorbar(), and guide_axis()).

Function: [`guides()`](#guides-function).

### `xlim()` {#xlim}

```php
->xlim(string|int|float $min, string|int|float $max): Plot
```

Fixes the x domain. Unlike ggplot2's xlim(), data outside it is clipped rather than dropped.

Function: [`xlim()`](#xlim-function).

### `ylim()` {#ylim}

```php
->ylim(string|int|float $min, string|int|float $max): Plot
```

Function: [`ylim()`](#ylim-function).

## Functions

### `scale_color_manual()` {#scale_color_manual}

```php
scale_color_manual(array $values, mixed ...$args): Closure
```

Manual colors: a level => color map, or colors in level order.

### `scale_fill_manual()` {#scale_fill_manual}

```php
scale_fill_manual(array $values, mixed ...$args): Closure
```

### `scale_color_scheme()` {#scale_color_scheme}

```php
scale_color_scheme(string $scheme, mixed ...$args): Closure
```

Vega color schemes by name, such as 'viridis', 'blues', 'set2', 'dark2', 'tableau10'.

### `scale_fill_scheme()` {#scale_fill_scheme}

```php
scale_fill_scheme(string $scheme, mixed ...$args): Closure
```

### `scale_color_viridis()` {#scale_color_viridis}

```php
scale_color_viridis(string $option = 'viridis', mixed ...$args): Closure
```

### `scale_fill_viridis()` {#scale_fill_viridis}

```php
scale_fill_viridis(string $option = 'viridis', mixed ...$args): Closure
```

### `scale_color_brewer()` {#scale_color_brewer}

```php
scale_color_brewer(string $palette = 'set1', mixed ...$args): Closure
```

### `scale_fill_brewer()` {#scale_fill_brewer}

```php
scale_fill_brewer(string $palette = 'set1', mixed ...$args): Closure
```

### `scale_color_gradient()` {#scale_color_gradient}

```php
scale_color_gradient(
    string $low = '#132B43',
    string $high = '#56B1F7',
    mixed ...$args,
): Closure
```

A two-color gradient, as ggplot2's scale_colour_gradient (defaults #132B43 to #56B1F7).

### `scale_fill_gradient()` {#scale_fill_gradient}

```php
scale_fill_gradient(
    string $low = '#132B43',
    string $high = '#56B1F7',
    mixed ...$args,
): Closure
```

### `scale_color_gradient2()` {#scale_color_gradient2}

```php
scale_color_gradient2(
    string $low = '#832424',
    string $mid = '#FFFFFF',
    string $high = '#3A3A98',
    int|float $midpoint = 0,
    mixed ...$args,
): Closure
```

A diverging gradient through mid at midpoint, as ggplot2's scale_colour_gradient2.

### `scale_fill_gradient2()` {#scale_fill_gradient2}

```php
scale_fill_gradient2(
    string $low = '#832424',
    string $mid = '#FFFFFF',
    string $high = '#3A3A98',
    int|float $midpoint = 0,
    mixed ...$args,
): Closure
```

### `viridis_scheme()` {#viridis_scheme}

```php
viridis_scheme(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
): array
```

viridisLite palettes by option: A magma, B inferno, C plasma, D viridis, E cividis, H turbo (or the names).

### `brewer_scheme()` {#brewer_scheme}

```php
brewer_scheme(string $palette, int $direction = 1): array|string
```

A ColorBrewer scheme, reversed when direction is -1.

### `scale_color_viridis_d()` {#scale_color_viridis_d}

```php
scale_color_viridis_d(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

viridis for discrete (_d), continuous (_c), and binned (_b) scales, as ggplot2's scale_colour_viridis_*.

### `scale_fill_viridis_d()` {#scale_fill_viridis_d}

```php
scale_fill_viridis_d(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

### `scale_color_viridis_c()` {#scale_color_viridis_c}

```php
scale_color_viridis_c(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

### `scale_fill_viridis_c()` {#scale_fill_viridis_c}

```php
scale_fill_viridis_c(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

### `scale_color_viridis_b()` {#scale_color_viridis_b}

```php
scale_color_viridis_b(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

### `scale_fill_viridis_b()` {#scale_fill_viridis_b}

```php
scale_fill_viridis_b(
    string $option = 'D',
    float $begin = 0.0,
    float $end = 1.0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

### `scale_color_distiller()` {#scale_color_distiller}

```php
scale_color_distiller(
    string $palette = 'Blues',
    int $direction = -1,
    mixed ...$args,
): Closure
```

ColorBrewer for continuous data, as ggplot2's scale_colour_distiller (reversed by default, as there).

### `scale_fill_distiller()` {#scale_fill_distiller}

```php
scale_fill_distiller(
    string $palette = 'Blues',
    int $direction = -1,
    mixed ...$args,
): Closure
```

### `scale_color_fermenter()` {#scale_color_fermenter}

```php
scale_color_fermenter(
    string $palette = 'Blues',
    int $direction = -1,
    mixed ...$args,
): Closure
```

ColorBrewer for binned continuous data, as ggplot2's scale_colour_fermenter.

### `scale_fill_fermenter()` {#scale_fill_fermenter}

```php
scale_fill_fermenter(
    string $palette = 'Blues',
    int $direction = -1,
    mixed ...$args,
): Closure
```

### `scale_color_gradientn()` {#scale_color_gradientn}

```php
scale_color_gradientn(array $colors, mixed ...$args): Closure
```

A gradient through several colors, as ggplot2's scale_colour_gradientn.

### `scale_fill_gradientn()` {#scale_fill_gradientn}

```php
scale_fill_gradientn(array $colors, mixed ...$args): Closure
```

### `scale_color_grey()` {#scale_color_grey}

```php
scale_color_grey(float $start = 0.2, float $end = 0.8, mixed ...$args): Closure
```

Grays for discrete data, as ggplot2's scale_colour_grey (computed for the number of levels).

### `scale_fill_grey()` {#scale_fill_grey}

```php
scale_fill_grey(float $start = 0.2, float $end = 0.8, mixed ...$args): Closure
```

### `scale_color_hue()` {#scale_color_hue}

```php
scale_color_hue(
    array $h = [0 => 15,1 => 375],
    int|float $c = 100,
    int|float $l = 65,
    int|float $hStart = 0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

ggplot2's hue palette with its options, as scale_colour_hue (computed for the number of levels).

### `scale_fill_hue()` {#scale_fill_hue}

```php
scale_fill_hue(
    array $h = [0 => 15,1 => 375],
    int|float $c = 100,
    int|float $l = 65,
    int|float $hStart = 0,
    int $direction = 1,
    mixed ...$args,
): Closure
```

### `scale_color_identity()` {#scale_color_identity}

```php
scale_color_identity(mixed ...$args): Closure
```

The data values are the colors (or sizes, or shapes) themselves, with no legend, as ggplot2's scale_*_identity.

### `scale_x_discrete()` {#scale_x_discrete}

```php
scale_x_discrete(?array $limits = null, mixed ...$args): Closure
```

Discrete position scales: limits orders (and subsets) the levels, labels renames them.

### `scale_y_discrete()` {#scale_y_discrete}

```php
scale_y_discrete(?array $limits = null, mixed ...$args): Closure
```

### `guides()` {#guides-function}

```php
guides(mixed ...$guides): Closure
```

Axis or legend options per aesthetic, as ggplot2's guides(): guides(color: 'none', x: guide_axis(angle: 45)).

### `guide_legend()` {#guide_legend}

```php
guide_legend(
    ?string $title = null,
    ?int $ncol = null,
    ?string $direction = null,
    mixed ...$properties,
): array
```

Legend options: title, columns (ncol), direction (horizontal or vertical), and further Vega-Lite legend properties.

### `guide_colorbar()` {#guide_colorbar}

```php
guide_colorbar(
    ?string $title = null,
    int|float|null $barwidth = null,
    int|float|null $barheight = null,
    ?string $direction = null,
    mixed ...$properties,
): array
```

Color bar options: barwidth and barheight in pixels.

### `guide_colourbar()` {#guide_colourbar}

```php
guide_colourbar(mixed ...$args): array
```

### `guide_axis()` {#guide_axis}

```php
guide_axis(
    ?string $title = null,
    int|float|null $angle = null,
    ?string $position = null,
    mixed ...$properties,
): array
```

Axis options: angle turns the labels anticlockwise, as ggplot2's guide_axis(angle =); position is top, bottom, left, or right.

### `guide_none()` {#guide_none}

```php
guide_none(): bool
```

### `label_number()` {#label_number}

```php
label_number(
    int|float|null $accuracy = null,
    int|float $scale = 1,
    string $prefix = '',
    string $suffix = '',
    string $bigMark = ',',
): Label
```

Number labels, as scales::label_number(): accuracy rounds, scale multiplies, prefix and suffix wrap.

### `label_comma()` {#label_comma}

```php
label_comma(
    int|float|null $accuracy = null,
    int|float $scale = 1,
    string $prefix = '',
    string $suffix = '',
): Label
```

Thousands separators, as scales::label_comma().

### `label_percent()` {#label_percent}

```php
label_percent(
    int|float|null $accuracy = null,
    int|float $scale = 100,
    string $prefix = '',
    string $suffix = '%',
): Label
```

Percentages of proportions, as scales::label_percent(): 0.25 becomes 25%.

### `label_dollar()` {#label_dollar}

```php
label_dollar(
    int|float|null $accuracy = null,
    int|float $scale = 1,
    string $prefix = '$',
    string $suffix = '',
): Label
```

Currency, as scales::label_dollar().

### `label_scientific()` {#label_scientific}

```php
label_scientific(int $digits = 3): Label
```

Scientific notation with digits significant digits, as scales::label_scientific().

### `label_date()` {#label_date}

```php
label_date(string $format = '%Y-%m-%d'): Label
```

Dates and times with a strftime-style format, as scales::label_date().

### `xlim()` {#xlim-function}

```php
xlim(string|int|float $min, string|int|float $max): Closure
```

### `ylim()` {#ylim-function}

```php
ylim(string|int|float $min, string|int|float $max): Closure
```


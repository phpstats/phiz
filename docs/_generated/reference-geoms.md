**Methods:** [`point()`](#point) · [`jitter()`](#jitter) · [`line()`](#line) · [`path()`](#path) · [`step()`](#step) · [`area()`](#area) · [`ribbon()`](#ribbon) · [`bar()`](#bar) · [`col()`](#col) · [`histogram()`](#histogram) · [`freqpoly()`](#freqpoly) · [`density()`](#density) · [`boxplot()`](#boxplot) · [`violin()`](#violin) · [`errorbar()`](#errorbar) · [`linerange()`](#linerange) · [`pointrange()`](#pointrange) · [`hline()`](#hline) · [`vline()`](#vline) · [`abline()`](#abline) · [`rule()`](#rule) · [`segment()`](#segment) · [`text()`](#text) · [`label()`](#label) · [`tile()`](#tile) · [`raster()`](#raster) · [`rect()`](#rect) · [`bin2d()`](#bin2d) · [`hex()`](#hex) · [`smooth()`](#smooth) · [`density2d()`](#density2d) · [`density2dFilled()`](#density2d-filled) · [`contour()`](#contour) · [`contourFilled()`](#contour-filled) · [`polygon()`](#polygon) · [`summary()`](#summary) · [`crossbar()`](#crossbar) · [`ellipse()`](#ellipse) · [`count()`](#count) · [`annotate()`](#annotate) · [`curve()`](#curve) · [`rug()`](#rug) · [`ecdf()`](#ecdf) · [`qq()`](#qq) · [`qqLine()`](#qq-line) · [`arc()`](#arc) · [`pie()`](#pie) · [`layer()`](#layer) · [`geom()`](#geom)

**Functions:** [`geom_point()`](#geom_point) · [`geom_jitter()`](#geom_jitter) · [`geom_line()`](#geom_line) · [`geom_path()`](#geom_path) · [`geom_step()`](#geom_step) · [`geom_area()`](#geom_area) · [`geom_ribbon()`](#geom_ribbon) · [`geom_bar()`](#geom_bar) · [`geom_col()`](#geom_col) · [`geom_histogram()`](#geom_histogram) · [`geom_freqpoly()`](#geom_freqpoly) · [`geom_density()`](#geom_density) · [`geom_boxplot()`](#geom_boxplot) · [`geom_violin()`](#geom_violin) · [`geom_errorbar()`](#geom_errorbar) · [`geom_linerange()`](#geom_linerange) · [`geom_pointrange()`](#geom_pointrange) · [`geom_hline()`](#geom_hline) · [`geom_vline()`](#geom_vline) · [`geom_abline()`](#geom_abline) · [`geom_rule()`](#geom_rule) · [`geom_segment()`](#geom_segment) · [`geom_text()`](#geom_text) · [`geom_label()`](#geom_label) · [`geom_tile()`](#geom_tile) · [`geom_raster()`](#geom_raster) · [`geom_rect()`](#geom_rect) · [`geom_bin2d()`](#geom_bin2d) · [`geom_hex()`](#geom_hex) · [`geom_smooth()`](#geom_smooth) · [`geom_function()`](#geom_function) · [`geom_rug()`](#geom_rug) · [`stat_ecdf()`](#stat_ecdf) · [`stat_qq()`](#stat_qq) · [`stat_qq_line()`](#stat_qq_line) · [`geom_arc()`](#geom_arc) · [`geom_polygon()`](#geom_polygon) · [`stat_summary()`](#stat_summary) · [`geom_crossbar()`](#geom_crossbar) · [`geom_count()`](#geom_count) · [`stat_ellipse()`](#stat_ellipse) · [`annotate()`](#annotate-function) · [`geom_density_2d()`](#geom_density_2d) · [`stat_density_2d()`](#stat_density_2d) · [`geom_density_2d_filled()`](#geom_density_2d_filled) · [`stat_density_2d_filled()`](#stat_density_2d_filled) · [`geom_contour()`](#geom_contour) · [`stat_contour()`](#stat_contour) · [`geom_contour_filled()`](#geom_contour_filled)

### `point()` {#point}

```php
->point(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $alpha = null,
    int|float|null $size = null,
    ?string $color = null,
    ?string $fill = null,
    string|int|null $shape = null,
    array|int|float|bool|null $jitter = null,
    ?string $position = null,
    ?int $seed = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Scatterplot points. jitter: true (or a width, or [width, height]) displaces them randomly. seed: makes the jitter reproducible: phiz draws it in PHP and embeds it in the data, so every render places the points the same way.

Function: [`geom_point()`]{#geom_point}.

### `jitter()` {#jitter}

```php
->jitter(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $width = null,
    int|float $height = 0,
    int|float|null $alpha = null,
    int|float|null $size = null,
    ?string $color = null,
    ?int $seed = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Points jittered by up to width/height (fraction of a band on discrete axes, data units otherwise). Without a seed the browser draws the jitter, so it changes on every render; with seed: phiz draws it in PHP and it is the same.

Function: [`geom_jitter()`]{#geom_jitter}.

### `line()` {#line}

```php
->line(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $alpha = null,
    int|float|null $linewidth = null,
    ?string $color = null,
    array|string|null $linetype = null,
    ?int $downsample = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Lines connecting points in order of x. downsample: n keeps about n points per group (LTTB), in PHP.

Function: [`geom_line()`]{#geom_line}.

### `path()` {#path}

```php
->path(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $alpha = null,
    int|float|null $linewidth = null,
    ?string $color = null,
    array|string|null $linetype = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Lines connecting points in data order.

Function: [`geom_path()`]{#geom_path}.

### `step()` {#step}

```php
->step(
    Aes|array|null $aes = null,
    mixed $data = null,
    string $direction = 'hv',
    int|float|null $linewidth = null,
    ?string $color = null,
    array|string|null $linetype = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Stairsteps: direction 'hv' (horizontal first), 'vh', or 'mid'.

Function: [`geom_step()`]{#geom_step}.

### `area()` {#area}

```php
->area(
    Aes|array|null $aes = null,
    mixed $data = null,
    string $position = 'stack',
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Areas from zero, stacked by fill (position: stack, fill, identity).

Function: [`geom_area()`]{#geom_area}.

### `ribbon()` {#ribbon}

```php
->ribbon(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

A band between ymin and ymax (or xmin and xmax).

Function: [`geom_ribbon()`]{#geom_ribbon}.

### `bar()` {#bar}

```php
->bar(
    Aes|array|null $aes = null,
    mixed $data = null,
    string $position = 'stack',
    int|float|null $width = null,
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Bars of counts of x (or y), or of the y values when y is mapped. position: stack, dodge, fill, identity.

Function: [`geom_bar()`]{#geom_bar}.

### `col()` {#col}

```php
->col(
    Aes|array|null $aes = null,
    mixed $data = null,
    string $position = 'stack',
    int|float|null $width = null,
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Bars whose heights are the y values.

Function: [`geom_col()`]{#geom_col}.

### `histogram()` {#histogram}

```php
->histogram(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?int $bins = null,
    int|float|null $binwidth = null,
    int|float|null $boundary = null,
    string $position = 'stack',
    bool $density = false,
    string $compute = 'auto',
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Binned counts of x: bins (a maximum; Vega-Lite picks nice boundaries) or binwidth. density: true scales to unit area. compute: 'server' bins in PHP (exactly bins equal-width bins), 'browser' in Vega-Lite, and 'auto' (the default) in PHP above Config::$serverRows rows.

Function: [`geom_histogram()`]{#geom_histogram}.

### `freqpoly()` {#freqpoly}

```php
->freqpoly(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?int $bins = null,
    int|float|null $binwidth = null,
    int|float|null $linewidth = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Frequency polygons: binned counts drawn as lines.

Function: [`geom_freqpoly()`]{#geom_freqpoly}.

### `density()` {#density}

```php
->density(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $bw = null,
    int|float|null $adjust = null,
    ?array $extent = null,
    ?int $n = null,
    bool $counts = false,
    ?string $position = null,
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Kernel density of x per group (Vega-Lite's density transform). bw sets the bandwidth; adjust multiplies it (from R's bw.nrd0 when bw is unset); counts: true scales to counts.

Function: [`geom_density()`]{#geom_density}.

### `boxplot()` {#boxplot}

```php
->boxplot(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float $coef = 1.5,
    bool $outliers = true,
    int|float|null $width = null,
    int|float|null $alpha = null,
    ?string $fill = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Tukey boxplots; coef sets the whisker extent in IQRs.

Function: [`geom_boxplot()`]{#geom_boxplot}.

### `violin()` {#violin}

```php
->violin(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $bw = null,
    int|float|null $adjust = null,
    float $width = 0.9,
    ?int $n = null,
    ?string $position = null,
    int|float|null $alpha = null,
    ?string $fill = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Violins: the kernel density of y per level of a discrete x. bw sets the bandwidth, adjust multiplies R's bw.nrd0, and width is the widest violin's share of its band. They layer with boxplot() and jitter().

Function: [`geom_violin()`]{#geom_violin}.

### `errorbar()` {#errorbar}

```php
->errorbar(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $width = null,
    ?string $position = null,
    ?string $color = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Error bars from ymin to ymax (or xmin to xmax) with end ticks; width is the tick length in pixels.

Function: [`geom_errorbar()`]{#geom_errorbar}.

### `linerange()` {#linerange}

```php
->linerange(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $position = null,
    ?string $color = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Vertical (or horizontal) lines from ymin to ymax.

Function: [`geom_linerange()`]{#geom_linerange}.

### `pointrange()` {#pointrange}

```php
->pointrange(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $position = null,
    ?string $color = null,
    int|float|null $size = null,
    int|float|null $linewidth = null,
    string|int|null $shape = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

A point at y on a line from ymin to ymax: the coefficient-plot geom. position: 'dodge' sets groups side by side.

Function: [`geom_pointrange()`]{#geom_pointrange}.

### `hline()` {#hline}

```php
->hline(
    Expr|array|int|float|null $yintercept = null,
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    mixed ...$style,
): Plot
```

Horizontal reference lines at fixed values, or at a mapped yintercept field when yintercept is null.

Function: [`geom_hline()`]{#geom_hline}.

### `vline()` {#vline}

```php
->vline(
    Expr|array|string|int|float|null $xintercept = null,
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    mixed ...$style,
): Plot
```

Vertical reference lines at fixed values, or at a mapped xintercept field.

Function: [`geom_vline()`]{#geom_vline}.

### `abline()` {#abline}

```php
->abline(
    array|int|float $intercept = 0,
    array|int|float $slope = 1,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    mixed ...$style,
): Plot
```

Lines y = intercept + slope * x across the x range (lists draw several).

Function: [`geom_abline()`]{#geom_abline}.

### `rule()` {#rule}

```php
->rule(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Rules at mapped positions (x, y, xend, yend).

Function: [`geom_rule()`]{#geom_rule}.

### `segment()` {#segment}

```php
->segment(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Line segments from (x, y) to (xend, yend).

Function: [`geom_segment()`]{#geom_segment}.

### `text()` {#text}

```php
->text(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $size = null,
    ?string $color = null,
    int|float|null $angle = null,
    int|float|null $nudgeX = null,
    int|float|null $nudgeY = null,
    int|float|null $hjust = null,
    int|float|null $vjust = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Text from the label aesthetic. nudgeX/nudgeY offset it in pixels; angle turns it anticlockwise.

Function: [`geom_text()`]{#geom_text}.

### `label()` {#label}

```php
->label(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $size = null,
    ?string $color = null,
    int|float|null $nudgeX = null,
    int|float|null $nudgeY = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Bold text labels.

Function: [`geom_label()`]{#geom_label}.

### `tile()` {#tile}

```php
->tile(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Heatmap tiles at discrete x and y (numbers are treated as ordered levels), filled by the fill aesthetic.

Function: [`geom_tile()`]{#geom_tile}.

### `raster()` {#raster}

```php
->raster(
    Aes|array|null $aes = null,
    mixed $data = null,
    bool $inheritAes = true,
    mixed ...$style,
): Plot
```

Alias of tile().

Function: [`geom_raster()`]{#geom_raster}.

### `rect()` {#rect}

```php
->rect(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $fill = null,
    ?string $color = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Rectangles from xmin, xmax, ymin, ymax.

Function: [`geom_rect()`]{#geom_rect}.

### `bin2d()` {#bin2d}

```php
->bin2d(
    Aes|array|null $aes = null,
    mixed $data = null,
    array|int $bins = 30,
    string $compute = 'auto',
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

A 2D histogram: counts in x-by-y bins; compute works as in histogram().

Function: [`geom_bin2d()`]{#geom_bin2d}.

### `hex()` {#hex}

```php
->hex(
    Aes|array|null $aes = null,
    mixed $data = null,
    int $bins = 30,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Hexagonal bins counted in PHP, as ggplot2's geom_hex: bins hexagons across the x range.

Function: [`geom_hex()`]{#geom_hex}.

### `smooth()` {#smooth}

```php
->smooth(
    Aes|array|null $aes = null,
    mixed $data = null,
    string $method = 'loess',
    ?bool $se = null,
    float $level = 0.95,
    float $span = 0.75,
    int $order = 3,
    int $n = 80,
    ?string $color = null,
    ?string $fill = null,
    int|float|null $linewidth = null,
    array|string|null $linetype = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

A fitted curve per group. method: loess (span sets the bandwidth), lm, poly (order), quad, log, exp, pow. With se (the default for lm, quad and poly with inline data) the fit and a level confidence band are computed in PHP; otherwise Vega-Lite fits the curve and no band is drawn.

Function: [`geom_smooth()`]{#geom_smooth}.

### `density2d()` {#density2d}

```php
->density2d(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?int $bins = null,
    int|float|null $binwidth = null,
    ?array $breaks = null,
    array|int|float|null $h = null,
    int|float $adjust = 1,
    int $n = 100,
    string $contourVar = 'density',
    ?string $color = null,
    int|float|null $linewidth = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Contour lines of a 2D kernel density of x and y, as ggplot2's geom_density_2d (MASS::kde2d on an n × n grid). h sets the bandwidths ([x, y] or one for both; default MASS::bandwidth.nrd), adjust multiplies them, and bins, binwidth, or breaks set the levels (default pretty(range, 10)). contourVar: density, ndensity (scaled to a maximum of 1 per group), or count. aes(color: 'level') colors lines by their level.

Functions: [`geom_density_2d()`]{#geom_density_2d}, [`stat_density_2d()`]{#stat_density_2d}.

### `density2dFilled()` {#density2d-filled}

```php
->density2dFilled(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?int $bins = null,
    int|float|null $binwidth = null,
    ?array $breaks = null,
    array|int|float|null $h = null,
    int|float $adjust = 1,
    int $n = 100,
    string $contourVar = 'density',
    int|float|null $alpha = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Filled bands of a 2D kernel density between consecutive breaks, as ggplot2's geom_density_2d_filled.

Functions: [`geom_density_2d_filled()`]{#geom_density_2d_filled}, [`stat_density_2d_filled()`]{#stat_density_2d_filled}.

### `contour()` {#contour}

```php
->contour(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?int $bins = null,
    int|float|null $binwidth = null,
    ?array $breaks = null,
    ?string $color = null,
    int|float|null $linewidth = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Contour lines of z over a regular grid of x and y, as ggplot2's geom_contour; bins, binwidth, or breaks.

Functions: [`geom_contour()`]{#geom_contour}, [`stat_contour()`]{#stat_contour}.

### `contourFilled()` {#contour-filled}

```php
->contourFilled(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?int $bins = null,
    int|float|null $binwidth = null,
    ?array $breaks = null,
    int|float|null $alpha = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Filled contour bands of z over a regular grid, as ggplot2's geom_contour_filled.

Function: [`geom_contour_filled()`]{#geom_contour_filled}.

### `polygon()` {#polygon}

```php
->polygon(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $fill = null,
    ?string $color = null,
    int|float|null $alpha = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Filled polygons through the points in data order, one per group (or fill level). Rows with null x and y start a new ring within a group; a ring drawn in the opposite direction cuts a hole.

Function: [`geom_polygon()`]{#geom_polygon}.

### `summary()` {#summary}

```php
->summary(
    Aes|array|null $aes = null,
    mixed $data = null,
    Closure|string|null $funData = null,
    Closure|string|null $fun = null,
    Closure|string|null $funMin = null,
    Closure|string|null $funMax = null,
    array $funArgs = [],
    ?string $geom = null,
    ?string $position = null,
    int|float|null $width = null,
    ?string $color = null,
    int|float|null $size = null,
    int|float|null $linewidth = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Summaries of y at each x, drawn with geom (pointrange by default), as ggplot2's stat_summary. funData: mean_se (the default), mean_cl_normal, mean_sdl, median_hilow, or a callable returning [y, ymin, ymax]. fun, funMin, and funMax: mean, median, min, max, sum, sd, n, or callables of the group's values. funArgs passes mult or conf. geom: pointrange, errorbar, linerange, crossbar, point, line, col, area, or ribbon.

Function: [`stat_summary()`]{#stat_summary}.

### `crossbar()` {#crossbar}

```php
->crossbar(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $width = null,
    ?string $position = null,
    ?string $color = null,
    ?string $fill = null,
    int|float|null $linewidth = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

A box from ymin to ymax with a line at y, as ggplot2's geom_crossbar; width is a share of the band.

Function: [`geom_crossbar()`]{#geom_crossbar}.

### `ellipse()` {#ellipse}

```php
->ellipse(
    Aes|array|null $aes = null,
    mixed $data = null,
    string $type = 't',
    float $level = 0.95,
    int $segments = 51,
    string $geom = 'path',
    ?string $color = null,
    ?string $fill = null,
    int|float|null $linewidth = null,
    array|string|null $linetype = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Confidence ellipses per group, as ggplot2's stat_ellipse: type 't' (a robust multivariate t fit), 'norm', or 'euclid' (a circle of radius level), at level. geom: 'polygon' fills them.

Function: [`stat_ellipse()`]{#stat_ellipse}.

### `count()` {#count}

```php
->count(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Points sized by how many rows share each x and y, as ggplot2's geom_count.

Function: [`geom_count()`]{#geom_count}.

### `annotate()` {#annotate}

```php
->annotate(
    string $geom,
    mixed $x = null,
    mixed $y = null,
    mixed $xmin = null,
    mixed $xmax = null,
    mixed $ymin = null,
    mixed $ymax = null,
    mixed $xend = null,
    mixed $yend = null,
    mixed $label = null,
    mixed ...$style,
): Plot
```

A layer of fixed values rather than data, as ggplot2's annotate(): annotate('text', x: 4, y: 40, label: 'Note'), annotate('rect', xmin: 2, xmax: 3, ymin: -INF ...). Positions are numbers, dates, or levels, or lists of them for several marks. Annotations ignore the plot's mappings and appear in every facet panel. geom: text, label, point, segment, rect, pointrange, linerange, errorbar, crossbar, or rule.

Function: [`annotate()`](#annotate-function).

### `curve()` {#curve}

```php
->curve(
    callable $fn,
    int|float $from,
    int|float $to,
    int $n = 101,
    Aes|array|null $aes = null,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    mixed ...$style,
): Plot
```

A function of x drawn from `from` to `to`, as ggplot2's geom_function.

Function: [`geom_function()`]{#geom_function}.

### `rug()` {#rug}

```php
->rug(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $sides = null,
    int $length = 8,
    ?string $color = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Marginal ticks: sides 'b' (bottom, for x), 'l' (left, for y), or 'bl'; length in pixels.

Function: [`geom_rug()`]{#geom_rug}.

### `ecdf()` {#ecdf}

```php
->ecdf(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Empirical cumulative distribution of x per group.

Function: [`stat_ecdf()`]{#stat_ecdf}.

### `qq()` {#qq}

```php
->qq(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    int|float|null $size = null,
    int|float|null $alpha = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Normal quantile-quantile points of the sample aesthetic.

Function: [`stat_qq()`]{#stat_qq}.

### `qqLine()` {#qq-line}

```php
->qqLine(
    Aes|array|null $aes = null,
    mixed $data = null,
    ?string $color = null,
    array|string|null $linetype = null,
    int|float|null $linewidth = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

The QQ reference line through the sample's quartiles.

Function: [`stat_qq_line()`]{#stat_qq_line}.

### `arc()` {#arc}

```php
->arc(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $innerRadius = null,
    ?string $color = null,
    bool $inheritAes = true,
    array|string|null $filter = null,
    mixed ...$style,
): Plot
```

Pie (or donut, with innerRadius in pixels) slices sized by theta, or counts of fill.

Function: [`geom_arc()`]{#geom_arc}.

### `pie()` {#pie}

```php
->pie(
    Aes|array|null $aes = null,
    mixed $data = null,
    int|float|null $innerRadius = null,
    mixed ...$style,
): Plot
```

Alias of arc().

### `layer()` {#layer}

```php
->layer(array $spec): Plot
```

A raw Vega-Lite layer, used as given.

### `geom()` {#geom}

```php
->geom(
    string $geom,
    Aes|array|null $aes = null,
    mixed $data = null,
    array $params = [],
    array $style = [],
    bool $inheritAes = true,
    array|string|null $filter = null,
    array $raw = [],
): Plot
```

Adds a geom by name with options: the generic form behind the geom methods.

## Functions

### `annotate()` {#annotate-function}

```php
annotate(string $geom, mixed ...$args): Closure
```


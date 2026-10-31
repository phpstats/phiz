### Construction

```php
Plot::of(mixed $data = null, Aes|array|null $aes = null): Plot
```

A plot of rows, columns, a phibble Table, a Laravel Collection, or Data::url(...), with optional mappings.

### Mappings and data

```php
->aes(mixed ...$mappings): Plot
```

Adds plot-wide aesthetic mappings: x, y, color, fill, size, shape, alpha, group, tooltip, label, ...

```php
->data(mixed $data): Plot
```

### Geoms

```php
->point(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, int|float|null $size = null, ?string $color = null, ?string $fill = null, string|int|null $shape = null, array|int|float|bool|null $jitter = null, ?string $position = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Scatterplot points. jitter: true (or a width, or [width, height]) displaces them randomly.

```php
->jitter(Aes|array|null $aes = null, mixed $data = null, int|float|null $width = null, int|float $height = 0, int|float|null $alpha = null, int|float|null $size = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Points jittered by up to width/height (fraction of a band on discrete axes, data units otherwise).

```php
->line(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, int|float|null $linewidth = null, ?string $color = null, array|string|null $linetype = null, ?int $downsample = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Lines connecting points in order of x. downsample: n keeps about n points per group (LTTB), in PHP.

```php
->path(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, int|float|null $linewidth = null, ?string $color = null, array|string|null $linetype = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Lines connecting points in data order.

```php
->step(Aes|array|null $aes = null, mixed $data = null, string $direction = 'hv', int|float|null $linewidth = null, ?string $color = null, array|string|null $linetype = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Stairsteps: direction 'hv' (horizontal first), 'vh', or 'mid'.

```php
->area(Aes|array|null $aes = null, mixed $data = null, string $position = 'stack', int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Areas from zero, stacked by fill (position: stack, fill, identity).

```php
->ribbon(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

A band between ymin and ymax (or xmin and xmax).

```php
->bar(Aes|array|null $aes = null, mixed $data = null, string $position = 'stack', int|float|null $width = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Bars of counts of x (or y), or of the y values when y is mapped. position: stack, dodge, fill, identity.

```php
->col(Aes|array|null $aes = null, mixed $data = null, string $position = 'stack', int|float|null $width = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Bars whose heights are the y values.

```php
->histogram(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, int|float|null $boundary = null, string $position = 'stack', bool $density = false, string $compute = 'auto', int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Binned counts of x: bins (a maximum; Vega-Lite picks nice boundaries) or binwidth. density: true scales to unit area. compute: 'server' bins in PHP (exactly bins equal-width bins), 'browser' in Vega-Lite, and 'auto' (the default) in PHP above Config::$serverRows rows.

```php
->freqpoly(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, int|float|null $linewidth = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Frequency polygons: binned counts drawn as lines.

```php
->density(Aes|array|null $aes = null, mixed $data = null, int|float|null $bw = null, int|float|null $adjust = null, ?array $extent = null, ?int $n = null, bool $counts = false, ?string $position = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Kernel density of x per group (Vega-Lite's density transform). bw sets the bandwidth; adjust multiplies it (from R's bw.nrd0 when bw is unset); counts: true scales to counts.

```php
->boxplot(Aes|array|null $aes = null, mixed $data = null, int|float $coef = 1.5, bool $outliers = true, int|float|null $width = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Tukey boxplots; coef sets the whisker extent in IQRs.

```php
->violin(Aes|array|null $aes = null, mixed $data = null, int|float|null $bw = null, int|float|null $adjust = null, float $width = 0.9, ?int $n = null, ?string $position = null, int|float|null $alpha = null, ?string $fill = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Violins: the kernel density of y per level of a discrete x. bw sets the bandwidth, adjust multiplies R's bw.nrd0, and width is the widest violin's share of its band. They layer with boxplot() and jitter().

```php
->errorbar(Aes|array|null $aes = null, mixed $data = null, int|float|null $width = null, ?string $position = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Error bars from ymin to ymax (or xmin to xmax) with end ticks; width is the tick length in pixels.

```php
->linerange(Aes|array|null $aes = null, mixed $data = null, ?string $position = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Vertical (or horizontal) lines from ymin to ymax.

```php
->pointrange(Aes|array|null $aes = null, mixed $data = null, ?string $position = null, ?string $color = null, int|float|null $size = null, int|float|null $linewidth = null, string|int|null $shape = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

A point at y on a line from ymin to ymax: the coefficient-plot geom. position: 'dodge' sets groups side by side.

```php
->hline(Expr|array|int|float|null $yintercept = null, Aes|array|null $aes = null, mixed $data = null, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): Plot
```

Horizontal reference lines at fixed values, or at a mapped yintercept field when yintercept is null.

```php
->vline(Expr|array|string|int|float|null $xintercept = null, Aes|array|null $aes = null, mixed $data = null, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): Plot
```

Vertical reference lines at fixed values, or at a mapped xintercept field.

```php
->abline(array|int|float $intercept = 0, array|int|float $slope = 1, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): Plot
```

Lines y = intercept + slope * x across the x range (lists draw several).

```php
->rule(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Rules at mapped positions (x, y, xend, yend).

```php
->segment(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Line segments from (x, y) to (xend, yend).

```php
->text(Aes|array|null $aes = null, mixed $data = null, int|float|null $size = null, ?string $color = null, int|float|null $angle = null, int|float|null $nudgeX = null, int|float|null $nudgeY = null, int|float|null $hjust = null, int|float|null $vjust = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Text from the label aesthetic. nudgeX/nudgeY offset it in pixels; angle turns it anticlockwise.

```php
->label(Aes|array|null $aes = null, mixed $data = null, int|float|null $size = null, ?string $color = null, int|float|null $nudgeX = null, int|float|null $nudgeY = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Bold text labels.

```php
->tile(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Heatmap tiles at discrete x and y (numbers are treated as ordered levels), filled by the fill aesthetic.

```php
->raster(Aes|array|null $aes = null, mixed $data = null, bool $inheritAes = true, mixed ...$style): Plot
```

Alias of tile().

```php
->rect(Aes|array|null $aes = null, mixed $data = null, ?string $fill = null, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Rectangles from xmin, xmax, ymin, ymax.

```php
->bin2d(Aes|array|null $aes = null, mixed $data = null, array|int $bins = 30, string $compute = 'auto', bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

A 2D histogram: counts in x-by-y bins; compute works as in histogram().

```php
->hex(Aes|array|null $aes = null, mixed $data = null, int $bins = 30, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Hexagonal bins counted in PHP, as ggplot2's geom_hex: bins hexagons across the x range.

```php
->smooth(Aes|array|null $aes = null, mixed $data = null, string $method = 'loess', ?bool $se = null, float $level = 0.95, float $span = 0.75, int $order = 3, int $n = 80, ?string $color = null, ?string $fill = null, int|float|null $linewidth = null, array|string|null $linetype = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

A fitted curve per group. method: loess (span sets the bandwidth), lm, poly (order), quad, log, exp, pow. With se (the default for lm, quad and poly with inline data) the fit and a level confidence band are computed in PHP; otherwise Vega-Lite fits the curve and no band is drawn.

```php
->density2d(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, array|int|float|null $h = null, int|float $adjust = 1, int $n = 100, string $contourVar = 'density', ?string $color = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Contour lines of a 2D kernel density of x and y, as ggplot2's geom_density_2d (MASS::kde2d on an n × n grid). h sets the bandwidths ([x, y] or one for both; default MASS::bandwidth.nrd), adjust multiplies them, and bins, binwidth, or breaks set the levels (default pretty(range, 10)). contourVar: density, ndensity (scaled to a maximum of 1 per group), or count. aes(color: 'level') colors lines by their level.

```php
->density2dFilled(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, array|int|float|null $h = null, int|float $adjust = 1, int $n = 100, string $contourVar = 'density', int|float|null $alpha = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Filled bands of a 2D kernel density between consecutive breaks, as ggplot2's geom_density_2d_filled.

```php
->contour(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, ?string $color = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Contour lines of z over a regular grid of x and y, as ggplot2's geom_contour; bins, binwidth, or breaks.

```php
->contourFilled(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, int|float|null $alpha = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Filled contour bands of z over a regular grid, as ggplot2's geom_contour_filled.

```php
->polygon(Aes|array|null $aes = null, mixed $data = null, ?string $fill = null, ?string $color = null, int|float|null $alpha = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Filled polygons through the points in data order, one per group (or fill level). Rows with null x and y start a new ring within a group; a ring drawn in the opposite direction cuts a hole.

```php
->summary(Aes|array|null $aes = null, mixed $data = null, Closure|string|null $funData = null, Closure|string|null $fun = null, Closure|string|null $funMin = null, Closure|string|null $funMax = null, array $funArgs = [], ?string $geom = null, ?string $position = null, int|float|null $width = null, ?string $color = null, int|float|null $size = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Summaries of y at each x, drawn with geom (pointrange by default), as ggplot2's stat_summary. funData: mean_se (the default), mean_cl_normal, mean_sdl, median_hilow, or a callable returning [y, ymin, ymax]. fun, funMin, and funMax: mean, median, min, max, sum, sd, n, or callables of the group's values. funArgs passes mult or conf. geom: pointrange, errorbar, linerange, crossbar, point, line, col, area, or ribbon.

```php
->crossbar(Aes|array|null $aes = null, mixed $data = null, int|float|null $width = null, ?string $position = null, ?string $color = null, ?string $fill = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

A box from ymin to ymax with a line at y, as ggplot2's geom_crossbar; width is a share of the band.

```php
->ellipse(Aes|array|null $aes = null, mixed $data = null, string $type = 't', float $level = 0.95, int $segments = 51, string $geom = 'path', ?string $color = null, ?string $fill = null, int|float|null $linewidth = null, array|string|null $linetype = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Confidence ellipses per group, as ggplot2's stat_ellipse: type 't' (a robust multivariate t fit), 'norm', or 'euclid' (a circle of radius level), at level. geom: 'polygon' fills them.

```php
->count(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Points sized by how many rows share each x and y, as ggplot2's geom_count.

```php
->annotate(string $geom, mixed $x = null, mixed $y = null, mixed $xmin = null, mixed $xmax = null, mixed $ymin = null, mixed $ymax = null, mixed $xend = null, mixed $yend = null, mixed $label = null, mixed ...$style): Plot
```

A layer of fixed values rather than data, as ggplot2's annotate(): annotate('text', x: 4, y: 40, label: 'Note'), annotate('rect', xmin: 2, xmax: 3, ymin: -INF ...). Positions are numbers, dates, or levels, or lists of them for several marks. Annotations ignore the plot's mappings and appear in every facet panel. geom: text, label, point, segment, rect, pointrange, linerange, errorbar, crossbar, or rule.

```php
->curve(callable $fn, int|float $from, int|float $to, int $n = 101, Aes|array|null $aes = null, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): Plot
```

A function of x drawn from `from` to `to`, as ggplot2's geom_function.

```php
->rug(Aes|array|null $aes = null, mixed $data = null, ?string $sides = null, int $length = 8, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Marginal ticks: sides 'b' (bottom, for x), 'l' (left, for y), or 'bl'; length in pixels.

```php
->ecdf(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Empirical cumulative distribution of x per group.

```php
->qq(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $size = null, int|float|null $alpha = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Normal quantile-quantile points of the sample aesthetic.

```php
->qqLine(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, array|string|null $linetype = null, int|float|null $linewidth = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

The QQ reference line through the sample's quartiles.

```php
->arc(Aes|array|null $aes = null, mixed $data = null, int|float|null $innerRadius = null, ?string $color = null, bool $inheritAes = true, array|string|null $filter = null, mixed ...$style): Plot
```

Pie (or donut, with innerRadius in pixels) slices sized by theta, or counts of fill.

```php
->pie(Aes|array|null $aes = null, mixed $data = null, int|float|null $innerRadius = null, mixed ...$style): Plot
```

Alias of arc().

```php
->layer(array $spec): Plot
```

A raw Vega-Lite layer, used as given.

```php
->geom(string $geom, Aes|array|null $aes = null, mixed $data = null, array $params = [], array $style = [], bool $inheritAes = true, array|string|null $filter = null, array $raw = []): Plot
```

Adds a geom by name with options: the generic form behind the geom methods.

### Scales

```php
->scale(string $aesthetic, mixed ...$options): Plot
```

Sets the scale for an aesthetic; see Scale::make() for the options.

```php
->scaleX(mixed ...$options): Plot
```

x scale: type (linear, log, sqrt, time, ...), domain, range, zero, nice, reverse, name, breaks, labels, format.

```php
->scaleY(mixed ...$options): Plot
```

```php
->scaleColor(mixed ...$options): Plot
```

Color scale: scheme ('viridis', 'blues', 'category10', ...), range, values (manual palette), domainMid, ...

```php
->scaleFill(mixed ...$options): Plot
```

```php
->scaleSize(mixed ...$options): Plot
```

```php
->scaleShape(mixed ...$options): Plot
```

```php
->scaleOpacity(mixed ...$options): Plot
```

```php
->scaleLinetype(mixed ...$options): Plot
```

```php
->guides(mixed ...$guides): Plot
```

Guides per aesthetic, as ggplot2's guides(): 'none' or false hides one, and an array sets axis or legend properties (see guide_legend(), guide_colorbar(), and guide_axis()).

```php
->xlim(string|int|float $min, string|int|float $max): Plot
```

Fixes the x domain. Unlike ggplot2's xlim(), data outside it is clipped rather than dropped.

```php
->ylim(string|int|float $min, string|int|float $max): Plot
```

### Coordinates and facets

```php
->coordFlip(bool $flip = true): Plot
```

Swaps the x and y channels, so mappings stay as written while the plot turns sideways.

```php
->coordPolar(string $theta = 'x', float $start = 0.0, int $direction = 1, int|float $innerRadius = 0): Plot
```

Polar coordinates for bar() and col() layers, as ggplot2's coord_polar. theta: 'y' turns stacked bars into a pie (map x to a constant, or leave it out; innerRadius in pixels makes a donut), and 'x' turns bars over a discrete x into a rose of equal angles with radius y. start rotates the first slice clockwise from twelve o'clock, in radians; direction -1 runs anticlockwise.

```php
->coordFixed(int|float $ratio = 1): Plot
```

A fixed aspect ratio, as ggplot2's coord_fixed: one unit of y takes ratio times the length of one unit of x. The panel's height follows from the width (300 pixels unless set) and the data ranges, which are kept exact.

```php
->coordCartesian(?array $xlim = null, ?array $ylim = null): Plot
```

Zooms to fixed domains, clipping marks outside them.

```php
->facetWrap(array|string $facets, ?int $ncol = null, ?int $nrow = null, string $scales = 'fixed', array|string $labeller = 'value', array $header = []): Plot
```

Small multiples wrapped into rows. scales: fixed, free, free_x, free_y. With several fields the panels are their combinations. labeller: 'both' labels panels "field: value", as ggplot2's label_both.

```php
->facetGrid(?string $rows = null, ?string $cols = null, string $scales = 'fixed', array|string $labeller = 'value', array $header = []): Plot
```

A grid of panels by rows and/or cols fields.

### Labels, theme, size

```php
->labs(mixed ...$labels): Plot
```

Titles: title, subtitle, caption, and one per aesthetic (x, y, color, fill, size, shape, ...). A facet field's name sets its panel header title. Null removes a title. The caption sits below the plot; theme() styles it.

```php
->title(?string $title, ?string $subtitle = null): Plot
```

```php
->theme(?string $preset = null, int|float|null $baseSize = null, ?string $font = null, ?string $legendPosition = null, ?string $background = null, ?string $panelBackground = null, ?string $panelBorder = null, ?bool $grid = null, ?string $titleAnchor = null, int|float|null $padding = null, int|float|null $xLabelAngle = null, int|float|null $yLabelAngle = null, ?string $legendDirection = null, ?bool $legendTitle = null, ?bool $axisLine = null, ?bool $ticks = null, ?string $gridColor = null, ?string $textColor = null, int|float|null $captionSize = null, ?string $captionColor = null, ?string $captionAnchor = null, array $config = []): Plot
```

A theme preset (gray, bw, linedraw, minimal, classic, light, dark, void, vega) and/or individual options; config is raw Vega-Lite config merged last. Successive calls build on each other. xLabelAngle and yLabelAngle turn axis labels anticlockwise, as element_text(angle =); legendTitle: false, axisLine, and ticks switch those parts. captionSize, captionColor, and captionAnchor (start, middle, or end) style labs(caption:).

```php
->size(string|int|null $width = null, string|int|null $height = null): Plot
```

Width and height in pixels (per panel when faceted); 'container' fills the parent element's width.

```php
->width(string|int $width): Plot
```

```php
->height(string|int $height): Plot
```

```php
->description(string $description): Plot
```

### Interaction

```php
->tooltip(array|string|bool $fields = true): Plot
```

Tooltips on every layer: true shows the encoded fields, a list shows those fields.

```php
->interactive(array $encodings = [0 => 'x',1 => 'y'], string $name = 'zoom'): Plot
```

Pan and zoom by dragging and scrolling (scales bound to an interval selection).

```php
->brush(string $name = 'brush', array $encodings = [0 => 'x'], ?string $unselected = '#cccccc', string $channel = 'color'): Plot
```

A drag-to-select interval named $name. Unselected marks of the first layer turn $unselected; other layers can react with filter: ['param' => $name], and linked plots in a composition share it.

```php
->highlight(string $aesthetic = 'color', string $name = 'highlight', ?string $bind = 'legend', float $opacity = 0.15): Plot
```

Click legend entries (or marks, with bind: null) to highlight their group; others fade to $opacity.

```php
->slider(string $name, int|float $min, int|float $max, int|float|null $step = null, int|float|null $value = null, ?string $label = null): Plot
```

A slider bound to a parameter; use expr($name) in geoms or a filter to react to it.

```php
->dropdown(string $name, array $options, mixed $value = null, ?string $label = null): Plot
```

A dropdown bound to a parameter.

```php
->param(string $name, mixed $value = null, ?array $bind = null, ?string $expr = null): Plot
```

A Vega-Lite parameter: a value, optionally bound to an input, or an expr.

```php
->select(array $param): Plot
```

A raw selection parameter, attached to the first layer.

### Escape hatches

```php
->transform(array ...$transforms): Plot
```

Vega-Lite transforms applied to the data before every layer.

```php
->raw(array $spec): Plot
```

Raw Vega-Lite merged into the top-level spec last (nested objects merge; lists and scalars replace).

```php
->apply(callable ...$fns): Plot
```

Applies a function to the plot: pipe-friendly composition of reusable plot fragments.

```php
->toArray(): array
```

```php
->toSpec(): array
```


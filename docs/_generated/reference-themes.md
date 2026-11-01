**Methods:** [`labs()`](#labs) · [`title()`](#title) · [`theme()`](#theme) · [`size()`](#size) · [`width()`](#width) · [`height()`](#height) · [`description()`](#description)

**Functions:** [`labs()`](#labs-function) · [`ggtitle()`](#ggtitle-function) · [`xlab()`](#xlab-function) · [`ylab()`](#ylab-function) · [`theme()`](#theme-function) · [`theme_gray()`](#theme_gray) · [`theme_grey()`](#theme_grey) · [`theme_bw()`](#theme_bw) · [`theme_minimal()`](#theme_minimal) · [`theme_classic()`](#theme_classic) · [`theme_light()`](#theme_light) · [`theme_dark()`](#theme_dark) · [`theme_void()`](#theme_void) · [`theme_linedraw()`](#theme_linedraw) · [`size()`](#size-function)

### `labs()` {#labs}

```php
->labs(mixed ...$labels): Plot
```

Titles: title, subtitle, caption, and one per aesthetic (x, y, color, fill, size, shape, ...). A facet field's name sets its panel header title. Null removes a title. The caption sits below the plot; theme() styles it.

Functions: [`labs()`]{#labs-function}, [`xlab()`](#xlab-function), [`ylab()`](#ylab-function).

### `title()` {#title}

```php
->title(?string $title, ?string $subtitle = null): Plot
```

The title and subtitle, as ggplot2's ggtitle(); null removes the title.

Function: [`ggtitle()`](#ggtitle-function).

### `theme()` {#theme}

```php
->theme(
    ?string $preset = null,
    int|float|null $baseSize = null,
    ?string $font = null,
    ?string $legendPosition = null,
    ?string $background = null,
    ?string $panelBackground = null,
    ?string $panelBorder = null,
    ?bool $grid = null,
    ?string $titleAnchor = null,
    int|float|null $padding = null,
    int|float|null $xLabelAngle = null,
    int|float|null $yLabelAngle = null,
    ?string $legendDirection = null,
    ?bool $legendTitle = null,
    ?bool $axisLine = null,
    ?bool $ticks = null,
    ?string $gridColor = null,
    ?string $textColor = null,
    int|float|null $captionSize = null,
    ?string $captionColor = null,
    ?string $captionAnchor = null,
    array $config = [],
): Plot
```

A theme preset (gray, bw, linedraw, minimal, classic, light, dark, void, vega) and/or individual options; config is raw Vega-Lite config merged last. Successive calls build on each other. xLabelAngle and yLabelAngle turn axis labels anticlockwise, as element_text(angle =); legendTitle: false, axisLine, and ticks switch those parts. captionSize, captionColor, and captionAnchor (start, middle, or end) style labs(caption:).

Functions: [`theme()`]{#theme-function}, [`theme_gray()`](#theme_gray), [`theme_grey()`](#theme_grey), [`theme_bw()`](#theme_bw), [`theme_minimal()`](#theme_minimal), [`theme_classic()`](#theme_classic), [`theme_light()`](#theme_light), [`theme_dark()`](#theme_dark), [`theme_void()`](#theme_void), [`theme_linedraw()`](#theme_linedraw).

### `size()` {#size}

```php
->size(string|int|null $width = null, string|int|null $height = null): Plot
```

Width and height in pixels (per panel when faceted); 'container' fills the parent element's width.

Function: [`size()`](#size-function).

### `width()` {#width}

```php
->width(string|int $width): Plot
```

The width in pixels (per panel when faceted), or 'container' to fill the parent element.

### `height()` {#height}

```php
->height(string|int $height): Plot
```

The height in pixels (per panel when faceted), or 'container' to fill the parent element.

### `description()` {#description}

```php
->description(string $description): Plot
```

## Functions

### `ggtitle()` {#ggtitle-function}

```php
ggtitle(?string $title, ?string $subtitle = null): Closure
```

The pipe form of [`->title()`](#title).

### `xlab()` {#xlab-function}

```php
xlab(?string $title): Closure
```

The x axis title, as ggplot2's xlab(); null removes it.

### `ylab()` {#ylab-function}

```php
ylab(?string $title): Closure
```

The y axis title, as ggplot2's ylab(); null removes it.

### `theme_gray()` {#theme_gray}

```php
theme_gray(int|float $baseSize = 11): Closure
```

The gray preset, ggplot2's default theme; baseSize sets the base font size in points.

### `theme_grey()` {#theme_grey}

```php
theme_grey(int|float $baseSize = 11): Closure
```

An alias of theme_gray().

### `theme_bw()` {#theme_bw}

```php
theme_bw(int|float $baseSize = 11): Closure
```

The bw preset: a white panel with a dark border and gray grid lines.

### `theme_minimal()` {#theme_minimal}

```php
theme_minimal(int|float $baseSize = 11): Closure
```

The minimal preset: grid lines only, with no panel background or border.

### `theme_classic()` {#theme_classic}

```php
theme_classic(int|float $baseSize = 11): Closure
```

The classic preset: axis lines and no grid.

### `theme_light()` {#theme_light}

```php
theme_light(int|float $baseSize = 11): Closure
```

The light preset: light gray grid lines and a light gray border.

### `theme_dark()` {#theme_dark}

```php
theme_dark(int|float $baseSize = 11): Closure
```

The dark preset: a dark background and panel, with light text and grid lines.

### `theme_void()` {#theme_void}

```php
theme_void(int|float $baseSize = 11): Closure
```

The void preset: no axes, grid, or background.

### `theme_linedraw()` {#theme_linedraw}

```php
theme_linedraw(int|float $baseSize = 11): Closure
```

The linedraw preset: fine black grid lines and a black border.

### `size()` {#size-function}

```php
size(string|int|null $width = null, string|int|null $height = null): Closure
```

The pipe form of [`->size()`](#size).


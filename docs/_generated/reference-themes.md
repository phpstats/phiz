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

### `height()` {#height}

```php
->height(string|int $height): Plot
```

### `description()` {#description}

```php
->description(string $description): Plot
```

## Functions

### `ggtitle()` {#ggtitle-function}

```php
ggtitle(?string $title, ?string $subtitle = null): Closure
```

### `xlab()` {#xlab-function}

```php
xlab(?string $title): Closure
```

### `ylab()` {#ylab-function}

```php
ylab(?string $title): Closure
```

### `theme_gray()` {#theme_gray}

```php
theme_gray(int|float $baseSize = 11): Closure
```

### `theme_grey()` {#theme_grey}

```php
theme_grey(int|float $baseSize = 11): Closure
```

### `theme_bw()` {#theme_bw}

```php
theme_bw(int|float $baseSize = 11): Closure
```

### `theme_minimal()` {#theme_minimal}

```php
theme_minimal(int|float $baseSize = 11): Closure
```

### `theme_classic()` {#theme_classic}

```php
theme_classic(int|float $baseSize = 11): Closure
```

### `theme_light()` {#theme_light}

```php
theme_light(int|float $baseSize = 11): Closure
```

### `theme_dark()` {#theme_dark}

```php
theme_dark(int|float $baseSize = 11): Closure
```

### `theme_void()` {#theme_void}

```php
theme_void(int|float $baseSize = 11): Closure
```

### `theme_linedraw()` {#theme_linedraw}

```php
theme_linedraw(int|float $baseSize = 11): Closure
```

### `size()` {#size-function}

```php
size(string|int|null $width = null, string|int|null $height = null): Closure
```


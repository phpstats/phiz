## Basics

### Scatterplot {#scatter}

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Scatterplot"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/scatter.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'class')
    ->point()
    ->labs(
        x: 'Engine displacement (L)',
        y: 'Highway mpg',
        color: 'Class',
    );
```

### Linear fit with confidence band {#scatter-smooth}

One least-squares line and 95% confidence band per group, computed in PHP.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Linear fit with confidence band"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/scatter-smooth.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm')
    ->labs(
        x: 'Displacement (L)',
        y: 'Highway mpg',
        color: 'Drive',
    );
```

### With ggplot2-style functions {#pipe}

ggplot2-style functions compose with apply(), or with the pipe operator on PHP 8.5.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="With ggplot2-style functions"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/pipe.json">Vega-Lite specification</a></div></div></div>
```

```php
ggplot($mpg, aes(x: 'displ', y: 'hwy'))->apply(
    geom_point(alpha: 0.4),
    geom_smooth(span: 0.5),
    labs(
        title: 'Loess smooth',
        x: 'Displacement (L)',
        y: 'Highway mpg',
    ),
    theme_minimal(),
);
```

### Time series {#line}

ISO dates are read as temporal fields.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Time series"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/line.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($economics)
    ->aes(x: 'date', y: 'unemploy')
    ->line(color: '#1F77B4')
    ->labs(
        title: 'US unemployment',
        x: null,
        y: 'Unemployed (thousands)',
    );
```

### Stacked area {#area}

Made-up monthly orders by channel. Areas stack by fill, so the top edge is the total.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Stacked area"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/area.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of(array_merge(...array_map(
    fn ($month) => [
        ['month' => $month, 'channel' => 'Web',
            'orders' => 400 + 12 * $month],
        ['month' => $month, 'channel' => 'App',
            'orders' => 150 + 20 * $month],
        ['month' => $month, 'channel' => 'Store',
            'orders' => 300 - 6 * $month],
    ],
    range(1, 24),
)))
    ->aes(x: 'month', y: 'orders', fill: 'channel')
    ->area(alpha: 0.8)
    ->labs(x: 'Month', y: 'Orders', fill: 'Channel');
```

### Labels {#text}

nudgeY moves each label above its point.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Labels"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/text.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of(array_slice($mtcars, 0, 12))
    ->aes(x: 'wt', y: 'mpg', label: 'model')
    ->point()
    ->text(nudgeY: 8, size: 9)
    ->labs(x: 'Weight (1000 lbs)', y: 'mpg');
```

## Distributions

### Histogram {#histogram}

bins sets the maximum number of bins.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Histogram"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/histogram.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($faithful)
    ->aes(x: 'waiting')
    ->histogram(bins: 30, fill: '#4C78A8')
    ->labs(x: 'Minutes between eruptions', y: 'Count');
```

### Histogram with density {#histogram-density}

density: true scales the bars to unit area, so a density curve can overlay them.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Histogram with density"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/histogram-density.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($faithful)
    ->aes(x: 'eruptions')
    ->histogram(
        binwidth: 0.25,
        density: true,
        fill: '#DDDDDD',
        color: '#999999',
    )
    ->density(color: '#D62728', linewidth: 2)
    ->labs(x: 'Eruption length (minutes)');
```

### Density by group {#density}

Computed in the browser by Vega-Lite's density transform.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Density by group"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/density.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'hwy', fill: 'drv')
    ->density(alpha: 0.4)
    ->labs(x: 'Highway mpg', fill: 'Drive');
```

### Boxplots {#boxplot}

Tukey boxplots, flipped for readable labels.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Boxplots"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/boxplot.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', y: 'hwy', fill: 'class')
    ->boxplot()
    ->coordFlip()
    ->scaleFill(guide: false)
    ->labs(x: null, y: 'Highway mpg');
```

### Violins with boxplots {#violin}

In ggplot2's gray theme and hue palette.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Violins with boxplots"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/violin.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', y: 'hwy', fill: 'class')
    ->violin(alpha: 0.6)
    ->boxplot(width: 8, outliers: false)
    ->scaleFill(guide: false)
    ->theme('gray')
    ->labs(x: null, y: 'Highway mpg');
```

### Jittered strip plot {#jitter}

Each point moves randomly by up to 0.3 of a band.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Jittered strip plot"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/jitter.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: factor('cyl'), y: 'hwy', color: factor('cyl'))
    ->jitter(width: 0.3, alpha: 0.6)
    ->labs(x: 'Cylinders', y: 'Highway mpg')
    ->scaleColor(guide: false);
```

### Empirical CDF {#ecdf}

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Empirical CDF"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/ecdf.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'cty', color: 'drv')
    ->ecdf()
    ->labs(
        x: 'City mpg',
        y: 'Proportion ≤ x',
        color: 'Drive',
    );
```

### QQ plot {#qq}

Sample quantiles against normal quantiles, with the line through the quartiles.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="QQ plot"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/qq.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(sample: 'hwy')
    ->qq(alpha: 0.6)
    ->qqLine(color: '#D62728');
```

### 2D density {#density2d}

Density bands computed in PHP as MASS::kde2d does, with the points on top.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="2D density"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/density2d.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($faithful)
    ->aes(x: 'eruptions', y: 'waiting')
    ->density2dFilled()
    ->point(size: 6, color: 'white', alpha: 0.5)
    ->labs(
        x: 'Eruption (minutes)',
        y: 'Waiting (minutes)',
        fill: 'density',
    );
```

### Confidence ellipses {#ellipse}

Multivariate t ellipses per group, as stat_ellipse() draws by default.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Confidence ellipses"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/ellipse.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point(alpha: 0.5)
    ->ellipse()
    ->labs(
        x: 'Displacement (L)',
        y: 'Highway mpg',
        color: 'Drive',
    );
```

## Counts

### Bar chart of counts {#bar}

bar() counts rows when y is unmapped.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Bar chart of counts"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/bar.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class')
    ->bar(fill: '#4C78A8')
    ->labs(x: null, y: 'Cars');
```

### Dodged bars {#bar-dodge}

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Dodged bars"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/bar-dodge.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', fill: 'drv')
    ->bar(position: 'dodge')
    ->labs(x: null, y: 'Cars', fill: 'Drive');
```

### Proportions {#bar-fill}

position: fill stacks each bar to 100%.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Proportions"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/bar-fill.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', fill: 'drv')
    ->bar(position: 'fill')
    ->scaleY(format: '%')
    ->labs(x: null, y: 'Share of cars', fill: 'Drive');
```

### Bars of values {#col}

col() draws the y values, here an aggregate.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Bars of values"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/col.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'manufacturer', y: 'mean(hwy)')
    ->col(fill: '#72B7B2')
    ->coordFlip()
    ->labs(x: null, y: 'Mean highway mpg');
```

### Heatmap {#tile}

Each tile is filled by the mean of its rows.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Heatmap"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/tile.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', y: factor('cyl'), fill: 'mean(hwy)')
    ->tile(color: 'white')
    ->scaleFill(scheme: 'viridis')
    ->labs(x: null, y: 'Cylinders', fill: 'Mean hwy');
```

### 2D histogram {#bin2d}

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="2D histogram"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/bin2d.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($faithful)
    ->aes(x: 'eruptions', y: 'waiting')
    ->bin2d(bins: 20)
    ->scaleFill(scheme: 'blues');
```

### Donut {#pie}

innerRadius cuts out the center of a pie.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Donut"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/pie.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(fill: 'drv')
    ->pie(innerRadius: 50)
    ->labs(fill: 'Drive');
```

### Polar bars {#rose}

coordPolar() turns bars into a rose.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Polar bars"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/rose.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', fill: 'class')
    ->bar()
    ->coordPolar()
    ->labs(fill: null);
```

### Annotations {#annotate}

annotate() adds shaded ranges, segments, and text.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Annotations"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/annotate.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'cty', y: 'hwy')
    ->annotate(
        'rect',
        xmin: 15, xmax: 20, ymin: -INF, ymax: INF,
        fill: 'orange', alpha: 0.2,
    )
    ->count(alpha: 0.6)
    ->annotate(
        'text',
        x: 30, y: 23, label: 'Most efficient',
        color: '#D62728',
    )
    ->labs(x: 'City mpg', y: 'Highway mpg');
```

## Facets

### Small multiples {#facet-wrap}

facetWrap() with a fitted line in every panel.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Small multiples"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/facet-wrap.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy')
    ->point(alpha: 0.5)
    ->smooth(method: 'lm')
    ->facetWrap('class', ncol: 4)
    ->size(150, 120);
```

### Grid of panels {#facet-grid}

Rows by drive, columns by year.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Grid of panels"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/facet-grid.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'cty', y: 'hwy')
    ->point(alpha: 0.5)
    ->abline(0, 1, linetype: 'dashed', color: '#888888')
    ->facetGrid(rows: 'drv', cols: 'year')
    ->size(270, 140);
```

### Side by side {#compose}

Compose::beside() lays plots out like patchwork.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Side by side"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/compose.json">Vega-Lite specification</a></div></div></div>
```

```php
Compose::beside(
    Plot::of($faithful)
        ->aes(x: 'eruptions', y: 'waiting')
        ->point(alpha: 0.5)
        ->size(420, 260),
    Plot::of($faithful)
        ->aes(x: 'waiting')
        ->histogram(bins: 20)
        ->coordFlip()
        ->size(180, 260),
)->title('Old Faithful');
```

## Scales and themes

### Log scale {#log-scale}

scaleX(type: 'log') with chosen breaks.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Log scale"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/log-scale.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($economics)
    ->aes(x: 'pce', y: 'psavert')
    ->point(alpha: 0.4)
    ->scaleX(
        type: 'log',
        breaks: [500, 1000, 2000, 5000, 10000],
        name: 'Personal consumption ($bn, log)',
    )
    ->labs(y: 'Savings rate (%)');
```

### Manual colors {#manual-colors}

A map from each level to its color.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Manual colors"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/manual-colors.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point()
    ->scaleColor(
        values: [
            '4' => '#D95F02',
            'f' => '#1B9E77',
            'r' => '#7570B3',
        ],
        name: 'Drive',
    );
```

### Continuous color {#gradient}

A viridis gradient for a numeric field.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Continuous color"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/gradient.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'cty')
    ->point(size: 50)
    ->scaleColor(scheme: 'viridis');
```

### theme("gray") {#theme-gray}

ggplot2's default look.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="theme(&quot;gray&quot;)"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/theme-gray.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point()
    ->theme('gray');
```

### theme("classic", baseSize: 13) {#theme-classic}

Axis lines, no grid, larger text.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="theme(&quot;classic&quot;, baseSize: 13)"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/theme-classic.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy')
    ->point()
    ->theme('classic', baseSize: 13);
```

### theme("dark") {#theme-dark}

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="theme(&quot;dark&quot;)"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/theme-dark.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($economics)
    ->aes(x: 'date', y: 'psavert')
    ->line(color: '#F58518')
    ->theme('dark');
```

## Interaction

### Tooltips, pan and zoom {#tooltip-zoom}

Hover for values. Drag to pan and scroll to zoom.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Tooltips, pan and zoom"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/tooltip-zoom.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'class')
    ->point()
    ->tooltip([
        'manufacturer', 'model', 'year', 'displ', 'hwy',
    ])
    ->interactive();
```

### Linked brushing {#brush-linked}

Brush the scatterplot to filter the bars.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Linked brushing"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/brush-linked.json">Vega-Lite specification</a></div></div></div>
```

```php
Compose::stack(
    Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point()
        ->brush(encodings: ['x', 'y'])
        ->size(600, 240),
    Plot::of($mpg)
        ->aes(x: 'class')
        ->bar(filter: ['param' => 'brush'])
        ->size(600, 140),
);
```

### Legend highlight {#highlight}

Click a legend entry. Shift-click selects several.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Legend highlight"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/highlight.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy', color: 'drv')
    ->point(size: 40)
    ->highlight();
```

### Slider-driven reference line {#slider}

The slider moves the rule and recolors the points.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Slider-driven reference line"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/slider.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'displ', y: 'hwy')
    ->slider('cutoff', 15, 40, 1, 30, 'Highway mpg cutoff')
    ->point(filter: 'datum.hwy >= cutoff', color: '#D62728')
    ->point(filter: 'datum.hwy < cutoff', color: '#BBBBBB')
    ->hline(expr('cutoff'), linetype: 'dashed');
```

## Statistics

### Coefficient plot {#coefplot}

From a fit in the shape phit's fit_lm() returns.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Coefficient plot"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/coefplot.json">Vega-Lite specification</a></div></div></div>
```

```php
coefplot($fit)
    ->labs(title: 'mpg ~ wt + hp + am');
```

### Comparing models {#coefplot-compare}

Several fits, dodged.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Comparing models"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/coefplot-compare.json">Vega-Lite specification</a></div></div></div>
```

```php
coefplot([
    'Full' => $fit,
    'Without am' => $reduced,
]);
```

### Regression diagnostics {#diagnostics}

Residuals, QQ, scale-location, and observed against fitted.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Regression diagnostics"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/diagnostics.json">Vega-Lite specification</a></div></div></div>
```

```php
diagnostic_plots($fit, width: 290, height: 190);
```

### Predicted values {#effects}

Predicted mpg with 95% confidence bands as weight varies, by transmission.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Predicted values"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/effects.json">Vega-Lite specification</a></div></div></div>
```

```php
effect_plot(
    $fit,
    $mtcars,
    'wt',
    by: 'am',
    design: fn ($r) => [1, $r['wt'], $r['hp'], $r['am']],
)
    ->labs(
        x: 'Weight (1000 lbs)',
        y: 'mpg',
        color: 'Manual',
        fill: 'Manual',
    );
```

### Posterior intervals {#posterior-intervals}

From CmdStanPHP draws_columns(), as bayesplot's mcmc_intervals() draws them.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Posterior intervals"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/posterior-intervals.json">Vega-Lite specification</a></div></div></div>
```

```php
interval_plot($draws);
```

### Means with error bars {#errorbars}

Precomputed means with normal-approximation 95% intervals.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Means with error bars"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/errorbars.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of(array_map(
    function ($class) use ($mpg) {
        $rows = array_filter(
            $mpg,
            fn ($r) => $r['class'] === $class,
        );
        $values = array_column($rows, 'hwy');
        $n = count($values);
        $mean = array_sum($values) / $n;
        $squares = array_map(
            fn ($v) => ($v - $mean) ** 2,
            $values,
        );
        $se = sqrt(array_sum($squares) / ($n - 1) / $n);

        return [
            'class' => $class,
            'mean' => $mean,
            'lower' => $mean - 1.96 * $se,
            'upper' => $mean + 1.96 * $se,
        ];
    },
    array_values(array_unique(array_column($mpg, 'class'))),
))
    ->aes(x: 'class', y: 'mean', ymin: 'lower', ymax: 'upper')
    ->errorbar(width: 8)
    ->point(size: 50)
    ->labs(x: null, y: 'Mean highway mpg (95% CI)');
```

### Summaries {#summary}

mean_cl_normal draws means with t-based 95% intervals over the raw data.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Summaries"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/summary.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of($mpg)
    ->aes(x: 'class', y: 'hwy')
    ->point(alpha: 0.2)
    ->summary(funData: 'mean_cl_normal', color: '#D62728')
    ->labs(x: null, y: 'Highway mpg');
```

### Functions {#curve}

curve() draws a PHP function, here the normal density over a histogram.

```{=html}
<div class="phiz-figure"><div data-phiz-deferred data-phiz-options="{&quot;actions&quot;:false}" aria-label="Functions"><div data-phiz-view wire:ignore><a data-phiz-spec href="gallery/curve.json">Vega-Lite specification</a></div></div></div>
```

```php
Plot::of(array_map(
    fn ($i) => [
        'z' => Fit::normalQuantile(($i + 0.5) / 500),
    ],
    range(0, 499),
))
    ->aes(x: 'z')
    ->histogram(
        binwidth: 0.25,
        density: true,
        fill: '#DDDDDD',
    )
    ->curve(
        fn (float $x) => exp(-$x * $x / 2) / sqrt(2 * M_PI),
        -3.5,
        3.5,
        aes: aes(x: 'z', y: 'density'),
        color: '#D62728',
        linewidth: 2,
    );
```


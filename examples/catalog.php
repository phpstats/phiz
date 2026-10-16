<?php

declare(strict_types=1);

/*
 * The phit example catalog: id => [section, title, description, build]. Each build returns a Plot or Compose built
 * from the bundled datasets. examples/gallery.php renders them to one HTML page; the tests validate every spec and,
 * when vl-convert is available, render each to SVG. Example code shown in the gallery is the build function's body.
 */

use Phit\Compose;
use Phit\Datasets;
use Phit\Plot;
use Phit\Stats\Fit;

use function Phit\aes;
use function Phit\coefplot;
use function Phit\diagnostic_plots;
use function Phit\effect_plot;
use function Phit\expr;
use function Phit\factor;
use function Phit\geom_point;
use function Phit\geom_smooth;
use function Phit\ggplot;
use function Phit\interval_plot;
use function Phit\labs;
use function Phit\theme_minimal;

$mpg = Datasets::mpg();
$mtcars = Datasets::mtcars();
$economics = Datasets::economics();
$faithful = Datasets::faithful();

// A model fit in the shape php-stats returns, computed here so the examples run without the extension.
$fit = (function () use ($mtcars) {
    $x = array_map(fn ($r) => [1, $r['wt'], $r['hp'], $r['am']], $mtcars);
    $fit = Fit::ols($x, array_column($mtcars, 'mpg'));
    $t = Fit::tQuantile(0.975, $fit['df_residual']);

    return $fit + [
        'columns' => ['(Intercept)', 'wt', 'hp', 'am'],
        'response' => 'mpg',
        't_statistics' => array_map(fn ($b, $s) => $b / $s, $fit['coefficients'], $fit['std_errors']),
        'conf_int' => array_map(fn ($b, $s) => [$b - $t * $s, $b + $t * $s], $fit['coefficients'], $fit['std_errors']),
    ];
})();
$reduced = (function () use ($mtcars) {
    $fit = Fit::ols(array_map(fn ($r) => [1, $r['wt'], $r['hp']], $mtcars), array_column($mtcars, 'mpg'));

    return $fit + ['columns' => ['(Intercept)', 'wt', 'hp'], 't_statistics' => []];
})();

// Posterior draws in the shape CmdStanPHP's draws_columns() returns.
$draws = (function () {
    $draws = [];
    $params = ['mu' => [1.2, 0.3], 'tau' => [0.6, 0.25], 'theta[1]' => [1.9, 0.7], 'theta[2]' => [0.8, 0.6], 'theta[3]' => [1.1, 0.5]];
    foreach ($params as $name => [$mean, $sd]) {
        for ($i = 0; $i < 400; $i++) {
            $draws[$name][] = $mean + $sd * Fit::normalQuantile(fmod(($i + 0.5) * 0.618034, 1.0));
        }
    }

    return $draws;
})();

return [
    // Basics.
    'scatter' => ['Basics', 'Scatterplot', 'Points with a color mapping.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'class')
        ->point()
        ->labs(x: 'Engine displacement (L)', y: 'Highway mpg', color: 'Class')],

    'scatter-smooth' => ['Basics', 'Linear fit with confidence band', 'lm fits are computed in PHP, with a 95% band, per group.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(alpha: 0.5)
        ->smooth(method: 'lm')
        ->labs(title: 'Bigger engines, fewer miles', x: 'Displacement (L)', y: 'Highway mpg', color: 'Drive')],

    'pipe' => ['Basics', 'With the pipe operator', 'ggplot2-style functions compose with PHP 8.5 pipes.', fn () => ggplot($mpg, aes(x: 'displ', y: 'hwy'))
        |> geom_point(alpha: 0.4)
        |> geom_smooth(span: 0.5)
        |> labs(title: 'Loess smooth', x: 'Displacement (L)', y: 'Highway mpg')
        |> theme_minimal()],

    'line' => ['Basics', 'Time series', 'ISO dates are read as temporal fields.', fn () => Plot::of($economics)
        ->aes(x: 'date', y: 'unemploy')
        ->line(color: '#1F77B4')
        ->labs(title: 'US unemployment', x: null, y: 'Unemployed (thousands)')],

    'area' => ['Basics', 'Stacked area', 'Areas stack by fill.', fn () => Plot::of(array_merge(...array_map(fn ($r) => [
        ['date' => $r['date'], 'series' => 'Unemployed', 'value' => $r['unemploy']],
        ['date' => $r['date'], 'series' => 'Median weeks unemployed (x1000)', 'value' => $r['uempmed'] * 1000],
    ], array_slice($economics, -120))))
        ->aes(x: 'date', y: 'value', fill: 'series')
        ->area(alpha: 0.8)
        ->theme(legendPosition: 'bottom')],

    'text' => ['Basics', 'Labels', 'Text labels with nudges.', fn () => Plot::of(array_slice($mtcars, 0, 12))
        ->aes(x: 'wt', y: 'mpg', label: 'model')
        ->point()
        ->text(nudgeY: 8, size: 9)
        ->labs(x: 'Weight (1000 lbs)', y: 'mpg')],

    // Distributions.
    'histogram' => ['Distributions', 'Histogram', 'Binned counts; bins sets the maximum number of bins.', fn () => Plot::of($faithful)
        ->aes(x: 'waiting')
        ->histogram(bins: 30, fill: '#4C78A8')
        ->labs(x: 'Minutes between eruptions', y: 'Count')],

    'histogram-density' => ['Distributions', 'Histogram with density', 'density: true scales bars to unit area, so a KDE overlays.', fn () => Plot::of($faithful)
        ->aes(x: 'eruptions')
        ->histogram(binwidth: 0.25, density: true, fill: '#DDDDDD', color: '#999999')
        ->density(color: '#D62728', linewidth: 2)
        ->labs(x: 'Eruption length (minutes)')],

    'density' => ['Distributions', 'Density by group', 'Kernel densities per group with Vega-Lite\'s density transform.', fn () => Plot::of($mpg)
        ->aes(x: 'hwy', fill: 'drv')
        ->density(alpha: 0.4)
        ->labs(x: 'Highway mpg', fill: 'Drive')],

    'boxplot' => ['Distributions', 'Boxplots', 'Tukey boxplots, flipped for readable labels.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: 'hwy', fill: 'class')
        ->boxplot()
        ->coordFlip()
        ->scaleFill(guide: false)
        ->labs(x: null, y: 'Highway mpg')],

    'violin' => ['Distributions', 'Violins with boxplots', 'Densities per level, layered with boxplots, in ggplot2\'s gray theme and hue palette.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: 'hwy', fill: 'class')
        ->violin(alpha: 0.6)
        ->boxplot(width: 8, outliers: false)
        ->scaleFill(guide: false)
        ->theme('gray')
        ->labs(x: null, y: 'Highway mpg')],

    'jitter' => ['Distributions', 'Jittered strip plot', 'Points spread within each band.', fn () => Plot::of($mpg)
        ->aes(x: factor('cyl'), y: 'hwy', color: factor('cyl'))
        ->jitter(width: 0.3, alpha: 0.6)
        ->labs(x: 'Cylinders', y: 'Highway mpg')
        ->scaleColor(guide: false)],

    'ecdf' => ['Distributions', 'Empirical CDF', 'Cumulative distributions per group.', fn () => Plot::of($mpg)
        ->aes(x: 'cty', color: 'drv')
        ->ecdf()
        ->labs(x: 'City mpg', y: 'Proportion ≤ x', color: 'Drive')],

    'qq' => ['Distributions', 'QQ plot', 'Sample quantiles against normal quantiles, with the quartile line.', fn () => Plot::of($mpg)
        ->aes(sample: 'hwy')
        ->qq(alpha: 0.6)
        ->qqLine(color: '#D62728')],

    'density2d' => ['Distributions', '2D density', 'Filled density bands from MASS::kde2d, computed in PHP, with the points on top.', fn () => Plot::of($faithful)
        ->aes(x: 'eruptions', y: 'waiting')
        ->density2dFilled()
        ->point(size: 6, color: 'white', alpha: 0.5)
        ->labs(x: 'Eruption (minutes)', y: 'Waiting (minutes)', fill: 'density')],

    'ellipse' => ['Distributions', 'Confidence ellipses', 'stat_ellipse: robust t ellipses per group.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(alpha: 0.5)
        ->ellipse()
        ->labs(x: 'Displacement (L)', y: 'Highway mpg', color: 'Drive')],

    // Counts and amounts.
    'bar' => ['Counts', 'Bar chart of counts', 'bar() counts rows when y is unmapped.', fn () => Plot::of($mpg)
        ->aes(x: 'class')
        ->bar(fill: '#4C78A8')
        ->labs(x: null, y: 'Cars')],

    'bar-dodge' => ['Counts', 'Dodged bars', 'Groups side by side.', fn () => Plot::of($mpg)
        ->aes(x: 'class', fill: 'drv')
        ->bar(position: 'dodge')
        ->labs(x: null, y: 'Cars', fill: 'Drive')],

    'bar-fill' => ['Counts', 'Proportions', 'position: fill stacks to 100%.', fn () => Plot::of($mpg)
        ->aes(x: 'class', fill: 'drv')
        ->bar(position: 'fill')
        ->scaleY(format: '%')
        ->labs(x: null, y: 'Share of cars', fill: 'Drive')],

    'col' => ['Counts', 'Bars of values', 'col() draws the y values, here an aggregate.', fn () => Plot::of($mpg)
        ->aes(x: 'manufacturer', y: 'mean(hwy)')
        ->col(fill: '#72B7B2')
        ->coordFlip()
        ->labs(x: null, y: 'Mean highway mpg')],

    'tile' => ['Counts', 'Heatmap', 'Tiles filled by an aggregate.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: factor('cyl'), fill: 'mean(hwy)')
        ->tile(color: 'white')
        ->scaleFill(scheme: 'viridis')
        ->labs(x: null, y: 'Cylinders', fill: 'Mean hwy')],

    'bin2d' => ['Counts', '2D histogram', 'Counts in x-by-y bins.', fn () => Plot::of($faithful)
        ->aes(x: 'eruptions', y: 'waiting')
        ->bin2d(bins: 20)
        ->scaleFill(scheme: 'blues')],

    'pie' => ['Counts', 'Donut', 'Arcs sized by counts.', fn () => Plot::of($mpg)
        ->aes(fill: 'drv')
        ->pie(innerRadius: 50)
        ->labs(fill: 'Drive')],

    'rose' => ['Counts', 'Polar bars', 'coordPolar() turns bars into a rose.', fn () => Plot::of($mpg)
        ->aes(x: 'class', fill: 'class')
        ->bar()
        ->coordPolar()
        ->labs(fill: null)],

    'annotate' => ['Counts', 'Annotations', 'annotate() adds shaded ranges, segments, and text.', fn () => Plot::of($mpg)
        ->aes(x: 'cty', y: 'hwy')
        ->annotate('rect', xmin: 15, xmax: 20, ymin: -INF, ymax: INF, fill: 'orange', alpha: 0.2)
        ->count(alpha: 0.6)
        ->annotate('text', x: 30, y: 23, label: 'Most efficient', color: '#D62728')
        ->labs(x: 'City mpg', y: 'Highway mpg')],

    // Facets and layout.
    'facet-wrap' => ['Facets', 'Small multiples', 'facetWrap with a fitted line in every panel.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy')
        ->point(alpha: 0.5)
        ->smooth(method: 'lm')
        ->facetWrap('class', ncol: 4)
        ->size(150, 120)],

    'facet-grid' => ['Facets', 'Grid of panels', 'Rows by drive, columns by year.', fn () => Plot::of($mpg)
        ->aes(x: 'cty', y: 'hwy')
        ->point(alpha: 0.5)
        ->abline(0, 1, linetype: 'dashed', color: '#888888')
        ->facetGrid(rows: 'drv', cols: 'year')
        ->size(200, 120)],

    'compose' => ['Facets', 'Side by side', 'Compose::beside lays plots out like patchwork.', fn () => Compose::beside(
        Plot::of($faithful)->aes(x: 'eruptions', y: 'waiting')->point(alpha: 0.5)->size(260, 220),
        Plot::of($faithful)->aes(x: 'waiting')->histogram(bins: 20)->coordFlip()->size(120, 220),
    )->title('Old Faithful')],

    // Scales and themes.
    'log-scale' => ['Scales and themes', 'Log scale', 'scaleX(type: log) with chosen breaks.', fn () => Plot::of($economics)
        ->aes(x: 'pce', y: 'psavert')
        ->point(alpha: 0.4)
        ->scaleX(type: 'log', breaks: [500, 1000, 2000, 5000, 10000], name: 'Personal consumption ($bn, log)')
        ->labs(y: 'Savings rate (%)')],

    'manual-colors' => ['Scales and themes', 'Manual colors', 'A level => color map.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point()
        ->scaleColor(values: ['4' => '#D95F02', 'f' => '#1B9E77', 'r' => '#7570B3'], name: 'Drive')],

    'gradient' => ['Scales and themes', 'Continuous color', 'A viridis gradient for a numeric field.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'cty')
        ->point(size: 50)
        ->scaleColor(scheme: 'viridis')],

    'theme-gray' => ['Scales and themes', 'theme("gray")', 'ggplot2\'s default look.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point()
        ->theme('gray')],

    'theme-classic' => ['Scales and themes', 'theme("classic", baseSize: 13)', 'Axis lines, no grid, larger text.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy')
        ->point()
        ->theme('classic', baseSize: 13)],

    'theme-dark' => ['Scales and themes', 'theme("dark")', 'A dark preset.', fn () => Plot::of($economics)
        ->aes(x: 'date', y: 'psavert')
        ->line(color: '#F58518')
        ->theme('dark')],

    // Interaction.
    'tooltip-zoom' => ['Interaction', 'Tooltips, pan and zoom', 'Hover for values; drag and scroll to explore.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'class')
        ->point()
        ->tooltip(['manufacturer', 'model', 'year', 'displ', 'hwy'])
        ->interactive()],

    'brush-linked' => ['Interaction', 'Linked brushing', 'Brush the scatterplot to filter the bars.', fn () => Compose::stack(
        Plot::of($mpg)->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->brush(encodings: ['x', 'y'])->size(420, 220),
        Plot::of($mpg)->aes(x: 'class')->bar(filter: ['param' => 'brush'])->size(420, 140),
    )],

    'highlight' => ['Interaction', 'Legend highlight', 'Click a legend entry.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(size: 40)
        ->highlight()],

    'slider' => ['Interaction', 'Slider-driven reference line', 'A parameter bound to a slider moves the rule and filters points.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy')
        ->slider('cutoff', 15, 40, 1, 30, 'Highway mpg cutoff')
        ->point(filter: 'datum.hwy >= cutoff', color: '#D62728')
        ->point(filter: 'datum.hwy < cutoff', color: '#BBBBBB')
        ->hline(expr('cutoff'), linetype: 'dashed')],

    // Statistics.
    'coefplot' => ['Statistics', 'Coefficient plot', 'Straight from a php-stats fit (stats_lm, stats_glm, ...).', fn () => coefplot($fit)
        ->labs(title: 'mpg ~ wt + hp + am')],

    'coefplot-compare' => ['Statistics', 'Comparing models', 'Several fits, dodged.', fn () => coefplot(['Full' => $fit, 'Without am' => $reduced])],

    'diagnostics' => ['Statistics', 'Regression diagnostics', 'Residuals, QQ, scale-location, observed vs fitted.', fn () => diagnostic_plots($fit, width: 230, height: 170)],

    'effects' => ['Statistics', 'Predicted values', 'Predictions with 95% bands as weight varies, by transmission.', fn () => effect_plot($fit, $mtcars, 'wt', by: 'am', design: fn ($r) => [1, $r['wt'], $r['hp'], $r['am']])
        ->labs(x: 'Weight (1000 lbs)', y: 'mpg', color: 'Manual', fill: 'Manual')],

    'posterior-intervals' => ['Statistics', 'Posterior intervals', 'From CmdStanPHP draws_columns(), as bayesplot\'s mcmc_intervals.', fn () => interval_plot($draws)],

    'errorbars' => ['Statistics', 'Means with error bars', 'Precomputed summaries with errorbar and point.', fn () => Plot::of(array_map(function ($class) use ($mpg) {
        $values = array_column(array_filter($mpg, fn ($r) => $r['class'] === $class), 'hwy');
        $mean = array_sum($values) / count($values);
        $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / (count($values) - 1));
        $se = $sd / sqrt(count($values));

        return ['class' => $class, 'mean' => $mean, 'lower' => $mean - 1.96 * $se, 'upper' => $mean + 1.96 * $se];
    }, array_values(array_unique(array_column($mpg, 'class')))))
        ->aes(x: 'class', y: 'mean', ymin: 'lower', ymax: 'upper')
        ->errorbar(width: 8)
        ->point(size: 50)
        ->labs(x: null, y: 'Mean highway mpg (95% CI)')],

    'summary' => ['Statistics', 'Summaries', 'stat_summary: means with t intervals over the raw data.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: 'hwy')
        ->point(alpha: 0.2)
        ->summary(funData: 'mean_cl_normal', color: '#D62728')
        ->labs(x: null, y: 'Highway mpg')],

    'curve' => ['Statistics', 'Functions', 'curve() draws a PHP function, here the normal density over a histogram.', fn () => Plot::of(array_map(fn ($i) => ['z' => Fit::normalQuantile(($i + 0.5) / 500)], range(0, 499)))
        ->aes(x: 'z')
        ->histogram(binwidth: 0.25, density: true, fill: '#DDDDDD')
        ->curve(fn (float $x) => exp(-$x * $x / 2) / sqrt(2 * M_PI), -3.5, 3.5, aes: aes(x: 'z', y: 'density'), color: '#D62728', linewidth: 2)],
];

<?php

declare(strict_types=1);

/*
 * The phiz example catalog: id => [section, title, description, build]. The description is empty when the title says
 * enough. Each build returns a Plot or Compose built from the bundled datasets, or from a small made-up dataset.
 * examples/gallery.php renders them to one HTML page; the tests validate every spec and, when vl-convert is
 * available, render each to SVG. Example code shown in the gallery is the build function's body.
 */

use Phiz\Compose;
use Phiz\Datasets;
use Phiz\Plot;
use Phiz\Stats\Fit;

use function Phiz\aes;
use function Phiz\coefplot;
use function Phiz\diagnostic_plots;
use function Phiz\effect_plot;
use function Phiz\expr;
use function Phiz\factor;
use function Phiz\geom_point;
use function Phiz\geom_smooth;
use function Phiz\ggplot;
use function Phiz\interval_plot;
use function Phiz\labs;
use function Phiz\theme_minimal;

$mpg = Datasets::mpg();
$mtcars = Datasets::mtcars();
$economics = Datasets::economics();
$faithful = Datasets::faithful();

// A model fit in the shape Phit\fit_lm() returns, computed here so the examples need neither phit nor its polyfill.
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
    'scatter' => ['Basics', 'Scatterplot', '', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'class')
        ->point()
        ->labs(
            x: 'Engine displacement (L)',
            y: 'Highway mpg',
            color: 'Class',
        )],

    'scatter-smooth' => ['Basics', 'Linear fit with confidence band', 'One least-squares line and 95% confidence band per group, computed in PHP.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(alpha: 0.5)
        ->smooth(method: 'lm')
        ->labs(
            x: 'Displacement (L)',
            y: 'Highway mpg',
            color: 'Drive',
        )],

    'pipe' => ['Basics', 'With ggplot2-style functions', 'ggplot2-style functions compose with apply(), or with the pipe operator on PHP 8.5.', fn () => ggplot($mpg, aes(x: 'displ', y: 'hwy'))->apply(
        geom_point(alpha: 0.4),
        geom_smooth(span: 0.5),
        labs(
            title: 'Loess smooth',
            x: 'Displacement (L)',
            y: 'Highway mpg',
        ),
        theme_minimal(),
    )],

    'line' => ['Basics', 'Time series', 'ISO dates are read as temporal fields.', fn () => Plot::of($economics)
        ->aes(x: 'date', y: 'unemploy')
        ->line(color: '#1F77B4')
        ->labs(
            title: 'US unemployment',
            x: null,
            y: 'Unemployed (thousands)',
        )],

    'area' => ['Basics', 'Stacked area', 'Made-up monthly orders by channel. Areas stack by fill, so the top edge is the total.', fn () => Plot::of(array_merge(...array_map(
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
        ->labs(x: 'Month', y: 'Orders', fill: 'Channel')],

    'text' => ['Basics', 'Labels', 'nudgeY moves each label above its point.', fn () => Plot::of(array_slice($mtcars, 0, 12))
        ->aes(x: 'wt', y: 'mpg', label: 'model')
        ->point()
        ->text(nudgeY: 8, size: 9)
        ->labs(x: 'Weight (1000 lbs)', y: 'mpg')],

    // Distributions.
    'histogram' => ['Distributions', 'Histogram', 'bins sets the maximum number of bins.', fn () => Plot::of($faithful)
        ->aes(x: 'waiting')
        ->histogram(bins: 30, fill: '#4C78A8')
        ->labs(x: 'Minutes between eruptions', y: 'Count')],

    'histogram-density' => ['Distributions', 'Histogram with density', 'density: true scales the bars to unit area, so a density curve can overlay them.', fn () => Plot::of($faithful)
        ->aes(x: 'eruptions')
        ->histogram(
            binwidth: 0.25,
            density: true,
            fill: '#DDDDDD',
            color: '#999999',
        )
        ->density(color: '#D62728', linewidth: 2)
        ->labs(x: 'Eruption length (minutes)')],

    'density' => ['Distributions', 'Density by group', 'Computed in the browser by Vega-Lite\'s density transform.', fn () => Plot::of($mpg)
        ->aes(x: 'hwy', fill: 'drv')
        ->density(alpha: 0.4)
        ->labs(x: 'Highway mpg', fill: 'Drive')],

    'boxplot' => ['Distributions', 'Boxplots', 'Tukey boxplots, flipped for readable labels.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: 'hwy', fill: 'class')
        ->boxplot()
        ->coordFlip()
        ->scaleFill(guide: false)
        ->labs(x: null, y: 'Highway mpg')],

    'violin' => ['Distributions', 'Violins with boxplots', 'In ggplot2\'s gray theme and hue palette.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: 'hwy', fill: 'class')
        ->violin(alpha: 0.6)
        ->boxplot(width: 8, outliers: false)
        ->scaleFill(guide: false)
        ->theme('gray')
        ->labs(x: null, y: 'Highway mpg')],

    'jitter' => ['Distributions', 'Jittered strip plot', 'Each point moves randomly by up to 0.3 of a band.', fn () => Plot::of($mpg)
        ->aes(x: factor('cyl'), y: 'hwy', color: factor('cyl'))
        ->jitter(width: 0.3, alpha: 0.6)
        ->labs(x: 'Cylinders', y: 'Highway mpg')
        ->scaleColor(guide: false)],

    'ecdf' => ['Distributions', 'Empirical CDF', '', fn () => Plot::of($mpg)
        ->aes(x: 'cty', color: 'drv')
        ->ecdf()
        ->labs(
            x: 'City mpg',
            y: 'Proportion ≤ x',
            color: 'Drive',
        )],

    'qq' => ['Distributions', 'QQ plot', 'Sample quantiles against normal quantiles, with the line through the quartiles.', fn () => Plot::of($mpg)
        ->aes(sample: 'hwy')
        ->qq(alpha: 0.6)
        ->qqLine(color: '#D62728')],

    'density2d' => ['Distributions', '2D density', 'Density bands computed in PHP as MASS::kde2d does, with the points on top.', fn () => Plot::of($faithful)
        ->aes(x: 'eruptions', y: 'waiting')
        ->density2dFilled()
        ->point(size: 6, color: 'white', alpha: 0.5)
        ->labs(
            x: 'Eruption (minutes)',
            y: 'Waiting (minutes)',
            fill: 'density',
        )],

    'ellipse' => ['Distributions', 'Confidence ellipses', 'Multivariate t ellipses per group, as stat_ellipse() draws by default.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(alpha: 0.5)
        ->ellipse()
        ->labs(
            x: 'Displacement (L)',
            y: 'Highway mpg',
            color: 'Drive',
        )],

    // Counts and amounts.
    'bar' => ['Counts', 'Bar chart of counts', 'bar() counts rows when y is unmapped.', fn () => Plot::of($mpg)
        ->aes(x: 'class')
        ->bar(fill: '#4C78A8')
        ->labs(x: null, y: 'Cars')],

    'bar-dodge' => ['Counts', 'Dodged bars', '', fn () => Plot::of($mpg)
        ->aes(x: 'class', fill: 'drv')
        ->bar(position: 'dodge')
        ->labs(x: null, y: 'Cars', fill: 'Drive')],

    'bar-fill' => ['Counts', 'Proportions', 'position: fill stacks each bar to 100%.', fn () => Plot::of($mpg)
        ->aes(x: 'class', fill: 'drv')
        ->bar(position: 'fill')
        ->scaleY(format: '%')
        ->labs(x: null, y: 'Share of cars', fill: 'Drive')],

    'col' => ['Counts', 'Bars of values', 'col() draws the y values, here an aggregate.', fn () => Plot::of($mpg)
        ->aes(x: 'manufacturer', y: 'mean(hwy)')
        ->col(fill: '#72B7B2')
        ->coordFlip()
        ->labs(x: null, y: 'Mean highway mpg')],

    'tile' => ['Counts', 'Heatmap', 'Each tile is filled by the mean of its rows.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: factor('cyl'), fill: 'mean(hwy)')
        ->tile(color: 'white')
        ->scaleFill(scheme: 'viridis')
        ->labs(x: null, y: 'Cylinders', fill: 'Mean hwy')],

    'bin2d' => ['Counts', '2D histogram', '', fn () => Plot::of($faithful)
        ->aes(x: 'eruptions', y: 'waiting')
        ->bin2d(bins: 20)
        ->scaleFill(scheme: 'blues')],

    'pie' => ['Counts', 'Donut', 'innerRadius cuts out the center of a pie.', fn () => Plot::of($mpg)
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
        ->labs(x: 'City mpg', y: 'Highway mpg')],

    // Facets and layout.
    'facet-wrap' => ['Facets', 'Small multiples', 'facetWrap() with a fitted line in every panel.', fn () => Plot::of($mpg)
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
        ->size(270, 140)],

    'compose' => ['Facets', 'Side by side', 'Compose::beside() lays plots out like patchwork.', fn () => Compose::beside(
        Plot::of($faithful)
            ->aes(x: 'eruptions', y: 'waiting')
            ->point(alpha: 0.5)
            ->size(420, 260),
        Plot::of($faithful)
            ->aes(x: 'waiting')
            ->histogram(bins: 20)
            ->coordFlip()
            ->size(180, 260),
    )->title('Old Faithful')],

    // Scales and themes.
    'log-scale' => ['Scales and themes', 'Log scale', 'scaleX(type: \'log\') with chosen breaks.', fn () => Plot::of($economics)
        ->aes(x: 'pce', y: 'psavert')
        ->point(alpha: 0.4)
        ->scaleX(
            type: 'log',
            breaks: [500, 1000, 2000, 5000, 10000],
            name: 'Personal consumption ($bn, log)',
        )
        ->labs(y: 'Savings rate (%)')],

    'manual-colors' => ['Scales and themes', 'Manual colors', 'A map from each level to its color.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point()
        ->scaleColor(
            values: [
                '4' => '#D95F02',
                'f' => '#1B9E77',
                'r' => '#7570B3',
            ],
            name: 'Drive',
        )],

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

    'theme-dark' => ['Scales and themes', 'theme("dark")', '', fn () => Plot::of($economics)
        ->aes(x: 'date', y: 'psavert')
        ->line(color: '#F58518')
        ->theme('dark')],

    // Interaction.
    'tooltip-zoom' => ['Interaction', 'Tooltips, pan and zoom', 'Hover for values. Drag to pan and scroll to zoom.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'class')
        ->point()
        ->tooltip([
            'manufacturer', 'model', 'year', 'displ', 'hwy',
        ])
        ->interactive()],

    'brush-linked' => ['Interaction', 'Linked brushing', 'Brush the scatterplot to filter the bars.', fn () => Compose::stack(
        Plot::of($mpg)
            ->aes(x: 'displ', y: 'hwy', color: 'drv')
            ->point()
            ->brush(encodings: ['x', 'y'])
            ->size(600, 240),
        Plot::of($mpg)
            ->aes(x: 'class')
            ->bar(filter: ['param' => 'brush'])
            ->size(600, 140),
    )],

    'highlight' => ['Interaction', 'Legend highlight', 'Click a legend entry. Shift-click selects several.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy', color: 'drv')
        ->point(size: 40)
        ->highlight()],

    'slider' => ['Interaction', 'Slider-driven reference line', 'The slider moves the rule and recolors the points.', fn () => Plot::of($mpg)
        ->aes(x: 'displ', y: 'hwy')
        ->slider('cutoff', 15, 40, 1, 30, 'Highway mpg cutoff')
        ->point(filter: 'datum.hwy >= cutoff', color: '#D62728')
        ->point(filter: 'datum.hwy < cutoff', color: '#BBBBBB')
        ->hline(expr('cutoff'), linetype: 'dashed')],

    // Statistics.
    'coefplot' => ['Statistics', 'Coefficient plot', 'From a fit in the shape phit\'s fit_lm() returns.', fn () => coefplot($fit)
        ->labs(title: 'mpg ~ wt + hp + am')],

    'coefplot-compare' => ['Statistics', 'Comparing models', 'Several fits, dodged.', fn () => coefplot([
        'Full' => $fit,
        'Without am' => $reduced,
    ])],

    'diagnostics' => ['Statistics', 'Regression diagnostics', 'Residuals, QQ, scale-location, and observed against fitted.', fn () => diagnostic_plots($fit, width: 290, height: 190)],

    'effects' => ['Statistics', 'Predicted values', 'Predicted mpg with 95% confidence bands as weight varies, by transmission.', fn () => effect_plot(
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
        )],

    'posterior-intervals' => ['Statistics', 'Posterior intervals', 'From CmdStanPHP draws_columns(), as bayesplot\'s mcmc_intervals() draws them.', fn () => interval_plot($draws)],

    'errorbars' => ['Statistics', 'Means with error bars', 'Precomputed means with normal-approximation 95% intervals.', fn () => Plot::of(array_map(
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
        ->labs(x: null, y: 'Mean highway mpg (95% CI)')],

    'summary' => ['Statistics', 'Summaries', 'mean_cl_normal draws means with t-based 95% intervals over the raw data.', fn () => Plot::of($mpg)
        ->aes(x: 'class', y: 'hwy')
        ->point(alpha: 0.2)
        ->summary(funData: 'mean_cl_normal', color: '#D62728')
        ->labs(x: null, y: 'Highway mpg')],

    'curve' => ['Statistics', 'Functions', 'curve() draws a PHP function, here the normal density over a histogram.', fn () => Plot::of(array_map(
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
        )],
];

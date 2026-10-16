<?php

declare(strict_types=1);

/*
 * Pipe-friendly functions. ggplot() starts a plot; the geom_*, scale_*, facet_*, coord_*, labs and theme functions
 * return closures that take a plot and return the next one, so they compose with PHP's pipe operator:
 *
 *     ggplot($mpg, aes(x: 'displ', y: 'hwy'))
 *         |> geom_point(alpha: 0.6)
 *         |> geom_smooth(method: 'lm')
 *         |> labs(x: 'Displacement (L)', y: 'Highway mpg');
 *
 * Each closure forwards its arguments to the Plot method of the same name.
 */

namespace Phit;

use Closure;

/** Aesthetic mappings: aes(x: 'displ', y: 'hwy', color: 'class'). */
function aes(mixed ...$mappings): Aes
{
    return Aes::of(...$mappings);
}

/** A plot of data with optional mappings. */
function ggplot(mixed $data = null, Aes|array|null $mapping = null): Plot
{
    return Plot::of($data, $mapping);
}

/** A field with an explicit type or other Vega-Lite field-definition properties. */
function field(string $name, ?string $type = null, mixed ...$properties): Field
{
    return Field::of($name)->with(...($type === null ? [] : ['type' => $type]) + $properties);
}

/** A field treated as discrete categories, like R's factor(cyl). Levels set the order. */
function factor(string $name, ?array $levels = null): Field
{
    return new Field($name, 'nominal', sort: $levels);
}

/** A field computed from a Vega expression: calc('datum.hwy - datum.cty', as: 'gap'). */
function calc(string $expression, string $as, ?string $type = null): Field
{
    return Field::calc($expression, $as, $type);
}

/** A Vega expression, such as a slider parameter's name. */
function expr(string $expression): Expr
{
    return new Expr($expression);
}

/** @internal Builds the closure for a Plot method. */
function chain(string $method, array $args): Closure
{
    return static fn (Plot $plot): Plot => $plot->{$method}(...$args);
}

function geom_point(mixed ...$args): Closure { return chain('point', $args); }
function geom_jitter(mixed ...$args): Closure { return chain('jitter', $args); }
function geom_line(mixed ...$args): Closure { return chain('line', $args); }
function geom_path(mixed ...$args): Closure { return chain('path', $args); }
function geom_step(mixed ...$args): Closure { return chain('step', $args); }
function geom_area(mixed ...$args): Closure { return chain('area', $args); }
function geom_ribbon(mixed ...$args): Closure { return chain('ribbon', $args); }
function geom_bar(mixed ...$args): Closure { return chain('bar', $args); }
function geom_col(mixed ...$args): Closure { return chain('col', $args); }
function geom_histogram(mixed ...$args): Closure { return chain('histogram', $args); }
function geom_freqpoly(mixed ...$args): Closure { return chain('freqpoly', $args); }
function geom_density(mixed ...$args): Closure { return chain('density', $args); }
function geom_boxplot(mixed ...$args): Closure { return chain('boxplot', $args); }
function geom_violin(mixed ...$args): Closure { return chain('violin', $args); }
function geom_errorbar(mixed ...$args): Closure { return chain('errorbar', $args); }
function geom_linerange(mixed ...$args): Closure { return chain('linerange', $args); }
function geom_pointrange(mixed ...$args): Closure { return chain('pointrange', $args); }
function geom_hline(mixed ...$args): Closure { return chain('hline', $args); }
function geom_vline(mixed ...$args): Closure { return chain('vline', $args); }
function geom_abline(mixed ...$args): Closure { return chain('abline', $args); }
function geom_rule(mixed ...$args): Closure { return chain('rule', $args); }
function geom_segment(mixed ...$args): Closure { return chain('segment', $args); }
function geom_text(mixed ...$args): Closure { return chain('text', $args); }
function geom_label(mixed ...$args): Closure { return chain('label', $args); }
function geom_tile(mixed ...$args): Closure { return chain('tile', $args); }
function geom_raster(mixed ...$args): Closure { return chain('raster', $args); }
function geom_rect(mixed ...$args): Closure { return chain('rect', $args); }
function geom_bin2d(mixed ...$args): Closure { return chain('bin2d', $args); }
function geom_hex(mixed ...$args): Closure { return chain('hex', $args); }
function geom_smooth(mixed ...$args): Closure { return chain('smooth', $args); }
function geom_function(mixed ...$args): Closure { return chain('curve', $args); }
function geom_rug(mixed ...$args): Closure { return chain('rug', $args); }
function stat_ecdf(mixed ...$args): Closure { return chain('ecdf', $args); }
function stat_qq(mixed ...$args): Closure { return chain('qq', $args); }
function stat_qq_line(mixed ...$args): Closure { return chain('qqLine', $args); }
function geom_arc(mixed ...$args): Closure { return chain('arc', $args); }
function geom_polygon(mixed ...$args): Closure { return chain('polygon', $args); }
function stat_summary(mixed ...$args): Closure { return chain('summary', $args); }
function geom_crossbar(mixed ...$args): Closure { return chain('crossbar', $args); }
function geom_count(mixed ...$args): Closure { return chain('count', $args); }
function stat_ellipse(mixed ...$args): Closure { return chain('ellipse', $args); }
function annotate(string $geom, mixed ...$args): Closure { return chain('annotate', [$geom, ...$args]); }
function geom_density_2d(mixed ...$args): Closure { return chain('density2d', $args); }
function stat_density_2d(mixed ...$args): Closure { return chain('density2d', $args); }
function geom_density_2d_filled(mixed ...$args): Closure { return chain('density2dFilled', $args); }
function stat_density_2d_filled(mixed ...$args): Closure { return chain('density2dFilled', $args); }
function geom_contour(mixed ...$args): Closure { return chain('contour', $args); }
function stat_contour(mixed ...$args): Closure { return chain('contour', $args); }
function geom_contour_filled(mixed ...$args): Closure { return chain('contourFilled', $args); }

function scale_x(mixed ...$args): Closure { return chain('scaleX', $args); }
function scale_y(mixed ...$args): Closure { return chain('scaleY', $args); }
function scale_x_continuous(mixed ...$args): Closure { return chain('scaleX', $args); }
function scale_y_continuous(mixed ...$args): Closure { return chain('scaleY', $args); }
function scale_x_log10(mixed ...$args): Closure { return chain('scaleX', ['type' => 'log'] + $args); }
function scale_y_log10(mixed ...$args): Closure { return chain('scaleY', ['type' => 'log'] + $args); }
function scale_x_sqrt(mixed ...$args): Closure { return chain('scaleX', ['type' => 'sqrt'] + $args); }
function scale_y_sqrt(mixed ...$args): Closure { return chain('scaleY', ['type' => 'sqrt'] + $args); }
function scale_x_reverse(mixed ...$args): Closure { return chain('scaleX', ['reverse' => true] + $args); }
function scale_y_reverse(mixed ...$args): Closure { return chain('scaleY', ['reverse' => true] + $args); }
function scale_x_date(mixed ...$args): Closure { return chain('scaleX', ['type' => 'time'] + $args); }
function scale_y_date(mixed ...$args): Closure { return chain('scaleY', ['type' => 'time'] + $args); }
function scale_color(mixed ...$args): Closure { return chain('scaleColor', $args); }
function scale_fill(mixed ...$args): Closure { return chain('scaleFill', $args); }
function scale_size(mixed ...$args): Closure { return chain('scaleSize', $args); }
function scale_shape(mixed ...$args): Closure { return chain('scaleShape', $args); }
function scale_alpha(mixed ...$args): Closure { return chain('scaleOpacity', $args); }
function scale_linetype(mixed ...$args): Closure { return chain('scaleLinetype', $args); }

/** Manual colors: a level => color map, or colors in level order. */
function scale_color_manual(array $values, mixed ...$args): Closure { return chain('scaleColor', ['values' => $values] + $args); }
function scale_fill_manual(array $values, mixed ...$args): Closure { return chain('scaleFill', ['values' => $values] + $args); }

/** Vega color schemes by name, such as 'viridis', 'blues', 'set2', 'dark2', 'tableau10'. */
function scale_color_scheme(string $scheme, mixed ...$args): Closure { return chain('scaleColor', ['scheme' => $scheme] + $args); }
function scale_fill_scheme(string $scheme, mixed ...$args): Closure { return chain('scaleFill', ['scheme' => $scheme] + $args); }
function scale_color_viridis(string $option = 'viridis', mixed ...$args): Closure { return chain('scaleColor', ['scheme' => $option] + $args); }
function scale_fill_viridis(string $option = 'viridis', mixed ...$args): Closure { return chain('scaleFill', ['scheme' => $option] + $args); }
function scale_color_brewer(string $palette = 'set1', mixed ...$args): Closure { return chain('scaleColor', ['scheme' => strtolower($palette)] + $args); }
function scale_fill_brewer(string $palette = 'set1', mixed ...$args): Closure { return chain('scaleFill', ['scheme' => strtolower($palette)] + $args); }

/** A two-color gradient, as ggplot2's scale_colour_gradient (defaults #132B43 to #56B1F7). */
function scale_color_gradient(string $low = '#132B43', string $high = '#56B1F7', mixed ...$args): Closure { return chain('scaleColor', ['range' => [$low, $high]] + $args); }
function scale_fill_gradient(string $low = '#132B43', string $high = '#56B1F7', mixed ...$args): Closure { return chain('scaleFill', ['range' => [$low, $high]] + $args); }

/** A diverging gradient through mid at midpoint, as ggplot2's scale_colour_gradient2. */
function scale_color_gradient2(string $low = '#832424', string $mid = '#FFFFFF', string $high = '#3A3A98', int|float $midpoint = 0, mixed ...$args): Closure { return chain('scaleColor', ['range' => [$low, $mid, $high], 'domainMid' => $midpoint] + $args); }
function scale_fill_gradient2(string $low = '#832424', string $mid = '#FFFFFF', string $high = '#3A3A98', int|float $midpoint = 0, mixed ...$args): Closure { return chain('scaleFill', ['range' => [$low, $mid, $high], 'domainMid' => $midpoint] + $args); }

/** viridisLite palettes by option: A magma, B inferno, C plasma, D viridis, E cividis, H turbo (or the names). */
function viridis_scheme(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1): array
{
    $name = match (strtoupper($option)) {
        'A', 'MAGMA' => 'magma', 'B', 'INFERNO' => 'inferno', 'C', 'PLASMA' => 'plasma', 'D', 'VIRIDIS' => 'viridis',
        'E', 'CIVIDIS' => 'cividis', 'H', 'TURBO' => 'turbo',
        default => throw new \InvalidArgumentException("Unknown viridis option '{$option}'; use A to E, H, or a palette name."),
    };

    return ['name' => $name] + ($begin == 0 && $end == 1 && $direction === 1 ? [] : ['extent' => $direction === -1 ? [$end, $begin] : [$begin, $end]]);
}

/** A ColorBrewer scheme, reversed when direction is -1. */
function brewer_scheme(string $palette, int $direction = 1): string|array
{
    $name = strtolower(str_replace(['-', ' '], '', $palette));

    return $direction === -1 ? ['name' => $name, 'extent' => [1, 0]] : $name;
}

/** viridis for discrete (_d), continuous (_c), and binned (_b) scales, as ggplot2's scale_colour_viridis_*. */
function scale_color_viridis_d(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1, mixed ...$args): Closure { return chain('scaleColor', ['scheme' => viridis_scheme($option, $begin, $end, $direction)] + $args); }
function scale_fill_viridis_d(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1, mixed ...$args): Closure { return chain('scaleFill', ['scheme' => viridis_scheme($option, $begin, $end, $direction)] + $args); }
function scale_color_viridis_c(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1, mixed ...$args): Closure { return chain('scaleColor', ['scheme' => viridis_scheme($option, $begin, $end, $direction)] + $args); }
function scale_fill_viridis_c(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1, mixed ...$args): Closure { return chain('scaleFill', ['scheme' => viridis_scheme($option, $begin, $end, $direction)] + $args); }
function scale_color_viridis_b(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1, mixed ...$args): Closure { return chain('scaleColor', ['type' => 'quantize', 'scheme' => viridis_scheme($option, $begin, $end, $direction)] + $args); }
function scale_fill_viridis_b(string $option = 'D', float $begin = 0.0, float $end = 1.0, int $direction = 1, mixed ...$args): Closure { return chain('scaleFill', ['type' => 'quantize', 'scheme' => viridis_scheme($option, $begin, $end, $direction)] + $args); }

/** ColorBrewer for continuous data, as ggplot2's scale_colour_distiller (reversed by default, as there). */
function scale_color_distiller(string $palette = 'Blues', int $direction = -1, mixed ...$args): Closure { return chain('scaleColor', ['scheme' => brewer_scheme($palette, $direction)] + $args); }
function scale_fill_distiller(string $palette = 'Blues', int $direction = -1, mixed ...$args): Closure { return chain('scaleFill', ['scheme' => brewer_scheme($palette, $direction)] + $args); }
/** ColorBrewer for binned continuous data, as ggplot2's scale_colour_fermenter. */
function scale_color_fermenter(string $palette = 'Blues', int $direction = -1, mixed ...$args): Closure { return chain('scaleColor', ['type' => 'quantize', 'scheme' => brewer_scheme($palette, $direction)] + $args); }
function scale_fill_fermenter(string $palette = 'Blues', int $direction = -1, mixed ...$args): Closure { return chain('scaleFill', ['type' => 'quantize', 'scheme' => brewer_scheme($palette, $direction)] + $args); }

/** A gradient through several colors, as ggplot2's scale_colour_gradientn. */
function scale_color_gradientn(array $colors, mixed ...$args): Closure { return chain('scaleColor', ['range' => array_values($colors)] + $args); }
function scale_fill_gradientn(array $colors, mixed ...$args): Closure { return chain('scaleFill', ['range' => array_values($colors)] + $args); }

/** Grays for discrete data, as ggplot2's scale_colour_grey (computed for the number of levels). */
function scale_color_grey(float $start = 0.2, float $end = 0.8, mixed ...$args): Closure { return chain('scaleColor', ['palette' => ['grey', $start, $end]] + $args); }
function scale_fill_grey(float $start = 0.2, float $end = 0.8, mixed ...$args): Closure { return chain('scaleFill', ['palette' => ['grey', $start, $end]] + $args); }

/** ggplot2's hue palette with its options, as scale_colour_hue (computed for the number of levels). */
function scale_color_hue(array $h = [15, 375], int|float $c = 100, int|float $l = 65, int|float $hStart = 0, int $direction = 1, mixed ...$args): Closure { return chain('scaleColor', ['palette' => ['hue', $h[0], $h[1], $c, $l, $hStart, $direction]] + $args); }
function scale_fill_hue(array $h = [15, 375], int|float $c = 100, int|float $l = 65, int|float $hStart = 0, int $direction = 1, mixed ...$args): Closure { return chain('scaleFill', ['palette' => ['hue', $h[0], $h[1], $c, $l, $hStart, $direction]] + $args); }

/** The data values are the colors (or sizes, or shapes) themselves, with no legend, as ggplot2's scale_*_identity. */
function scale_color_identity(mixed ...$args): Closure { return chain('scaleColor', ['identity' => true] + $args); }
function scale_fill_identity(mixed ...$args): Closure { return chain('scaleFill', ['identity' => true] + $args); }

/** Discrete position scales: limits orders (and subsets) the levels, labels renames them. */
function scale_x_discrete(?array $limits = null, mixed ...$args): Closure { return chain('scaleX', ($limits === null ? [] : ['domain' => array_values($limits)]) + $args); }
function scale_y_discrete(?array $limits = null, mixed ...$args): Closure { return chain('scaleY', ($limits === null ? [] : ['domain' => array_values($limits)]) + $args); }

/** Axis or legend options per aesthetic, as ggplot2's guides(): guides(color: 'none', x: guide_axis(angle: 45)). */
function guides(mixed ...$guides): Closure { return chain('guides', $guides); }
/** Legend options: title, columns (ncol), direction (horizontal or vertical), and further Vega-Lite legend properties. */
function guide_legend(?string $title = null, ?int $ncol = null, ?string $direction = null, mixed ...$properties): array { return array_filter(['title' => $title, 'columns' => $ncol, 'direction' => $direction], fn ($v) => $v !== null) + $properties; }
/** Color bar options: barwidth and barheight in pixels. */
function guide_colorbar(?string $title = null, int|float|null $barwidth = null, int|float|null $barheight = null, ?string $direction = null, mixed ...$properties): array { return array_filter(['title' => $title, 'gradientThickness' => $barwidth, 'gradientLength' => $barheight, 'direction' => $direction], fn ($v) => $v !== null) + $properties; }
function guide_colourbar(mixed ...$args): array { return guide_colorbar(...$args); }
/** Axis options: angle turns the labels anticlockwise, as ggplot2's guide_axis(angle =); position is top, bottom, left, or right. */
function guide_axis(?string $title = null, int|float|null $angle = null, ?string $position = null, mixed ...$properties): array
{
    $out = array_filter(['title' => $title, 'orient' => $position], fn ($v) => $v !== null);
    if ($angle !== null) {
        $out['labelAngle'] = -$angle;
        $out['labelAlign'] = $angle == 0 ? 'center' : ($angle > 0 ? 'right' : 'left');
        $out['labelBaseline'] = abs($angle) >= 90 ? 'middle' : 'top';
    }

    return $out + $properties;
}
function guide_none(): bool { return false; }

/** Number labels, as scales::label_number(): accuracy rounds, scale multiplies, prefix and suffix wrap. */
function label_number(int|float|null $accuracy = null, int|float $scale = 1, string $prefix = '', string $suffix = '', string $bigMark = ','): Label { return Label::number($accuracy, $scale, $prefix, $suffix, $bigMark); }
/** Thousands separators, as scales::label_comma(). */
function label_comma(int|float|null $accuracy = null, int|float $scale = 1, string $prefix = '', string $suffix = ''): Label { return Label::number($accuracy, $scale, $prefix, $suffix); }
/** Percentages of proportions, as scales::label_percent(): 0.25 becomes 25%. */
function label_percent(int|float|null $accuracy = null, int|float $scale = 100, string $prefix = '', string $suffix = '%'): Label { return Label::number($accuracy, $scale, $prefix, $suffix, ''); }
/** Currency, as scales::label_dollar(). */
function label_dollar(int|float|null $accuracy = null, int|float $scale = 1, string $prefix = '$', string $suffix = ''): Label { return Label::number($accuracy, $scale, $prefix, $suffix); }
/** Scientific notation with digits significant digits, as scales::label_scientific(). */
function label_scientific(int $digits = 3): Label { return new Label(format: '.'.max(0, $digits - 1).'e'); }
/** Dates and times with a strftime-style format, as scales::label_date(). */
function label_date(string $format = '%Y-%m-%d'): Label { return new Label(format: $format, formatType: 'time'); }

function xlim(int|float|string $min, int|float|string $max): Closure { return chain('xlim', [$min, $max]); }
function ylim(int|float|string $min, int|float|string $max): Closure { return chain('ylim', [$min, $max]); }
function coord_flip(): Closure { return chain('coordFlip', []); }
function coord_polar(mixed ...$args): Closure { return chain('coordPolar', $args); }
function coord_fixed(int|float $ratio = 1): Closure { return chain('coordFixed', [$ratio]); }
function coord_equal(int|float $ratio = 1): Closure { return chain('coordFixed', [$ratio]); }
function coord_cartesian(mixed ...$args): Closure { return chain('coordCartesian', $args); }
function facet_wrap(mixed ...$args): Closure { return chain('facetWrap', $args); }
function facet_grid(mixed ...$args): Closure { return chain('facetGrid', $args); }
function labs(mixed ...$args): Closure { return chain('labs', $args); }
function ggtitle(?string $title, ?string $subtitle = null): Closure { return chain('title', [$title, $subtitle]); }
function xlab(?string $title): Closure { return chain('labs', ['x' => $title]); }
function ylab(?string $title): Closure { return chain('labs', ['y' => $title]); }
function theme(mixed ...$args): Closure { return chain('theme', $args); }
function theme_gray(int|float $baseSize = 11): Closure { return chain('theme', ['gray', $baseSize]); }
function theme_grey(int|float $baseSize = 11): Closure { return chain('theme', ['gray', $baseSize]); }
function theme_bw(int|float $baseSize = 11): Closure { return chain('theme', ['bw', $baseSize]); }
function theme_minimal(int|float $baseSize = 11): Closure { return chain('theme', ['minimal', $baseSize]); }
function theme_classic(int|float $baseSize = 11): Closure { return chain('theme', ['classic', $baseSize]); }
function theme_light(int|float $baseSize = 11): Closure { return chain('theme', ['light', $baseSize]); }
function theme_dark(int|float $baseSize = 11): Closure { return chain('theme', ['dark', $baseSize]); }
function theme_void(int|float $baseSize = 11): Closure { return chain('theme', ['void', $baseSize]); }
function theme_linedraw(int|float $baseSize = 11): Closure { return chain('theme', ['linedraw', $baseSize]); }
function size(int|string|null $width = null, int|string|null $height = null): Closure { return chain('size', [$width, $height]); }
function tooltip(bool|array|string $fields = true): Closure { return chain('tooltip', [$fields]); }
function interactive(mixed ...$args): Closure { return chain('interactive', $args); }
function brush(mixed ...$args): Closure { return chain('brush', $args); }
function highlight(mixed ...$args): Closure { return chain('highlight', $args); }

// Statistics helpers: plain functions returning ordinary plots (see Phit\Stats\Charts).

/** Coefficient plot of one fit, or of several keyed by label (dodged side by side). */
function coefplot(array $fits, mixed ...$options): Plot { return Stats\Charts::coefficients($fits, ...$options); }
function residual_plot(array $fit): Plot { return Stats\Charts::residuals($fit); }
function qq_plot(array $fitOrValues, bool $standardize = false): Plot { return Stats\Charts::qq($fitOrValues, $standardize); }
function fitted_plot(array $fit, ?array $observed = null): Plot { return Stats\Charts::fitted($fit, $observed); }
function scale_location_plot(array $fit): Plot { return Stats\Charts::scaleLocation($fit); }
function diagnostic_plots(array $fit, mixed ...$options): Compose { return Stats\Charts::diagnostics($fit, ...$options); }
/** Predicted values with confidence bands as x varies (by levels of $by), other variables held at typical values. */
function effect_plot(array $fit, mixed $data, string $x, mixed ...$options): Plot { return Stats\Charts::effects($fit, $data, $x, ...$options); }
function predictions(array $fit, array $grid, mixed ...$options): array { return Stats\Charts::predictions($fit, $grid, ...$options); }
/** Posterior intervals from draws columns or a summary, as bayesplot's mcmc_intervals. */
function interval_plot(array $draws, mixed ...$options): Plot { return Stats\Charts::intervals($draws, ...$options); }
/** Estimated marginal means of a factor with confidence intervals, as emmeans. */
function marginal_means(array $fit, mixed $data, string $factor, mixed ...$options): Plot { return Stats\Charts::marginalMeans($fit, $data, $factor, ...$options); }
/** A caterpillar plot of a mixed model's random effects with conditional intervals, as lme4's dotplot(ranef()). */
function random_effects_plot(array $fit, mixed $data = null, mixed ...$options): Plot { return Stats\Charts::randomEffects($fit, $data, ...$options); }

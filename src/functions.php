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

namespace Phiz;

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
function geom_smooth(mixed ...$args): Closure { return chain('smooth', $args); }
function geom_function(mixed ...$args): Closure { return chain('curve', $args); }
function geom_rug(mixed ...$args): Closure { return chain('rug', $args); }
function stat_ecdf(mixed ...$args): Closure { return chain('ecdf', $args); }
function stat_qq(mixed ...$args): Closure { return chain('qq', $args); }
function stat_qq_line(mixed ...$args): Closure { return chain('qqLine', $args); }
function geom_arc(mixed ...$args): Closure { return chain('arc', $args); }

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

/** Manual colours: a level => colour map, or colours in level order. */
function scale_color_manual(array $values, mixed ...$args): Closure { return chain('scaleColor', ['values' => $values] + $args); }
function scale_fill_manual(array $values, mixed ...$args): Closure { return chain('scaleFill', ['values' => $values] + $args); }

/** Vega colour schemes by name, such as 'viridis', 'blues', 'set2', 'dark2', 'tableau10'. */
function scale_color_scheme(string $scheme, mixed ...$args): Closure { return chain('scaleColor', ['scheme' => $scheme] + $args); }
function scale_fill_scheme(string $scheme, mixed ...$args): Closure { return chain('scaleFill', ['scheme' => $scheme] + $args); }
function scale_color_viridis(string $option = 'viridis', mixed ...$args): Closure { return chain('scaleColor', ['scheme' => $option] + $args); }
function scale_fill_viridis(string $option = 'viridis', mixed ...$args): Closure { return chain('scaleFill', ['scheme' => $option] + $args); }
function scale_color_brewer(string $palette = 'set1', mixed ...$args): Closure { return chain('scaleColor', ['scheme' => strtolower($palette)] + $args); }
function scale_fill_brewer(string $palette = 'set1', mixed ...$args): Closure { return chain('scaleFill', ['scheme' => strtolower($palette)] + $args); }

/** A two-colour gradient, as ggplot2's scale_colour_gradient (defaults #132B43 to #56B1F7). */
function scale_color_gradient(string $low = '#132B43', string $high = '#56B1F7', mixed ...$args): Closure { return chain('scaleColor', ['range' => [$low, $high]] + $args); }
function scale_fill_gradient(string $low = '#132B43', string $high = '#56B1F7', mixed ...$args): Closure { return chain('scaleFill', ['range' => [$low, $high]] + $args); }

/** A diverging gradient through mid at midpoint, as ggplot2's scale_colour_gradient2. */
function scale_color_gradient2(string $low = '#832424', string $mid = '#FFFFFF', string $high = '#3A3A98', int|float $midpoint = 0, mixed ...$args): Closure { return chain('scaleColor', ['range' => [$low, $mid, $high], 'domainMid' => $midpoint] + $args); }
function scale_fill_gradient2(string $low = '#832424', string $mid = '#FFFFFF', string $high = '#3A3A98', int|float $midpoint = 0, mixed ...$args): Closure { return chain('scaleFill', ['range' => [$low, $mid, $high], 'domainMid' => $midpoint] + $args); }

function xlim(int|float|string $min, int|float|string $max): Closure { return chain('xlim', [$min, $max]); }
function ylim(int|float|string $min, int|float|string $max): Closure { return chain('ylim', [$min, $max]); }
function coord_flip(): Closure { return chain('coordFlip', []); }
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
function size(int|string|null $width = null, int|string|null $height = null): Closure { return chain('size', [$width, $height]); }
function tooltip(bool|array|string $fields = true): Closure { return chain('tooltip', [$fields]); }
function interactive(mixed ...$args): Closure { return chain('interactive', $args); }
function brush(mixed ...$args): Closure { return chain('brush', $args); }
function highlight(mixed ...$args): Closure { return chain('highlight', $args); }

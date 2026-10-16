<?php

declare(strict_types=1);

namespace Phiz;

use InvalidArgumentException;
use Phiz\Compile\Compiler;

/**
 * A ggplot2-style plot that compiles to Vega-Lite.
 *
 *     Plot::of($rentals)
 *         ->aes(x: 'area_m2', y: 'rent', color: 'neighborhood')
 *         ->point(alpha: 0.6)
 *         ->smooth(method: 'lm')
 *         ->facetWrap('bedrooms', ncol: 3)
 *         ->labs(title: 'Rent by floor area', x: 'Area (m²)', y: 'Monthly rent');
 *
 * Plots are immutable: every method returns a new plot, so a base plot can be shared and extended.
 *
 * Geoms take aes: (a layer's own mappings), data: (a layer's own data), inheritAes: (false ignores the plot's
 * mappings), filter: (a Vega expression or selection predicate), and fixed visual properties by their ggplot2 names
 * (alpha, color, fill, size, linewidth, linetype, shape) or as any Vega-Lite mark property (strokeWidth: 2).
 */
final class Plot extends Chart
{
    /**
     * @param list<Layer> $layers
     * @param array<string, Scale> $scales
     * @param array<string, string|null> $labels
     * @param array<string, mixed>|null $facet
     * @param array<string, array> $limits
     * @param list<array<string, mixed>> $params
     * @param list<array<string, mixed>> $conditions
     * @param list<array<string, mixed>> $transforms
     */
    private function __construct(
        public readonly ?Data $data = null,
        public readonly Aes $aes = new Aes,
        public readonly array $layers = [],
        public readonly array $scales = [],
        public readonly array $labels = [],
        public readonly ?array $facet = null,
        public readonly bool $flip = false,
        public readonly ?array $coord = null,
        public readonly array $limits = [],
        public readonly bool|array|null $tooltip = null,
        public readonly int|string|null $width = null,
        public readonly int|string|null $height = null,
        public readonly ?string $description = null,
        public readonly array $transforms = [],
        public readonly array $params = [],
        public readonly array $conditions = [],
        public readonly array $config = [],
        public readonly array $raw = [],
    ) {}

    /** A plot of rows, columns, a phibble Table, a Laravel Collection, or Data::url(...), with optional mappings. */
    public static function of(mixed $data = null, Aes|array|null $aes = null): self
    {
        return new self(data: $data === null ? null : Data::from($data), aes: Aes::from($aes));
    }

    // Mappings and data.

    /** Adds plot-wide aesthetic mappings: x, y, color, fill, size, shape, alpha, group, tooltip, label, ... */
    public function aes(mixed ...$mappings): self
    {
        if (count($mappings) === 1 && array_is_list($mappings) && $mappings[0] instanceof Aes) {
            return clone($this, ['aes' => $this->aes->merge($mappings[0])]);
        }

        return clone($this, ['aes' => $this->aes->merge(Aes::of(...$mappings))]);
    }

    public function data(mixed $data): self
    {
        return clone($this, ['data' => Data::from($data)]);
    }

    // Geoms.

    /** Scatterplot points. jitter: true (or a width, or [width, height]) displaces them randomly. */
    public function point(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, int|float|null $size = null, ?string $color = null, ?string $fill = null, string|int|null $shape = null, bool|int|float|array|null $jitter = null, ?string $position = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('point', $aes, $data, ['jitter' => $jitter, 'position' => $position], compact('alpha', 'size', 'color', 'fill', 'shape') + $style, $inheritAes, $filter);
    }

    /** Points jittered by up to width/height (fraction of a band on discrete axes, data units otherwise). */
    public function jitter(Aes|array|null $aes = null, mixed $data = null, int|float|null $width = null, int|float $height = 0, int|float|null $alpha = null, int|float|null $size = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('point', $aes, $data, ['jitter' => [$width, $height]], compact('alpha', 'size', 'color') + $style, $inheritAes, $filter);
    }

    /** Lines connecting points in order of x. downsample: n keeps about n points per group (LTTB), in PHP. */
    public function line(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, int|float|null $linewidth = null, ?string $color = null, string|array|null $linetype = null, ?int $downsample = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('line', $aes, $data, compact('downsample'), compact('alpha', 'linewidth', 'color', 'linetype') + $style, $inheritAes, $filter);
    }

    /** Lines connecting points in data order. */
    public function path(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, int|float|null $linewidth = null, ?string $color = null, string|array|null $linetype = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('path', $aes, $data, [], compact('alpha', 'linewidth', 'color', 'linetype') + $style, $inheritAes, $filter);
    }

    /** Stairsteps: direction 'hv' (horizontal first), 'vh', or 'mid'. */
    public function step(Aes|array|null $aes = null, mixed $data = null, string $direction = 'hv', int|float|null $linewidth = null, ?string $color = null, string|array|null $linetype = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('step', $aes, $data, ['direction' => $direction], compact('linewidth', 'color', 'linetype') + $style, $inheritAes, $filter);
    }

    /** Areas from zero, stacked by fill (position: stack, fill, identity). */
    public function area(Aes|array|null $aes = null, mixed $data = null, string $position = 'stack', int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('area', $aes, $data, ['position' => $position], compact('alpha', 'fill', 'color') + $style, $inheritAes, $filter);
    }

    /** A band between ymin and ymax (or xmin and xmax). */
    public function ribbon(Aes|array|null $aes = null, mixed $data = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('ribbon', $aes, $data, [], compact('alpha', 'fill', 'color') + $style, $inheritAes, $filter);
    }

    /** Bars of counts of x (or y), or of the y values when y is mapped. position: stack, dodge, fill, identity. */
    public function bar(Aes|array|null $aes = null, mixed $data = null, string $position = 'stack', int|float|null $width = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('bar', $aes, $data, ['position' => $position, 'width' => $width], compact('alpha', 'fill', 'color') + $style, $inheritAes, $filter);
    }

    /** Bars whose heights are the y values. */
    public function col(Aes|array|null $aes = null, mixed $data = null, string $position = 'stack', int|float|null $width = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('col', $aes, $data, ['position' => $position, 'width' => $width], compact('alpha', 'fill', 'color') + $style, $inheritAes, $filter);
    }

    /**
     * Binned counts of x: bins (a maximum; Vega-Lite picks nice boundaries) or binwidth. density: true scales to unit
     * area. compute: 'server' bins in PHP (exactly bins equal-width bins), 'browser' in Vega-Lite, and 'auto' (the
     * default) in PHP above Config::$serverRows rows.
     */
    public function histogram(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, int|float|null $boundary = null, string $position = 'stack', bool $density = false, string $compute = 'auto', int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('histogram', $aes, $data, compact('bins', 'binwidth', 'boundary', 'position', 'density', 'compute'), compact('alpha', 'fill', 'color') + $style, $inheritAes, $filter);
    }

    /** Frequency polygons: binned counts drawn as lines. */
    public function freqpoly(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, int|float|null $linewidth = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('freqpoly', $aes, $data, compact('bins', 'binwidth'), compact('linewidth', 'color') + $style, $inheritAes, $filter);
    }

    /**
     * Kernel density of x per group (Vega-Lite's density transform). bw sets the bandwidth; adjust multiplies it
     * (from R's bw.nrd0 when bw is unset); counts: true scales to counts.
     */
    public function density(Aes|array|null $aes = null, mixed $data = null, int|float|null $bw = null, int|float|null $adjust = null, ?array $extent = null, ?int $n = null, bool $counts = false, ?string $position = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('density', $aes, $data, compact('bw', 'adjust', 'extent', 'n', 'position') + ['counts' => $counts ?: null], compact('alpha', 'fill', 'color', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** Tukey boxplots; coef sets the whisker extent in IQRs. */
    public function boxplot(Aes|array|null $aes = null, mixed $data = null, int|float $coef = 1.5, bool $outliers = true, int|float|null $width = null, int|float|null $alpha = null, ?string $fill = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('boxplot', $aes, $data, compact('coef', 'outliers', 'width'), compact('alpha', 'fill', 'color') + $style, $inheritAes, $filter);
    }

    /**
     * Violins: the kernel density of y per level of a discrete x. bw sets the bandwidth, adjust multiplies R's
     * bw.nrd0, and width is the widest violin's share of its band. They layer with boxplot() and jitter().
     */
    public function violin(Aes|array|null $aes = null, mixed $data = null, int|float|null $bw = null, int|float|null $adjust = null, float $width = 0.9, ?int $n = null, ?string $position = null, int|float|null $alpha = null, ?string $fill = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('violin', $aes, $data, compact('bw', 'adjust', 'width', 'n', 'position'), compact('alpha', 'fill') + $style, $inheritAes, $filter);
    }

    /** Error bars from ymin to ymax (or xmin to xmax) with end ticks; width is the tick length in pixels. */
    public function errorbar(Aes|array|null $aes = null, mixed $data = null, int|float|null $width = null, ?string $position = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('errorbar', $aes, $data, compact('width', 'position'), compact('color', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** Vertical (or horizontal) lines from ymin to ymax. */
    public function linerange(Aes|array|null $aes = null, mixed $data = null, ?string $position = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('linerange', $aes, $data, compact('position'), compact('color', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** A point at y on a line from ymin to ymax: the coefficient-plot geom. position: 'dodge' sets groups side by side. */
    public function pointrange(Aes|array|null $aes = null, mixed $data = null, ?string $position = null, ?string $color = null, int|float|null $size = null, int|float|null $linewidth = null, string|int|null $shape = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('pointrange', $aes, $data, compact('position'), compact('color', 'size', 'linewidth', 'shape') + $style, $inheritAes, $filter);
    }

    /** Horizontal reference lines at fixed values, or at a mapped yintercept field when yintercept is null. */
    public function hline(int|float|Expr|array|null $yintercept = null, Aes|array|null $aes = null, mixed $data = null, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): self
    {
        return $this->add('hline', $aes, $data, ['intercept' => $yintercept], compact('color', 'linetype', 'linewidth') + $style, $yintercept === null);
    }

    /** Vertical reference lines at fixed values, or at a mapped xintercept field. */
    public function vline(int|float|string|Expr|array|null $xintercept = null, Aes|array|null $aes = null, mixed $data = null, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): self
    {
        return $this->add('vline', $aes, $data, ['intercept' => $xintercept], compact('color', 'linetype', 'linewidth') + $style, $xintercept === null);
    }

    /** Lines y = intercept + slope * x across the x range (lists draw several). */
    public function abline(int|float|array $intercept = 0, int|float|array $slope = 1, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): self
    {
        return $this->add('abline', null, null, compact('intercept', 'slope'), compact('color', 'linetype', 'linewidth') + $style);
    }

    /** Rules at mapped positions (x, y, xend, yend). */
    public function rule(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('rule', $aes, $data, [], compact('color', 'linetype', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** Line segments from (x, y) to (xend, yend). */
    public function segment(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('segment', $aes, $data, [], compact('color', 'linetype', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** Text from the label aesthetic. nudgeX/nudgeY offset it in pixels; angle turns it anticlockwise. */
    public function text(Aes|array|null $aes = null, mixed $data = null, int|float|null $size = null, ?string $color = null, int|float|null $angle = null, int|float|null $nudgeX = null, int|float|null $nudgeY = null, int|float|null $hjust = null, int|float|null $vjust = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('text', $aes, $data, [], compact('size', 'color', 'angle', 'nudgeX', 'nudgeY', 'hjust', 'vjust') + $style, $inheritAes, $filter);
    }

    /** Bold text labels. */
    public function label(Aes|array|null $aes = null, mixed $data = null, int|float|null $size = null, ?string $color = null, int|float|null $nudgeX = null, int|float|null $nudgeY = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('label', $aes, $data, [], compact('size', 'color', 'nudgeX', 'nudgeY') + $style, $inheritAes, $filter);
    }

    /** Heatmap tiles at discrete x and y (numbers are treated as ordered levels), filled by the fill aesthetic. */
    public function tile(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('tile', $aes, $data, [], compact('color', 'alpha') + $style, $inheritAes, $filter);
    }

    /** Alias of tile(). */
    public function raster(Aes|array|null $aes = null, mixed $data = null, bool $inheritAes = true, mixed ...$style): self
    {
        return $this->add('raster', $aes, $data, [], $style, $inheritAes);
    }

    /** Rectangles from xmin, xmax, ymin, ymax. */
    public function rect(Aes|array|null $aes = null, mixed $data = null, ?string $fill = null, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('rect', $aes, $data, [], compact('fill', 'color', 'alpha') + $style, $inheritAes, $filter);
    }

    /** A 2D histogram: counts in x-by-y bins; compute works as in histogram(). */
    public function bin2d(Aes|array|null $aes = null, mixed $data = null, int|array $bins = 30, string $compute = 'auto', bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('bin2d', $aes, $data, compact('bins', 'compute'), $style, $inheritAes, $filter);
    }

    /** Hexagonal bins counted in PHP, as ggplot2's geom_hex: bins hexagons across the x range. */
    public function hex(Aes|array|null $aes = null, mixed $data = null, int $bins = 30, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('hex', $aes, $data, compact('bins'), $style, $inheritAes, $filter);
    }

    /**
     * A fitted curve per group. method: loess (span sets the bandwidth), lm, poly (order), quad, log, exp, pow. With se
     * (the default for lm, quad and poly with inline data) the fit and a level confidence band are computed in PHP;
     * otherwise Vega-Lite fits the curve and no band is drawn.
     */
    public function smooth(Aes|array|null $aes = null, mixed $data = null, string $method = 'loess', ?bool $se = null, float $level = 0.95, float $span = 0.75, int $order = 3, int $n = 80, ?string $color = null, ?string $fill = null, int|float|null $linewidth = null, string|array|null $linetype = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('smooth', $aes, $data, compact('method', 'se', 'level', 'span', 'order', 'n'), compact('color', 'fill', 'linewidth', 'linetype') + $style, $inheritAes, $filter);
    }

    /**
     * Contour lines of a 2D kernel density of x and y, as ggplot2's geom_density_2d (MASS::kde2d on an n × n grid).
     * h sets the bandwidths ([x, y] or one for both; default MASS::bandwidth.nrd), adjust multiplies them, and bins,
     * binwidth, or breaks set the levels (default pretty(range, 10)). contourVar: density, ndensity (scaled to a
     * maximum of 1 per group), or count. aes(color: 'level') colors lines by their level.
     */
    public function density2d(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, int|float|array|null $h = null, int|float $adjust = 1, int $n = 100, string $contourVar = 'density', ?string $color = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('density_2d', $aes, $data, compact('bins', 'binwidth', 'breaks', 'h', 'adjust', 'n', 'contourVar'), compact('color', 'linewidth', 'alpha') + $style, $inheritAes, $filter);
    }

    /** Filled bands of a 2D kernel density between consecutive breaks, as ggplot2's geom_density_2d_filled. */
    public function density2dFilled(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, int|float|array|null $h = null, int|float $adjust = 1, int $n = 100, string $contourVar = 'density', int|float|null $alpha = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('density_2d_filled', $aes, $data, compact('bins', 'binwidth', 'breaks', 'h', 'adjust', 'n', 'contourVar'), compact('alpha', 'color') + $style, $inheritAes, $filter);
    }

    /** Contour lines of z over a regular grid of x and y, as ggplot2's geom_contour; bins, binwidth, or breaks. */
    public function contour(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, ?string $color = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('contour', $aes, $data, compact('bins', 'binwidth', 'breaks'), compact('color', 'linewidth', 'alpha') + $style, $inheritAes, $filter);
    }

    /** Filled contour bands of z over a regular grid, as ggplot2's geom_contour_filled. */
    public function contourFilled(Aes|array|null $aes = null, mixed $data = null, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null, int|float|null $alpha = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('contour_filled', $aes, $data, compact('bins', 'binwidth', 'breaks'), compact('alpha', 'color') + $style, $inheritAes, $filter);
    }

    /**
     * Filled polygons through the points in data order, one per group (or fill level). Rows with null x and y start
     * a new ring within a group; a ring drawn in the opposite direction cuts a hole.
     */
    public function polygon(Aes|array|null $aes = null, mixed $data = null, ?string $fill = null, ?string $color = null, int|float|null $alpha = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('polygon', $aes, $data, [], compact('fill', 'color', 'alpha', 'linewidth') + $style, $inheritAes, $filter);
    }

    /**
     * Summaries of y at each x, drawn with geom (pointrange by default), as ggplot2's stat_summary. funData:
     * mean_se (the default), mean_cl_normal, mean_sdl, median_hilow, or a callable returning [y, ymin, ymax]. fun,
     * funMin, and funMax: mean, median, min, max, sum, sd, n, or callables of the group's values. funArgs passes mult
     * or conf. geom: pointrange, errorbar, linerange, crossbar, point, line, col, area, or ribbon.
     */
    public function summary(Aes|array|null $aes = null, mixed $data = null, string|\Closure|null $funData = null, string|\Closure|null $fun = null, string|\Closure|null $funMin = null, string|\Closure|null $funMax = null, array $funArgs = [], ?string $geom = null, ?string $position = null, int|float|null $width = null, ?string $color = null, int|float|null $size = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('summary', $aes, $data, compact('funData', 'fun', 'funMin', 'funMax', 'geom', 'position', 'width') + ['funArgs' => $funArgs ?: null], compact('color', 'size', 'linewidth', 'alpha') + $style, $inheritAes, $filter);
    }

    /** A box from ymin to ymax with a line at y, as ggplot2's geom_crossbar; width is a share of the band. */
    public function crossbar(Aes|array|null $aes = null, mixed $data = null, int|float|null $width = null, ?string $position = null, ?string $color = null, ?string $fill = null, int|float|null $linewidth = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('crossbar', $aes, $data, compact('width', 'position'), compact('color', 'fill', 'linewidth', 'alpha') + $style, $inheritAes, $filter);
    }

    /** Points sized by how many rows share each x and y, as ggplot2's geom_count. */
    public function count(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('count', $aes, $data, [], compact('color', 'alpha') + $style, $inheritAes, $filter);
    }

    /**
     * A layer of fixed values rather than data, as ggplot2's annotate(): annotate('text', x: 4, y: 40, label: 'Note'),
     * annotate('rect', xmin: 2, xmax: 3, ymin: -INF ...). Positions are numbers, dates, or levels, or lists of them for
     * several marks. Annotations ignore the plot's mappings and appear in every facet panel. geom: text, label,
     * point, segment, rect, pointrange, linerange, errorbar, crossbar, or rule.
     */
    public function annotate(string $geom, mixed $x = null, mixed $y = null, mixed $xmin = null, mixed $xmax = null, mixed $ymin = null, mixed $ymax = null, mixed $xend = null, mixed $yend = null, mixed $label = null, mixed ...$style): self
    {
        if (! in_array($geom, ['text', 'label', 'point', 'segment', 'rect', 'pointrange', 'linerange', 'errorbar', 'crossbar', 'rule'], true)) {
            throw new InvalidArgumentException("annotate() cannot draw geom '{$geom}'.");
        }
        $values = array_filter(compact('x', 'y', 'xmin', 'xmax', 'ymin', 'ymax', 'xend', 'yend', 'label'), fn ($v) => $v !== null);
        $n = max(array_map(fn ($v) => is_array($v) ? count($v) : 1, $values) ?: [1]);
        $aes = [];
        // Fields named after the plot's own x and y keep the axis titles; the other ends get private names.
        $names = [];
        foreach ($values as $aesthetic => $value) {
            $axis = $aesthetic === 'label' ? null : $aesthetic[0];
            if (is_float($value) && is_infinite($value) && $axis !== null) {
                // -INF and INF reach the panel's edges, as in ggplot2: pixel positions rather than data values.
                $edge = ($axis === 'x') === ($value > 0) ? ($axis === 'x' ? 'width' : 'height') : '0';
                $aes[$aesthetic] = ['value' => $edge === '0' ? 0 : $edge];
                unset($values[$aesthetic]);
                continue;
            }
            $primary = in_array($aesthetic, ['x', 'y', 'xmin', 'ymin'], true) && ! ($aesthetic === 'xmin' && isset($values['x'])) && ! ($aesthetic === 'ymin' && isset($values['y']));
            $plain = $axis === null ? null : $this->aes->field($axis);
            $names[$aesthetic] = $primary && $plain !== null ? $plain : "__{$aesthetic}";
            $field = $axis === null ? null : $this->aes->get($axis);
            $type = $field === null || is_array($field) ? null : Field::of($field)->type;
            $aes[$aesthetic] = $type === null ? $names[$aesthetic] : new Field($names[$aesthetic], $type);
        }
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            foreach ($values as $aesthetic => $value) {
                $v = is_array($value) ? ($value[$i] ?? null) : $value;
                $row[$names[$aesthetic]] = is_float($v) && is_infinite($v) ? null : $v;
            }
            $rows[] = $row;
        }

        return $this->add($geom, $aes, $rows, ['annotation' => true], $style, inheritAes: false);
    }

    /** A function of x drawn from `from` to `to`, as ggplot2's geom_function. */
    public function curve(callable $fn, int|float $from, int|float $to, int $n = 101, Aes|array|null $aes = null, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, mixed ...$style): self
    {
        return $this->add('curve', $aes, null, ['fn' => $fn, 'from' => $from, 'to' => $to, 'n' => $n], compact('color', 'linetype', 'linewidth') + $style);
    }

    /** Marginal ticks: sides 'b' (bottom, for x), 'l' (left, for y), or 'bl'; length in pixels. */
    public function rug(Aes|array|null $aes = null, mixed $data = null, ?string $sides = null, int $length = 8, ?string $color = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('rug', $aes, $data, compact('sides', 'length'), compact('color', 'alpha') + $style, $inheritAes, $filter);
    }

    /** Empirical cumulative distribution of x per group. */
    public function ecdf(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('ecdf', $aes, $data, [], compact('color', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** Normal quantile-quantile points of the sample aesthetic. */
    public function qq(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, int|float|null $size = null, int|float|null $alpha = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('qq', $aes, $data, [], compact('color', 'size', 'alpha') + $style, $inheritAes, $filter);
    }

    /** The QQ reference line through the sample's quartiles. */
    public function qqLine(Aes|array|null $aes = null, mixed $data = null, ?string $color = null, string|array|null $linetype = null, int|float|null $linewidth = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('qq_line', $aes, $data, [], compact('color', 'linetype', 'linewidth') + $style, $inheritAes, $filter);
    }

    /** Pie (or donut, with innerRadius in pixels) slices sized by theta, or counts of fill. */
    public function arc(Aes|array|null $aes = null, mixed $data = null, int|float|null $innerRadius = null, ?string $color = null, bool $inheritAes = true, string|array|null $filter = null, mixed ...$style): self
    {
        return $this->add('arc', $aes, $data, compact('innerRadius'), compact('color') + $style, $inheritAes, $filter);
    }

    /** Alias of arc(). */
    public function pie(Aes|array|null $aes = null, mixed $data = null, int|float|null $innerRadius = null, mixed ...$style): self
    {
        return $this->arc($aes, $data, $innerRadius, ...$style);
    }

    /** A raw Vega-Lite layer, used as given. */
    public function layer(array $spec): self
    {
        return clone($this, ['layers' => [...$this->layers, new Layer('raw', Aes::of(), ['spec' => $spec])]]);
    }

    /** Adds a geom by name with options: the generic form behind the geom methods. */
    public function geom(string $geom, Aes|array|null $aes = null, mixed $data = null, array $params = [], array $style = [], bool $inheritAes = true, string|array|null $filter = null, array $raw = []): self
    {
        return $this->add($geom, $aes, $data, $params, $style, $inheritAes, $filter, $raw);
    }

    private function add(string $geom, Aes|array|null $aes, mixed $data, array $params, array $style, bool $inheritAes = true, string|array|null $filter = null, array $raw = []): self
    {
        $layer = new Layer(
            $geom,
            Aes::from($aes),
            array_filter($params, fn ($v) => $v !== null),
            array_filter($style, fn ($v) => $v !== null),
            $data === null ? null : Data::from($data),
            $inheritAes,
            $filter,
            $raw,
        );

        return clone($this, ['layers' => [...$this->layers, $layer]]);
    }

    // Scales.

    /** Sets the scale for an aesthetic; see Scale::make() for the options. */
    public function scale(string $aesthetic, mixed ...$options): self
    {
        $aesthetic = match ($aesthetic) { 'colour' => 'color', 'alpha' => 'opacity', default => $aesthetic };
        $scale = Scale::make(...$options);
        $scales = $this->scales;
        $scales[$aesthetic] = isset($scales[$aesthetic]) ? $scales[$aesthetic]->merge($scale) : $scale;

        return clone($this, ['scales' => $scales]);
    }

    /** x scale: type (linear, log, sqrt, time, ...), domain, range, zero, nice, reverse, name, breaks, labels, format. */
    public function scaleX(mixed ...$options): self
    {
        return $this->scale('x', ...$options);
    }

    public function scaleY(mixed ...$options): self
    {
        return $this->scale('y', ...$options);
    }

    /** Color scale: scheme ('viridis', 'blues', 'category10', ...), range, values (manual palette), domainMid, ... */
    public function scaleColor(mixed ...$options): self
    {
        return $this->scale('color', ...$options);
    }

    public function scaleFill(mixed ...$options): self
    {
        return $this->scale('fill', ...$options);
    }

    public function scaleSize(mixed ...$options): self
    {
        return $this->scale('size', ...$options);
    }

    public function scaleShape(mixed ...$options): self
    {
        return $this->scale('shape', ...$options);
    }

    public function scaleOpacity(mixed ...$options): self
    {
        return $this->scale('opacity', ...$options);
    }

    public function scaleLinetype(mixed ...$options): self
    {
        return $this->scale('linetype', ...$options);
    }

    /**
     * Guides per aesthetic, as ggplot2's guides(): 'none' or false hides one, and an array sets axis or legend
     * properties (see guide_legend(), guide_colorbar(), and guide_axis()).
     */
    public function guides(mixed ...$guides): self
    {
        $plot = $this;
        foreach ($guides as $aesthetic => $guide) {
            $plot = $plot->scale((string) $aesthetic, guide: $guide === 'none' ? false : $guide);
        }

        return $plot;
    }

    /** Fixes the x domain. Unlike ggplot2's xlim(), data outside it is clipped rather than dropped. */
    public function xlim(int|float|string $min, int|float|string $max): self
    {
        return $this->coordCartesian(xlim: [$min, $max]);
    }

    public function ylim(int|float|string $min, int|float|string $max): self
    {
        return $this->coordCartesian(ylim: [$min, $max]);
    }

    // Coordinates and facets.

    /** Swaps the x and y channels, so mappings stay as written while the plot turns sideways. */
    public function coordFlip(bool $flip = true): self
    {
        return clone($this, ['flip' => $flip]);
    }

    /**
     * Polar coordinates for bar() and col() layers, as ggplot2's coord_polar. theta: 'y' turns stacked bars into a
     * pie (map x to a constant, or leave it out; innerRadius in pixels makes a donut), and 'x' turns bars over a
     * discrete x into a rose of equal angles with radius y. start rotates the first slice clockwise from twelve
     * o'clock, in radians; direction -1 runs anticlockwise.
     */
    public function coordPolar(string $theta = 'x', float $start = 0.0, int $direction = 1, int|float $innerRadius = 0): self
    {
        if (! in_array($theta, ['x', 'y'], true) || ! in_array($direction, [1, -1], true)) {
            throw new InvalidArgumentException("coordPolar() takes theta 'x' or 'y' and direction 1 or -1.");
        }

        return clone($this, ['coord' => ['type' => 'polar', 'theta' => $theta, 'start' => $start, 'direction' => $direction, 'innerRadius' => $innerRadius]]);
    }

    /**
     * A fixed aspect ratio, as ggplot2's coord_fixed: one unit of y takes ratio times the length of one unit of x.
     * The panel's height follows from the width (300 pixels unless set) and the data ranges, which are kept exact.
     */
    public function coordFixed(int|float $ratio = 1): self
    {
        if (! ($ratio > 0)) {
            throw new InvalidArgumentException('coordFixed() needs a positive ratio.');
        }

        return clone($this, ['coord' => ['type' => 'fixed', 'ratio' => $ratio]]);
    }

    /** Zooms to fixed domains, clipping marks outside them. */
    public function coordCartesian(?array $xlim = null, ?array $ylim = null): self
    {
        return clone($this, ['limits' => array_filter(['x' => $xlim, 'y' => $ylim] + $this->limits, fn ($v) => $v !== null)]);
    }

    /**
     * Small multiples wrapped into rows. scales: fixed, free, free_x, free_y. With several fields the panels are
     * their combinations. labeller: 'both' labels panels "field: value", as ggplot2's label_both.
     *
     * @param array<string, mixed> $header Vega-Lite header properties for the panel labels.
     */
    public function facetWrap(string|array $facets, ?int $ncol = null, ?int $nrow = null, string $scales = 'fixed', string|array $labeller = 'value', array $header = []): self
    {
        $facets = (array) $facets;
        if ($facets === []) {
            throw new InvalidArgumentException('facetWrap needs a field.');
        }

        return clone($this, ['facet' => ['type' => 'wrap', 'facets' => array_values($facets), 'ncol' => $ncol, 'nrow' => $nrow, 'scales' => $scales, 'labeller' => self::labeller($labeller), 'header' => $header]]);
    }

    /** A grid of panels by rows and/or cols fields. */
    public function facetGrid(?string $rows = null, ?string $cols = null, string $scales = 'fixed', string|array $labeller = 'value', array $header = []): self
    {
        if ($rows === null && $cols === null) {
            throw new InvalidArgumentException('facetGrid needs rows, cols, or both.');
        }

        return clone($this, ['facet' => ['type' => 'grid', 'rows' => $rows, 'cols' => $cols, 'scales' => $scales, 'labeller' => self::labeller($labeller), 'header' => $header]]);
    }

    /**
     * Panel labels: 'value' (ggplot2's label_value), 'both' (label_both, "field: value"), or a map from values to
     * labels (as_labeller), optionally keyed by field for grids: ['drv' => ['f' => 'Front'], 'year' => [...]].
     */
    private static function labeller(string|array $labeller): string|array
    {
        if (is_string($labeller) && ! in_array($labeller, ['value', 'both'], true)) {
            throw new InvalidArgumentException("labeller must be 'value', 'both', or a map of values to labels.");
        }

        return $labeller;
    }

    // Labels, theme, size.

    /**
     * Titles: title, subtitle, and one per aesthetic (x, y, color, fill, size, shape, ...). A facet field's name sets
     * its panel header title. Null removes a title.
     */
    public function labs(mixed ...$labels): self
    {
        $out = $this->labels;
        foreach ($labels as $name => $title) {
            $out[match ($name) { 'colour' => 'color', 'alpha' => 'opacity', default => $name }] = $title;
        }

        return clone($this, ['labels' => $out]);
    }

    public function title(?string $title, ?string $subtitle = null): self
    {
        return $this->labs(...['title' => $title] + ($subtitle === null ? [] : ['subtitle' => $subtitle]));
    }

    /**
     * A theme preset (gray, bw, linedraw, minimal, classic, light, dark, void, vega) and/or individual options; config
     * is raw Vega-Lite config merged last. Successive calls build on each other. xLabelAngle and yLabelAngle turn axis
     * labels anticlockwise, as element_text(angle =); legendTitle: false, axisLine, and ticks switch those parts.
     */
    public function theme(?string $preset = null, int|float|null $baseSize = null, ?string $font = null, ?string $legendPosition = null, ?string $background = null, ?string $panelBackground = null, ?string $panelBorder = null, ?bool $grid = null, ?string $titleAnchor = null, int|float|null $padding = null, int|float|null $xLabelAngle = null, int|float|null $yLabelAngle = null, ?string $legendDirection = null, ?bool $legendTitle = null, ?bool $axisLine = null, ?bool $ticks = null, ?string $gridColor = null, ?string $textColor = null, array $config = []): self
    {
        $base = match (true) {
            $preset !== null => Theme::preset($preset, $baseSize ?? 11),
            $baseSize !== null => Theme::fonts($baseSize),
            default => [],
        };
        $current = $preset !== null ? [] : $this->config;

        return clone($this, ['config' => Theme::merge($current, $base, Theme::options($font, $legendPosition, $background, $panelBackground, $panelBorder, $grid, $titleAnchor, $padding, $xLabelAngle, $yLabelAngle, $legendDirection, $legendTitle, $axisLine, $ticks, $gridColor, $textColor), $config)]);
    }

    /** Width and height in pixels (per panel when faceted); 'container' fills the parent element's width. */
    public function size(int|string|null $width = null, int|string|null $height = null): self
    {
        return clone($this, ['width' => $width ?? $this->width, 'height' => $height ?? $this->height]);
    }

    public function width(int|string $width): self
    {
        return clone($this, ['width' => $width]);
    }

    public function height(int|string $height): self
    {
        return clone($this, ['height' => $height]);
    }

    public function description(string $description): self
    {
        return clone($this, ['description' => $description]);
    }

    // Interaction.

    /** Tooltips on every layer: true shows the encoded fields, a list shows those fields. */
    public function tooltip(bool|array|string $fields = true): self
    {
        return clone($this, ['tooltip' => is_string($fields) ? [$fields] : $fields]);
    }

    /** Pan and zoom by dragging and scrolling (scales bound to an interval selection). */
    public function interactive(array $encodings = ['x', 'y'], string $name = 'zoom'): self
    {
        return $this->select(['name' => $name, 'select' => ['type' => 'interval', 'encodings' => $encodings], 'bind' => 'scales']);
    }

    /**
     * A drag-to-select interval named $name. Unselected marks of the first layer turn $unselected; other layers can
     * react with filter: ['param' => $name], and linked plots in a composition share it.
     */
    public function brush(string $name = 'brush', array $encodings = ['x'], ?string $unselected = '#cccccc', string $channel = 'color'): self
    {
        $plot = $this->select(['name' => $name, 'select' => ['type' => 'interval', 'encodings' => $encodings]]);

        return $unselected === null ? $plot : clone($plot, ['conditions' => [...$plot->conditions, ['param' => $name, 'channel' => $channel, 'unselected' => $unselected, 'layer' => 'first']]]);
    }

    /** Click legend entries (or marks, with bind: null) to highlight their group; others fade to $opacity. */
    public function highlight(string $aesthetic = 'color', string $name = 'highlight', ?string $bind = 'legend', float $opacity = 0.15): self
    {
        $field = $this->aes->field($aesthetic);
        foreach ($this->layers as $layer) {
            $field ??= $layer->aes->field($aesthetic);
        }
        if ($field === null) {
            throw new InvalidArgumentException("highlight() needs the {$aesthetic} aesthetic mapped to a field.");
        }
        $param = ['name' => $name, 'select' => ['type' => 'point', 'fields' => [Field::escape($field)]]];
        if ($bind !== null) {
            $param['bind'] = $bind;
        }

        return clone($this->select($param), ['conditions' => [...$this->conditions, ['param' => $name, 'channel' => 'opacity', 'unselected' => $opacity, 'selected' => 1, 'layer' => 'all']]]);
    }

    /** A slider bound to a parameter; use expr($name) in geoms or a filter to react to it. */
    public function slider(string $name, int|float $min, int|float $max, int|float|null $step = null, int|float|null $value = null, ?string $label = null): self
    {
        $bind = ['input' => 'range', 'min' => $min, 'max' => $max] + array_filter(['step' => $step, 'name' => $label], fn ($v) => $v !== null);

        return $this->param($name, $value ?? $min, $bind);
    }

    /** A dropdown bound to a parameter. */
    public function dropdown(string $name, array $options, mixed $value = null, ?string $label = null): self
    {
        $bind = ['input' => 'select', 'options' => array_values($options)] + ($label === null ? [] : ['name' => $label]);

        return $this->param($name, $value ?? reset($options), $bind);
    }

    /** A Vega-Lite parameter: a value, optionally bound to an input, or an expr. */
    public function param(string $name, mixed $value = null, ?array $bind = null, ?string $expr = null): self
    {
        $param = ['name' => $name];
        if ($expr !== null) {
            $param['expr'] = $expr;
        } else {
            $param['value'] = $value;
        }
        if ($bind !== null) {
            $param['bind'] = $bind;
        }

        return clone($this, ['params' => [...$this->params, $param]]);
    }

    /** A raw selection parameter, attached to the first layer. */
    public function select(array $param): self
    {
        if (! isset($param['name'], $param['select'])) {
            throw new InvalidArgumentException('A selection parameter needs a name and a select definition.');
        }

        return clone($this, ['params' => [...$this->params, $param]]);
    }

    // Escape hatches.

    /** Vega-Lite transforms applied to the data before every layer. */
    public function transform(array ...$transforms): self
    {
        return clone($this, ['transforms' => [...$this->transforms, ...$transforms]]);
    }

    /** Raw Vega-Lite merged into the top-level spec last (nested objects merge; lists and scalars replace). */
    public function raw(array $spec): self
    {
        return clone($this, ['raw' => Theme::merge($this->raw, $spec)]);
    }

    /** Applies a function to the plot: pipe-friendly composition of reusable plot fragments. */
    public function apply(callable ...$fns): self
    {
        $plot = $this;
        foreach ($fns as $fn) {
            $plot = $fn($plot);
        }

        return $plot;
    }

    public function toArray(): array
    {
        return Compiler::compile($this);
    }

    public function toSpec(): array
    {
        $spec = Compiler::compile($this, topLevel: false);
        unset($spec['config']);

        return $spec;
    }
}

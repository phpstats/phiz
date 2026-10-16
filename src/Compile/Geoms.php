<?php

declare(strict_types=1);

namespace Phit\Compile;

use InvalidArgumentException;
use Phit\Aes;
use Phit\Bin;
use Phit\Config;
use Phit\Data;
use Phit\Expr;
use Phit\Field;
use Phit\Layer;
use Phit\Stats\Contour;
use Phit\Stats\Fit;
use Phit\Style;

/**
 * Compiles each geom to Vega-Lite layers (mark, encoding, transform; data is attached by the Compiler).
 *
 * @internal
 */
final class Geoms
{
    private const SMOOTH_COLOR = '#3366FF';

    private const INTERVAL_COLOR = '#4C78A8';

    /** The most rows for which smooth(method: 'loess') computes its confidence band in PHP by default. */
    private const LOESS_ROWS = 3000;

    private const COMMON = ['opacity' => 'opacity', 'tooltip' => 'tooltip', 'href' => 'href', 'key' => 'key', 'description' => 'description'];

    /** @return list<array<string, mixed>> */
    public static function compile(Context $c): array
    {
        $geom = $c->layer->geom;
        if ($c->polar !== null && ! in_array($geom, ['bar', 'col', 'arc', 'raw'], true) && ! $c->layer->param('annotation')) {
            throw new InvalidArgumentException("coordPolar() draws bar(), col(), and arc() layers; {$geom}() has no polar form.");
        }
        if ($c->polar !== null && in_array($geom, ['bar', 'col'], true)) {
            return self::polarBar($c);
        }

        return match ($geom) {
            'point' => self::point($c),
            'line', 'path', 'step' => self::line($c),
            'area' => self::area($c),
            'ribbon' => self::ribbon($c),
            'bar', 'col' => self::bar($c),
            'histogram' => self::histogram($c),
            'freqpoly' => self::freqpoly($c),
            'density' => self::density($c),
            'boxplot' => self::boxplot($c),
            'violin' => self::violin($c),
            'errorbar' => self::errorbar($c),
            'linerange' => [self::linerange($c)],
            'pointrange' => self::pointrange($c),
            'hline', 'vline' => self::refline($c),
            'abline' => self::abline($c),
            'rule' => self::rule($c),
            'segment' => self::segment($c),
            'text', 'label' => self::text($c),
            'tile', 'raster' => self::tile($c),
            'rect' => self::rect($c),
            'bin2d' => self::bin2d($c),
            'hex' => self::hex($c),
            'smooth' => self::smooth($c),
            'curve' => self::curve($c),
            'rug' => self::rug($c),
            'ecdf' => self::ecdf($c),
            'qq' => self::qq($c),
            'qq_line' => self::qqLine($c),
            'arc' => self::arc($c),
            'polygon' => self::polygon($c),
            'summary' => self::summary($c),
            'crossbar' => self::crossbar($c),
            'count' => self::count($c),
            'ellipse' => self::ellipse($c),
            'density_2d' => self::contour($c, density: true, filled: false),
            'density_2d_filled' => self::contour($c, density: true, filled: true),
            'contour' => self::contour($c, density: false, filled: false),
            'contour_filled' => self::contour($c, density: false, filled: true),
            'raw' => [$c->param('spec')],
            default => throw new InvalidArgumentException("Unknown geom '{$geom}'."),
        };
    }

    private static function point(Context $c): array
    {
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'color' => 'color', 'fill' => 'fill', 'size' => 'size', 'shape' => 'shape', 'group' => 'detail', 'detail' => 'detail', 'order' => 'order'] + self::COMMON);
        $jitter = $c->param('jitter');
        if ($jitter !== null && $jitter !== false) {
            [$width, $height] = is_array($jitter) ? [$jitter[0] ?? null, $jitter[1] ?? 0] : [is_numeric($jitter) ? $jitter : null, 0];
            $encoding = self::jitter($c, $encoding, 'x', $width);
            $encoding = self::jitter($c, $encoding, 'y', $height);
        }

        $encoding = self::dodge($c, $encoding);

        return [['mark' => $c->mark('point', 'point', ['filled' => true]), 'encoding' => $encoding]];
    }

    /**
     * position: 'dodge' for points and intervals: side by side within each band of the discrete axis, split by the
     * color (or fill, or group) field.
     */
    private static function dodge(Context $c, array $encoding): array
    {
        if ($c->param('position') !== 'dodge') {
            return $encoding;
        }
        $axis = match (true) {
            isset($encoding['y']['type']) && in_array($encoding['y']['type'], ['nominal', 'ordinal'], true) => 'y',
            isset($encoding['x']['type']) && in_array($encoding['x']['type'], ['nominal', 'ordinal'], true) => 'x',
            default => throw new InvalidArgumentException('position dodge needs a discrete x or y axis.'),
        };
        foreach (['color', 'fill', 'group'] as $aesthetic) {
            if ($c->has($aesthetic)) {
                $encoding["{$axis}Offset"] = ['field' => $c->ref($aesthetic), 'type' => 'nominal'];
                break;
            }
        }

        return $encoding;
    }

    /** Random displacement: within the band for a discrete axis, in data units (default 40% of resolution) for a continuous one. */
    private static function jitter(Context $c, array $encoding, string $axis, int|float|null $amount): array
    {
        if (! isset($encoding[$axis]) || $amount === 0 || $amount === 0.0) {
            return $encoding;
        }
        $as = "__jitter_{$axis}";
        if ($c->discrete($axis)) {
            // Offsets span the band, so a domain of ±0.25/w moves points up to w of a band from its center.
            $half = 0.25 / ($amount ?? 0.4);
            $c->calculate('random() - 0.5', $as);
            $encoding["{$axis}Offset"] = ['field' => $as, 'type' => 'quantitative', 'scale' => ['domain' => [-$half, $half]]];

            return $encoding;
        }
        $amount ??= 0.4 * self::resolution($c->data->column((string) $c->name($axis)));
        $c->calculate('datum['.json_encode($c->name($axis)).'] + (random() - 0.5) * '.self::number(2 * $amount), $as);
        $encoding[$axis] = array_replace($encoding[$axis], ['field' => $as, 'title' => $encoding[$axis]['title'] ?? $c->name($axis)]);

        return $encoding;
    }

    private static function line(Context $c): array
    {
        $downsample = $c->param('downsample');
        if ($downsample !== null && $c->data->isInline() && $c->data->count() > $downsample) {
            $c->computed = Bin::lttb($c->data->rows, (string) $c->name('x'), (string) $c->name('y'), (int) $downsample, $c->groupFields());
        }
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'color' => 'color', 'size' => 'strokeWidth', 'linetype' => 'strokeDash', 'group' => 'detail', 'detail' => 'detail', 'order' => 'order'] + self::COMMON);
        $defaults = [];
        if ($c->layer->geom === 'path' && ! isset($encoding['order'])) {
            // Connect in data order rather than sorting by x.
            $c->transforms[] = ['window' => [['op' => 'row_number', 'as' => '__order']]];
            $encoding['order'] = ['field' => '__order', 'type' => 'quantitative'];
        }
        if ($c->layer->geom === 'step') {
            $defaults['interpolate'] = match ($c->param('direction', 'hv')) {
                'hv' => 'step-after', 'vh' => 'step-before', 'mid' => 'step',
                default => throw new InvalidArgumentException("step direction must be 'hv', 'vh', or 'mid'."),
            };
        }

        return [['mark' => $c->mark('line', 'line', $defaults), 'encoding' => $encoding]];
    }

    private static function area(Context $c): array
    {
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'fill' => 'color', 'color' => 'stroke', 'group' => 'detail', 'detail' => 'detail', 'order' => 'order'] + self::COMMON);
        $encoding = self::position($c, $encoding, 'y', 'x');

        return [['mark' => $c->mark('area', 'filled'), 'encoding' => $encoding]];
    }

    private static function ribbon(Context $c): array
    {
        $horizontal = $c->has('xmin') && ! $c->has('ymin');
        $c->require(...($horizontal ? ['y', 'xmin', 'xmax'] : ['x', 'ymin', 'ymax']));
        $map = $horizontal ? ['y' => 'y', 'xmin' => 'x', 'xmax' => 'x2'] : ['x' => 'x', 'ymin' => 'y', 'ymax' => 'y2'];
        $encoding = self::ranged($c, $map + ['fill' => 'color', 'color' => 'stroke', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);

        return [['mark' => $c->mark('area', 'filled', ['opacity' => 0.4] + ($horizontal ? ['orient' => 'vertical'] : [])), 'encoding' => $encoding]];
    }

    /** Encodes min/max aesthetics, titling the range by the y (or x) mapping as ggplot2 does. */
    private static function ranged(Context $c, array $map): array
    {
        $encoding = $c->encode($map);
        foreach (['x' => ['xmin', 'x'], 'y' => ['ymin', 'y']] as $channel => [$min, $main]) {
            if (isset($map[$min], $encoding[$channel]) && ! isset($encoding[$channel]['title']) && $c->has($main) && ! isset($map[$main])) {
                $encoding[$channel]['title'] = $c->name($main);
            }
        }

        return $encoding;
    }

    private static function bar(Context $c): array
    {
        if ($c->has('xmin') && $c->has('xmax')) {
            // Precomputed bins: bars spanning [xmin, xmax] with height y, on a continuous x axis.
            $c->require('y');
            $encoding = $c->encode(['xmin' => 'x', 'xmax' => 'x2', 'y' => 'y', 'fill' => 'color', 'color' => 'stroke', 'group' => 'detail'] + self::COMMON);
            $encoding['x']['bin'] = ['binned' => true];
            $encoding['x']['type'] = 'quantitative';
            if (! isset($encoding['x']['title']) && $c->has('x')) {
                $encoding['x']['title'] = $c->name('x');
            }

            return [['mark' => $c->mark('bar', 'filled'), 'encoding' => self::position($c, $encoding, 'y', 'x')]];
        }
        $count = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count'];
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'fill' => 'color', 'color' => 'stroke', 'group' => 'detail', 'detail' => 'detail', 'order' => 'order'] + self::COMMON);
        [$value, $category] = ['y', 'x'];
        if ($c->layer->geom === 'bar' && ! $c->has('y')) {
            $c->require('x');
            $encoding['y'] = $count;
            $c->channels['y'] = 'y';
        } elseif ($c->layer->geom === 'bar' && ! $c->has('x')) {
            $encoding['x'] = $count;
            $c->channels['x'] = 'x';
            [$value, $category] = ['x', 'y'];
        } else {
            $c->require('x', 'y');
            if ($c->discrete('y') && ! $c->discrete('x')) {
                [$value, $category] = ['x', 'y'];
            }
        }
        $encoding = self::position($c, $encoding, $value, $category);
        $defaults = [];
        if ($c->param('width') !== null) {
            $defaults[$category === 'x' ? 'width' : 'height'] = ['band' => $c->param('width')];
        }

        return [['mark' => $c->mark('bar', 'filled', $defaults), 'encoding' => $encoding]];
    }

    /** Applies position adjustment: stack (default), fill (stack to 100%), dodge (side by side), identity (overlap). */
    private static function position(Context $c, array $encoding, string $value, string $category): array
    {
        $position = $c->param('position', 'stack');
        if (! isset($encoding[$value])) {
            return $encoding;
        }
        switch ($position) {
            case 'stack':
                break;
            case 'fill':
                $encoding[$value]['stack'] = 'normalize';
                break;
            case 'identity':
                $encoding[$value]['stack'] = null;
                break;
            case 'dodge':
                $group = $c->has('fill') ? 'fill' : ($c->has('color') ? 'color' : ($c->has('group') ? 'group' : null));
                if ($group !== null) {
                    $encoding["{$category}Offset"] = ['field' => $c->ref($group), 'type' => 'nominal'];
                }
                $encoding[$value]['stack'] = null;
                break;
            default:
                throw new InvalidArgumentException("Unknown position '{$position}'; use stack, fill, dodge, or identity.");
        }

        return $encoding;
    }

    private static function bin(Context $c): array|bool
    {
        $bins = $c->param('bins');
        $width = $c->param('binwidth');
        $bin = match (true) {
            $width !== null => ['step' => $width],
            default => ['maxbins' => $bins ?? 30],
        };
        if ($c->param('boundary') !== null) {
            $bin['anchor'] = $c->param('boundary');
        }

        return $bin;
    }

    private static function histogram(Context $c): array
    {
        $axis = $c->has('x') ? 'x' : 'y';
        $other = $axis === 'x' ? 'y' : 'x';
        $c->require($axis);
        if (self::server($c, [$axis])) {
            $field = (string) $c->name($axis);
            $c->computed = Bin::histogram($c->data->source(), $field, (int) $c->param('bins', 30), $c->param('binwidth'), $c->param('boundary'), groups: self::binGroups($c));
            $c->transforms = [];
            $encoding = $c->encode(['fill' => 'color', 'color' => 'stroke', 'group' => 'detail']);
            $encoding[$axis] = ['field' => Field::escape($field.'_start'), 'type' => 'quantitative', 'bin' => ['binned' => true], 'title' => $field];
            $encoding["{$axis}2"] = ['field' => Field::escape($field.'_end')];
            $density = (bool) $c->param('density');
            $encoding[$other] = ['field' => $density ? 'density' : 'count', 'type' => 'quantitative', 'title' => $density ? 'density' : 'count'];
            $c->channels += [$axis => $axis, $other => $other];

            return [['mark' => $c->mark('bar', 'filled'), 'encoding' => self::position($c, $encoding, $other, $axis)]];
        }
        $encoding = $c->encode([$axis => $axis, 'fill' => 'color', 'color' => 'stroke', 'group' => 'detail'] + self::COMMON);
        $c->channels[$other] = $other;

        if ($c->param('density')) {
            $group = $c->groupby();
            $c->transforms[] = ['bin' => self::bin($c), 'field' => $c->ref($axis), 'as' => ['__bin_start', '__bin_end']];
            $c->transforms[] = ['aggregate' => [['op' => 'count', 'as' => '__count']], 'groupby' => ['__bin_start', '__bin_end', ...$group]];
            $c->transforms[] = ['joinaggregate' => [['op' => 'sum', 'field' => '__count', 'as' => '__total']], 'groupby' => $group];
            $c->transforms[] = ['calculate' => 'datum.__count / datum.__total / (datum.__bin_end - datum.__bin_start)', 'as' => 'density'];
            $encoding[$axis] = ['field' => '__bin_start', 'type' => 'quantitative', 'bin' => ['binned' => true], 'title' => $c->name($axis)];
            $encoding["{$axis}2"] = ['field' => '__bin_end'];
            $encoding[$other] = ['field' => 'density', 'type' => 'quantitative', 'title' => 'density'];
        } else {
            $encoding[$axis]['bin'] = self::bin($c);
            $encoding[$axis]['title'] ??= $c->name($axis);
            $encoding[$other] = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count'];
        }
        $encoding = self::position($c, $encoding, $other, $axis);

        return [['mark' => $c->mark('bar', 'filled'), 'encoding' => $encoding]];
    }

    private static function freqpoly(Context $c): array
    {
        $c->require('x');
        $encoding = $c->encode(['x' => 'x', 'color' => 'color', 'linetype' => 'strokeDash', 'group' => 'detail'] + self::COMMON);
        $encoding['x']['bin'] = self::bin($c);
        $encoding['x']['title'] ??= $c->name('x');
        $encoding['y'] = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count'];
        $c->channels['y'] = 'y';

        return [['mark' => $c->mark('line', 'line'), 'encoding' => $encoding]];
    }

    private static function density(Context $c): array
    {
        $c->require('x');
        $x = (string) $c->name('x');
        $transform = ['density' => $c->ref('x'), 'groupby' => $c->groupby(), 'as' => [$x, 'density']];
        if ($transform['groupby'] === []) {
            unset($transform['groupby']);
        }
        $bandwidth = $c->param('bw');
        if ($c->param('adjust') !== null) {
            $values = array_values(array_filter($c->data->column($x), is_numeric(...)));
            if ($bandwidth === null && count($values) < 2) {
                throw new InvalidArgumentException('density(adjust:) computes the bandwidth from inline data; with URL data set bw: instead.');
            }
            $bandwidth = ($bandwidth ?? Fit::bandwidth($values)) * $c->param('adjust');
        }
        foreach (['bandwidth' => $bandwidth, 'extent' => $c->param('extent'), 'counts' => $c->param('counts'), 'steps' => $c->param('n'), 'cumulative' => $c->param('cumulative')] as $key => $value) {
            if ($value !== null) {
                $transform[$key] = $value;
            }
        }
        $c->transforms[] = $transform;

        $filled = $c->has('fill') || isset($c->layer->style['fill']);
        $encoding = $c->encode(['x' => 'x'] + ($filled ? ['fill' => 'color', 'color' => 'stroke'] : ['color' => 'color', 'fill' => 'color']) + ['linetype' => 'strokeDash', 'group' => 'detail', 'opacity' => 'opacity', 'tooltip' => 'tooltip']);
        $encoding['x']['type'] = 'quantitative';
        $encoding['y'] = ['field' => 'density', 'type' => 'quantitative', 'title' => $c->param('counts') ? 'count' : 'density'];
        $c->channels['y'] = 'y';
        if ($filled) {
            $encoding['y']['stack'] = $c->param('position') === 'stack' ? 'zero' : null;
        }

        return [['mark' => $filled ? $c->mark('area', 'filled', ['opacity' => 0.5, 'line' => true]) : $c->mark('line', 'line'), 'encoding' => $encoding]];
    }

    private static function boxplot(Context $c): array
    {
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'fill' => 'color', 'color' => 'color'] + ['opacity' => 'opacity']);
        $defaults = ['extent' => $c->param('coef', 1.5)];
        if ($c->param('outliers') === false) {
            $defaults['outliers'] = false;
        }
        if ($c->param('width') !== null) {
            $defaults['size'] = $c->param('width');
        }
        $mark = $c->mark('boxplot', 'filled', $defaults);
        if (isset($mark['stroke'])) {
            // A boxplot's color is its rule, box outline, and median.
            $mark['rule'] = ['stroke' => $mark['stroke']];
            $mark['median'] = ['stroke' => $mark['stroke']];
            $mark['box'] = ['stroke' => $mark['stroke']];
            unset($mark['stroke']);
        }

        return [['mark' => $mark, 'encoding' => $encoding]];
    }

    /**
     * Violins: a kernel density of y per level of x, drawn as a trail whose width is the density, centered on the
     * level's band. Every violin has the same maximum width (ggplot2's scale = "width"). They layer with boxplots and
     * points on the same discrete axis.
     */
    private static function violin(Context $c): array
    {
        $c->require('x', 'y');
        if (! $c->discrete('x')) {
            throw new InvalidArgumentException('violin needs a discrete x; use factor() or the :N shorthand for numeric codes.');
        }
        $y = (string) $c->name('y');
        if ($c->data->isInline() && $c->fieldOf('y')?->calculate === null && $c->fieldOf('x')?->calculate === null) {
            // As ggplot2: each group's own bandwidth (bw, or R's bw.nrd0 times adjust), trimmed to its range.
            $c->computed = self::violinDensities($c, (string) $c->name('x'), $y);
        } else {
            $groupby = array_values(array_unique([$c->ref('x'), ...$c->groupby()]));
            $transform = ['density' => $c->ref('y'), 'groupby' => $groupby, 'as' => [$y, '__density']];
            if ($c->param('adjust') !== null && $c->param('bw') === null) {
                throw new InvalidArgumentException('violin(adjust:) computes bandwidths from inline data; with URL data set bw: instead.');
            }
            foreach (['bandwidth' => $c->param('bw') === null ? null : $c->param('bw') * $c->param('adjust', 1), 'steps' => $c->param('n')] as $key => $value) {
                if ($value !== null) {
                    $transform[$key] = $value;
                }
            }
            $c->transforms[] = $transform;
            // Scale each violin to its own maximum density, so every violin spans the same width.
            $c->transforms[] = ['joinaggregate' => [['op' => 'max', 'field' => '__density', 'as' => '__peak']], 'groupby' => $groupby];
            $c->transforms[] = ['calculate' => 'datum.__density / datum.__peak', 'as' => '__width'];
        }

        // The widest violin fills most of its band: the panel size along x divided by the number of levels.
        $levels = $c->data->isInline() ? max(1, count($c->data->levels((string) $c->name('x')))) : 4;
        $panel = $c->flipped ? $c->height : $c->width;
        $panel = is_int($panel) ? $panel : max(300, 20 * $levels);
        $maxWidth = $c->param('width', 0.9) * $panel / $levels;
        if ($c->param('position') === 'dodge') {
            // Dodged violins share their band.
            foreach (['fill', 'color', 'group'] as $aesthetic) {
                if ($c->has($aesthetic) && $c->name($aesthetic) !== $c->name('x')) {
                    $maxWidth /= max(1, count($c->data->levels((string) $c->name($aesthetic))));
                    break;
                }
            }
        }
        $maxWidth = round($maxWidth, 1);

        $encoding = $c->encode(['x' => 'x', 'fill' => 'color', 'color' => 'color', 'group' => 'detail'] + ['opacity' => 'opacity', 'tooltip' => 'tooltip']);
        $encoding['y'] = ['field' => Field::escape($y), 'type' => 'quantitative', 'title' => $y];
        $c->channels['y'] = 'y';
        $encoding['size'] = ['field' => '__width', 'type' => 'quantitative', 'scale' => ['domain' => [0, 1], 'range' => [0, $maxWidth]], 'legend' => null];
        $encoding['order'] = ['field' => Field::escape($y), 'type' => 'quantitative'];
        // A trail is one mark per group, so every level of x (and of any grouping) draws its own violin.
        $encoding['detail'] = array_values(array_filter([['field' => $c->ref('x'), 'type' => 'nominal'], $encoding['detail'] ?? null]));
        $encoding = self::dodge($c, $encoding);

        return [['mark' => $c->mark('trail', 'point', ['opacity' => 0.7]), 'encoding' => $encoding]];
    }

    /** Kernel densities of y per level of x (and grouping), scaled to each group's peak, for violin(). */
    private static function violinDensities(Context $c, string $x, string $y): array
    {
        $groups = array_values(array_unique([$x, ...array_diff($c->groupFields(), ['__facet'])]));
        $sets = [];
        foreach ($c->data->rows ?? [] as $row) {
            $v = $row[$y] ?? null;
            if ((is_int($v) || is_float($v)) && is_finite((float) $v)) {
                $key = array_map(fn ($g) => $row[$g] ?? null, $groups);
                $sets[json_encode($key)] ??= ['key' => array_combine($groups, $key), 'values' => []];
                $sets[json_encode($key)]['values'][] = (float) $v;
            }
        }
        $n = (int) $c->param('n', 200);
        $out = [];
        foreach ($sets as $set) {
            $values = $set['values'];
            if (count($values) < 2) {
                continue; // ggplot2 drops groups with fewer than two points.
            }
            $bw = ($c->param('bw') ?? Fit::bandwidth($values)) * $c->param('adjust', 1);
            [$lo, $hi] = [min($values), max($values)];
            if ($hi - $lo <= 0) {
                continue;
            }
            $density = Fit::kde($values, $bw, $lo, $hi, $n);
            $peak = max(array_column($density, 1));
            foreach ($density as [$at, $d]) {
                $out[] = $set['key'] + [$y => $at, '__width' => $peak > 0 ? $d / $peak : 0.0];
            }
        }

        return $out;
    }

    /** Horizontal when xmin/xmax are mapped instead of ymin/ymax. */
    private static function rangeMap(Context $c): array
    {
        if ($c->has('xmin') && ! $c->has('ymin')) {
            $c->require('xmin', 'xmax');

            return ['y' => 'y', 'xmin' => 'x', 'xmax' => 'x2'];
        }
        $c->require('ymin', 'ymax');

        return ['x' => 'x', 'ymin' => 'y', 'ymax' => 'y2'];
    }

    private static function errorbar(Context $c): array
    {
        $encoding = self::ranged($c, self::rangeMap($c) + ['color' => 'color', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);
        $style = $c->layer->style;
        $mark = ['type' => 'errorbar', 'ticks' => true] + (self::intervalColor($c) === null ? [] : ['color' => self::INTERVAL_COLOR]);
        foreach (['color' => 'color', 'colour' => 'color', 'alpha' => 'opacity', 'opacity' => 'opacity', 'linewidth' => 'thickness', 'size' => 'thickness', 'width' => 'size'] as $from => $to) {
            if (isset($style[$from])) {
                $mark[$to] = $style[$from];
                unset($style[$from]);
            }
        }
        if ($c->param('width') !== null) {
            $mark['ticks'] = ['size' => $c->param('width')];
        }
        $encoding = self::dodge($c, $encoding);

        return [['mark' => $mark + $style, 'encoding' => $encoding]];
    }

    private static function linerange(Context $c): array
    {
        $encoding = self::ranged($c, self::rangeMap($c) + ['color' => 'color', 'size' => 'strokeWidth', 'linetype' => 'strokeDash', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);

        $mark = $c->mark('rule', 'line');
        $mark['color'] ??= self::intervalColor($c);

        return ['mark' => array_filter($mark, fn ($v) => $v !== null), 'encoding' => self::dodge($c, $encoding)];
    }

    /**
     * Vega-Lite draws rules black by default, which vanishes on dark backgrounds. Unmapped intervals take the default
     * mark color instead, the same as their points.
     */
    private static function intervalColor(Context $c): ?string
    {
        return $c->has('color') || isset($c->layer->style['color']) || isset($c->layer->style['colour']) ? null : self::INTERVAL_COLOR;
    }

    private static function pointrange(Context $c): array
    {
        $map = self::rangeMap($c);
        $horizontal = isset($map['xmin']);
        $c->require($horizontal ? 'x' : 'y');
        $range = self::ranged($c, $map + ['color' => 'color', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);
        $style = array_filter(Style::mark($c->layer->style, 'point'), fn ($v) => $v !== null);
        $style['linewidth'] = $c->layer->style['linewidth'] ?? null;
        $style['color'] ??= self::intervalColor($c);
        $rule = ['type' => 'rule'] + array_filter(['strokeWidth' => $style['linewidth'] ?? 1.5, 'color' => $style['color'] ?? null, 'opacity' => $style['opacity'] ?? null], fn ($v) => $v !== null);
        $point = $c->encode([$horizontal ? 'x' : 'y' => $horizontal ? 'x' : 'y', $horizontal ? 'y' : 'x' => $horizontal ? 'y' : 'x', 'color' => 'color', 'fill' => 'fill', 'shape' => 'shape'] + self::COMMON);
        $dot = ['type' => 'point', 'filled' => true] + array_filter(['size' => $style['size'] ?? 50, 'color' => $style['color'] ?? null, 'fill' => $style['fill'] ?? null, 'opacity' => $style['opacity'] ?? null, 'shape' => $style['shape'] ?? null], fn ($v) => $v !== null);

        return [['mark' => $rule, 'encoding' => self::dodge($c, $range)], ['mark' => $dot, 'encoding' => self::dodge($c, $point)]];
    }

    /** hline and vline: rules at fixed positions (one layer each) or at a mapped xintercept/yintercept field. */
    private static function refline(Context $c): array
    {
        $axis = $c->layer->geom === 'hline' ? 'y' : 'x';
        $aesthetic = "{$axis}intercept";
        $values = $c->param('intercept');
        $mark = $c->mark('rule', 'line');
        $extra = $c->encode(['color' => 'color', 'linetype' => 'strokeDash', 'size' => 'strokeWidth'] + self::COMMON);

        if ($values === null) {
            $c->require($aesthetic);

            // The axis keeps the plot's own title rather than adding the reference field's.
            $def = $c->def($aesthetic, $axis);
            if (! isset($def['title']) && $c->has($axis)) {
                $def['title'] = $c->name($axis) ?? $def['title'] ?? null;
            }

            return [['mark' => $mark, 'encoding' => [$axis => array_filter($def, fn ($v) => $v !== null)] + $extra]];
        }
        $c->constant = true;
        $c->channels[$axis] = $axis;
        $layers = [];
        foreach (is_array($values) ? $values : [$values] as $value) {
            if ($value instanceof Expr) {
                // A parameter's value becomes a field named like the axis's own field, so the axis title is unchanged.
                $as = $c->name($axis) ?? $aesthetic;
                $layers[] = ['data' => ['values' => [new \stdClass]], 'transform' => [['calculate' => $value->expr, 'as' => $as]], 'mark' => $mark, 'encoding' => [$axis => ['field' => Field::escape($as), 'type' => 'quantitative']] + $extra];
                continue;
            }
            $layers[] = ['data' => ['values' => [new \stdClass]], 'mark' => $mark, 'encoding' => [$axis => ['datum' => $value]] + $extra];
        }

        return $layers;
    }

    /** Straight lines y = intercept + slope * x across the range of the x data. */
    private static function abline(Context $c): array
    {
        $c->require('x');
        $x = $c->ref('x');
        $group = $c->facetFields === [] ? [] : array_map(Field::escape(...), $c->facetFields);
        $intercepts = (array) $c->param('intercept', 0);
        $slopes = (array) $c->param('slope', 1);
        if (count($intercepts) !== count($slopes)) {
            throw new InvalidArgumentException('abline needs as many intercepts as slopes.');
        }
        $mark = $c->mark('rule', 'line');
        $extra = $c->encode(['color' => 'color', 'linetype' => 'strokeDash', 'size' => 'strokeWidth']);
        $c->channels += ['x' => 'x', 'y' => 'y'];
        $layers = [];
        foreach (array_map(null, $intercepts, $slopes) as [$a, $b]) {
            $transform = [
                ['aggregate' => [['op' => 'min', 'field' => $x, 'as' => '__x1'], ['op' => 'max', 'field' => $x, 'as' => '__x2']]] + ($group === [] ? [] : ['groupby' => $group]),
                ['calculate' => self::number($a).' + '.self::number($b).' * datum.__x1', 'as' => '__y1'],
                ['calculate' => self::number($a).' + '.self::number($b).' * datum.__x2', 'as' => '__y2'],
            ];
            $layers[] = ['transform' => $transform, 'mark' => $mark, 'encoding' => [
                'x' => ['field' => '__x1', 'type' => 'quantitative', 'title' => $c->name('x')], 'x2' => ['field' => '__x2'],
                'y' => ['field' => '__y1', 'type' => 'quantitative', 'title' => $c->name('y')], 'y2' => ['field' => '__y2'],
            ] + $extra];
        }

        return $layers;
    }

    private static function rule(Context $c): array
    {
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'xend' => 'x2', 'yend' => 'y2', 'color' => 'color', 'size' => 'strokeWidth', 'linetype' => 'strokeDash', 'group' => 'detail'] + self::COMMON);

        return [['mark' => $c->mark('rule', 'line'), 'encoding' => $encoding]];
    }

    private static function segment(Context $c): array
    {
        $c->require('x', 'y');
        if (! $c->has('xend') && ! $c->has('yend')) {
            throw new InvalidArgumentException('geom segment needs xend or yend.');
        }
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'xend' => 'x2', 'yend' => 'y2', 'color' => 'color', 'size' => 'strokeWidth', 'linetype' => 'strokeDash', 'group' => 'detail'] + self::COMMON);
        $encoding['x2'] ??= ['field' => $encoding['x']['field']];
        $encoding['y2'] ??= ['field' => $encoding['y']['field']];

        return [['mark' => $c->mark('rule', 'line'), 'encoding' => $encoding]];
    }

    private static function text(Context $c): array
    {
        $c->require('label');
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'label' => 'text', 'color' => 'color', 'size' => 'size', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);
        $defaults = $c->layer->geom === 'label' ? ['fontWeight' => 'bold'] : [];

        return [['mark' => $c->mark('text', 'text', $defaults), 'encoding' => $encoding]];
    }

    private static function tile(Context $c): array
    {
        $c->require('x', 'y');
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'fill' => 'color', 'color' => 'stroke'] + self::COMMON);
        foreach (['x', 'y'] as $axis) {
            if (($c->fieldOf($axis)?->type === null) && $encoding[$axis]['type'] === 'quantitative') {
                $encoding[$axis]['type'] = 'ordinal';
            }
        }
        // ggplot2 puts the first level at the bottom; Vega-Lite puts it at the top.
        if (in_array($encoding['y']['type'], ['ordinal', 'nominal'], true) && ! array_key_exists('sort', $encoding['y'])) {
            $encoding['y']['sort'] = 'descending';
        }

        return [['mark' => $c->mark('rect', 'filled'), 'encoding' => $encoding]];
    }

    private static function rect(Context $c): array
    {
        $c->require('xmin', 'xmax', 'ymin', 'ymax');
        $encoding = $c->encode(['xmin' => 'x', 'xmax' => 'x2', 'ymin' => 'y', 'ymax' => 'y2', 'fill' => 'color', 'color' => 'stroke'] + self::COMMON);

        return [['mark' => $c->mark('rect', 'filled'), 'encoding' => $encoding]];
    }

    private static function bin2d(Context $c): array
    {
        $c->require('x', 'y');
        if (self::server($c, ['x', 'y'])) {
            [$x, $y] = [(string) $c->name('x'), (string) $c->name('y')];
            $c->computed = Bin::grid($c->data->source(), $x, $y, $c->param('bins', 30), groups: self::binGroups($c));
            $c->transforms = [];
            $c->channels += ['x' => 'x', 'y' => 'y', 'fill' => 'color'];

            return [['mark' => $c->mark('rect', 'filled'), 'encoding' => [
                'x' => ['field' => Field::escape($x.'_start'), 'type' => 'quantitative', 'title' => $x], 'x2' => ['field' => Field::escape($x.'_end')],
                'y' => ['field' => Field::escape($y.'_start'), 'type' => 'quantitative', 'title' => $y], 'y2' => ['field' => Field::escape($y.'_end')],
                'color' => ['field' => 'count', 'type' => 'quantitative', 'title' => 'count'],
            ]]];
        }
        $encoding = $c->encode(['x' => 'x', 'y' => 'y'] + self::COMMON);
        $bins = $c->param('bins', 30);
        [$bx, $by] = is_array($bins) ? $bins : [$bins, $bins];
        $encoding['x']['bin'] = ['maxbins' => $bx];
        $encoding['y']['bin'] = ['maxbins' => $by];
        $encoding['x']['title'] ??= $c->name('x');
        $encoding['y']['title'] ??= $c->name('y');
        $encoding['color'] = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count'];
        $c->channels['fill'] = 'color';

        return [['mark' => $c->mark('rect', 'filled'), 'encoding' => $encoding]];
    }

    /**
     * Whether a binning geom computes in PHP: compute 'server', or 'auto' above Config::$serverRows inline rows. Binning
     * in PHP reads the data columns directly, so 'auto' leaves the work to Vega-Lite when a layer filter, a plot
     * transform, a computed field, or a non-numeric axis needs the browser's data flow.
     *
     * @param list<string> $axes The aesthetics being binned.
     */
    private static function server(Context $c, array $axes, bool $always = false): bool
    {
        $compute = $always ? 'server' : $c->param('compute', 'auto');
        if (! in_array($compute, ['auto', 'server', 'browser'], true)) {
            throw new InvalidArgumentException("compute must be 'auto', 'server', or 'browser'.");
        }
        if ($compute === 'browser') {
            return false;
        }
        $blocker = match (true) {
            ! $c->data->isInline() => 'inline data',
            $c->layer->filter !== null => 'no layer filter',
            $c->transformed => 'no plot transform()',
            default => null,
        };
        foreach ($axes as $axis) {
            $blocker ??= $c->fieldOf($axis)?->calculate !== null || $c->fieldOf($axis)?->aggregate !== null ? "a plain {$axis} field" : null;
            $blocker ??= $c->type($axis) !== 'quantitative' ? "a numeric {$axis}" : null;
        }
        if ($blocker !== null) {
            if ($compute === 'server') {
                throw new InvalidArgumentException("Binning {$c->layer->geom} in PHP needs {$blocker}; use compute: 'browser' or bin2d().");
            }

            return false;
        }

        return $compute === 'server' || $c->data->count() > Config::$serverRows;
    }

    /** Group fields for PHP binning: the computed __facet field is rebuilt by the plot's transform afterwards. */
    private static function binGroups(Context $c): array
    {
        return array_values(array_diff($c->groupFields(), ['__facet']));
    }

    /**
     * Hexagonal bins, counted in PHP (as ggplot2's geom_hex): each hexagon is a point with a hexagon shape, sized
     * from the panel width so neighbors tile. The scales are fixed to the data range so hexagons meet exactly.
     */
    private static function hex(Context $c): array
    {
        $c->require('x', 'y');
        self::server($c, ['x', 'y'], always: true);
        [$x, $y] = [(string) $c->name('x'), (string) $c->name('y')];
        $bins = (int) $c->param('bins', 30);
        $rows = $c->data->source();
        $xlim = Bin::range($rows, $x);
        $ylim = Bin::range($rows, $y);
        [$width, $height] = [is_int($c->width) ? $c->width : 300, is_int($c->height) ? $c->height : 300];
        if ($c->flipped) {
            [$width, $height] = [$height, $width];
        }
        $c->computed = Bin::hex($rows, $x, $y, $bins, $xlim, $ylim, $height / $width, self::binGroups($c));
        $c->transforms = [];
        $c->channels += ['x' => 'x', 'y' => 'y', 'fill' => 'color'];
        $diameter = 2 * ($width / $bins) / sqrt(3);
        // Pointy-top hexagons in Vega's unit square (size is the bounding box area in square pixels). coordFlip()
        // turns the lattice a quarter turn, so the hexagons become flat-topped.
        $shape = $c->flipped ? 'M-1,0L-0.5,-0.866L0.5,-0.866L1,0L0.5,0.866L-0.5,0.866Z' : 'M0,-1L0.866,-0.5L0.866,0.5L0,1L-0.866,0.5L-0.866,-0.5Z';
        $exact = ['nice' => false, 'zero' => false, 'padding' => 0];

        return [['mark' => $c->mark('point', 'point', ['shape' => $shape, 'filled' => true, 'opacity' => 1, 'size' => round($diameter * $diameter, 2), 'strokeWidth' => 0]), 'encoding' => [
            'x' => ['field' => Field::escape($x), 'type' => 'quantitative', 'scale' => ['domain' => $xlim] + $exact],
            'y' => ['field' => Field::escape($y), 'type' => 'quantitative', 'scale' => ['domain' => $ylim] + $exact],
            'color' => ['field' => 'count', 'type' => 'quantitative', 'title' => 'count'],
        ] + $c->encode(self::COMMON)]];
    }

    /**
     * Fitted curves. With se (the default for loess, lm, quad, and poly on inline data) the fit and its confidence band
     * are computed in PHP; otherwise Vega-Lite's regression or loess transform fits the curve in the browser.
     */
    private static function smooth(Context $c): array
    {
        $c->require('x', 'y');
        $method = $c->param('method', 'loess');
        $vlMethod = match ($method) {
            'lm', 'linear' => 'linear', 'poly' => 'poly', 'quad' => 'quad', 'log' => 'log', 'exp' => 'exp', 'pow' => 'pow', 'loess' => 'loess',
            default => throw new InvalidArgumentException("Unknown smooth method '{$method}'; use lm, poly, quad, log, exp, pow, or loess."),
        };
        $degree = match ($vlMethod) { 'linear' => 1, 'quad' => 2, 'poly' => $c->param('order', 3), default => null };
        $numeric = fn (string $aesthetic) => $c->fieldOf($aesthetic)?->calculate === null && $c->type($aesthetic) === 'quantitative';
        $fittable = ($degree !== null || $vlMethod === 'loess') && $c->data->isInline() && $numeric('x') && $numeric('y');
        // Loess bands cost O(n²) in PHP; past LOESS_ROWS the default leaves the fit to Vega-Lite, without a band.
        $se = $c->param('se') ?? ($fittable && ($vlMethod !== 'loess' || $c->data->count() <= self::LOESS_ROWS));
        if ($c->param('n', 80) < 2) {
            throw new InvalidArgumentException('smooth needs n >= 2 points.');
        }
        $colorMapped = $c->has('color');
        $lineMark = $c->mark('line', 'line', ['strokeWidth' => 2] + ($colorMapped ? [] : ['color' => self::SMOOTH_COLOR]));
        $lineEncoding = $c->encode(['x' => 'x', 'y' => 'y', 'color' => 'color', 'linetype' => 'strokeDash', 'group' => 'detail']);
        if ($lineEncoding['x']['type'] !== 'temporal') {
            $lineEncoding['x']['type'] = 'quantitative';
        }
        $lineEncoding['y']['type'] = 'quantitative';
        $span = $vlMethod === 'loess' ? (float) $c->param('span', 0.75) : null;
        $fit = fn () => Fit::smooth($c->data->rows ?? [], (string) $c->name('x'), (string) $c->name('y'), $c->groupFields(), $degree ?? 2, (float) $c->param('level', 0.95), (int) $c->param('n', 80), $span);
        if ($se && $span !== null && $c->param('se') === null && ($c->computed = $fit()) === []) {
            // A default loess band that no group has enough points for leaves the curve to Vega-Lite.
            [$se, $c->computed] = [false, null];
        }

        if (! $se) {
            $transform = $vlMethod === 'loess'
                ? ['loess' => $c->ref('y'), 'on' => $c->ref('x'), 'bandwidth' => $c->param('span', 0.75)]
                : ['regression' => $c->ref('y'), 'on' => $c->ref('x'), 'method' => $vlMethod] + ($vlMethod === 'poly' ? ['order' => $degree] : []);
            if ($c->groupby() !== []) {
                $transform['groupby'] = $c->groupby();
            }
            $transform['as'] = [(string) $c->name('x'), (string) $c->name('y')];
            $c->transforms[] = $transform;

            return [['mark' => $lineMark, 'encoding' => $lineEncoding]];
        }

        if ($degree === null && $vlMethod !== 'loess') {
            throw new InvalidArgumentException("se bands are computed for method loess, lm, quad, and poly; use se: false with method {$method}.");
        }
        if (! $c->data->isInline()) {
            throw new InvalidArgumentException('se bands need inline data; use se: false with URL data.');
        }
        if (! $fittable) {
            throw new InvalidArgumentException('se bands need numeric x and y columns in the data (not dates or calc() fields); use se: false.');
        }
        $c->computed ??= $fit();
        if ($c->computed === []) {
            throw new InvalidArgumentException($span === null ? "smooth has no group with enough distinct x values to fit a degree {$degree} curve." : "smooth has no group with enough points for a loess fit with span {$span}.");
        }
        $c->transforms = array_values(array_filter($c->transforms, fn ($t) => ! isset($t['calculate'])));
        $band = ['x' => $lineEncoding['x'], 'y' => ['field' => '__ymin', 'type' => 'quantitative', 'title' => $c->name('y')], 'y2' => ['field' => '__ymax']];
        foreach (['group' => 'detail', 'color' => 'detail'] as $aesthetic => $channel) {
            if ($c->has($aesthetic)) {
                $band[$channel] = ['field' => $c->ref($aesthetic), 'type' => 'nominal'];
            }
        }

        return [
            ['mark' => ['type' => 'area', 'color' => $c->layer->style['fill'] ?? '#999999', 'opacity' => 0.4], 'encoding' => $band],
            ['mark' => $lineMark, 'encoding' => $lineEncoding],
        ];
    }

    /** A function of x drawn over [from, to], as ggplot2's geom_function. */
    private static function curve(Context $c): array
    {
        $fn = $c->param('fn');
        [$from, $to] = [(float) $c->param('from'), (float) $c->param('to')];
        $n = (int) $c->param('n', 101);
        if (! is_callable($fn) || ! ($to > $from) || $n < 2) {
            throw new InvalidArgumentException('curve needs a callable and from < to, with n >= 2.');
        }
        $x = $c->name('x') ?? 'x';
        $y = $c->name('y') ?? 'y';
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $xi = $from + ($to - $from) * $i / ($n - 1);
            $yi = $fn($xi);
            $rows[] = [$x => $xi, $y => is_float($yi) && ! is_finite($yi) ? null : $yi];
        }
        $c->computed = $rows;
        $c->constant = true;
        $c->channels += ['x' => 'x', 'y' => 'y'];
        $extra = $c->encode(['color' => 'color', 'linetype' => 'strokeDash']);

        return [['mark' => $c->mark('line', 'line'), 'encoding' => ['x' => ['field' => Field::escape($x), 'type' => 'quantitative'], 'y' => ['field' => Field::escape($y), 'type' => 'quantitative']] + $extra]];
    }

    /** Marginal ticks along the bottom (b) and/or left (l) edges. */
    private static function rug(Context $c): array
    {
        $sides = $c->param('sides', $c->has('x') && $c->has('y') ? 'bl' : ($c->has('y') ? 'l' : 'b'));
        $length = $c->param('length', 8);
        $mark = $c->mark('rule', 'line', ['opacity' => 0.5]);
        $extra = $c->encode(['color' => 'color'] + self::COMMON);
        // Ticks sit along the axis that shows their data. coordFlip() swaps channels afterwards, so a flipped
        // plot's x rug is placed for the left edge and its y rug for the bottom edge.
        $bottom = ['value' => new Expr('height')];
        $bottom2 = ['value' => new Expr('height - '.$length)];
        $layers = [];
        if (str_contains($sides, 'b')) {
            $c->require('x');
            $layers[] = ['mark' => $mark, 'encoding' => ['x' => $c->def('x', 'x')] + ($c->flipped ? ['y' => ['value' => 0], 'y2' => ['value' => $length]] : ['y' => $bottom, 'y2' => $bottom2]) + $extra];
        }
        if (str_contains($sides, 'l')) {
            $c->require('y');
            $layers[] = ['mark' => $mark, 'encoding' => ['y' => $c->def('y', 'y')] + ($c->flipped ? ['x' => $bottom, 'x2' => $bottom2] : ['x' => ['value' => 0], 'x2' => ['value' => $length]]) + $extra];
        }

        return $layers;
    }

    private static function ecdf(Context $c): array
    {
        $c->require('x');
        $window = ['window' => [['op' => 'cume_dist', 'as' => 'ecdf']], 'sort' => [['field' => $c->ref('x')]]];
        if ($c->groupby() !== []) {
            $window['groupby'] = $c->groupby();
        }
        $c->transforms[] = $window;
        $encoding = $c->encode(['x' => 'x', 'color' => 'color', 'linetype' => 'strokeDash', 'group' => 'detail'] + self::COMMON);
        $encoding['y'] = ['field' => 'ecdf', 'type' => 'quantitative', 'title' => 'ecdf'];
        $c->channels['y'] = 'y';

        return [['mark' => $c->mark('line', 'line', ['interpolate' => 'step-after']), 'encoding' => $encoding]];
    }

    /**
     * Sample quantiles against standard normal quantiles at R's ppoints(n): (i - 3/8) / (n + 1/4) for n <= 10 and
     * (i - 1/2) / n otherwise, as qqnorm and ggplot2's stat_qq use. Each group's sorted values are its sample
     * quantiles, at probabilities from its own size.
     */
    private static function qqTransforms(Context $c): array
    {
        $c->require('sample');
        $value = 'datum['.json_encode((string) $c->name('sample')).']';
        $group = $c->groupby() === [] ? [] : ['groupby' => $c->groupby()];

        return [
            ['filter' => "isValid({$value}) && isFinite({$value})"],
            ['window' => [['op' => 'row_number', 'as' => '__rank']], 'sort' => [['field' => $c->ref('sample')]]] + $group,
            ['joinaggregate' => [['op' => 'count', 'as' => '__n']]] + $group,
            ['calculate' => 'datum.__n <= 10 ? (datum.__rank - 0.375) / (datum.__n + 0.25) : (datum.__rank - 0.5) / datum.__n', 'as' => '__prob'],
            ['calculate' => 'quantileNormal(datum.__prob)', 'as' => 'theoretical'],
        ];
    }

    private static function qq(Context $c): array
    {
        array_push($c->transforms, ...self::qqTransforms($c));
        $encoding = ['x' => ['field' => 'theoretical', 'type' => 'quantitative', 'title' => 'theoretical'], 'y' => ['field' => $c->ref('sample'), 'type' => 'quantitative', 'title' => $c->name('sample')]];
        $c->channels += ['x' => 'x', 'y' => 'y', 'sample' => 'y'];
        $encoding += $c->encode(['color' => 'color', 'shape' => 'shape'] + self::COMMON);

        return [['mark' => $c->mark('point', 'point', ['filled' => true]), 'encoding' => $encoding]];
    }

    /** The line through the first and third quartiles, as ggplot2's stat_qq_line. */
    private static function qqLine(Context $c): array
    {
        array_push($c->transforms, ...self::qqTransforms($c));
        $sample = $c->ref('sample');
        $group = $c->groupby();
        array_push(
            $c->transforms,
            ['joinaggregate' => [['op' => 'q1', 'field' => $sample, 'as' => '__q1'], ['op' => 'q3', 'field' => $sample, 'as' => '__q3']]] + ($group === [] ? [] : ['groupby' => $group]),
            ['calculate' => '(datum.__q3 - datum.__q1) / (quantileNormal(0.75) - quantileNormal(0.25))', 'as' => '__slope'],
            ['calculate' => 'datum.__q1 - datum.__slope * quantileNormal(0.25)', 'as' => '__intercept'],
            ['aggregate' => [['op' => 'min', 'field' => 'theoretical', 'as' => '__x1'], ['op' => 'max', 'field' => 'theoretical', 'as' => '__x2']], 'groupby' => ['__slope', '__intercept', ...$group]],
            ['calculate' => 'datum.__intercept + datum.__slope * datum.__x1', 'as' => '__y1'],
            ['calculate' => 'datum.__intercept + datum.__slope * datum.__x2', 'as' => '__y2'],
        );
        $c->channels += ['x' => 'x', 'y' => 'y', 'sample' => 'y'];
        $encoding = [
            'x' => ['field' => '__x1', 'type' => 'quantitative', 'title' => 'theoretical'], 'x2' => ['field' => '__x2'],
            'y' => ['field' => '__y1', 'type' => 'quantitative', 'title' => $c->name('sample')], 'y2' => ['field' => '__y2'],
        ] + $c->encode(['color' => 'color', 'linetype' => 'strokeDash']);

        return [['mark' => $c->mark('rule', 'line'), 'encoding' => $encoding]];
    }

    /**
     * Bars in polar coordinates. theta y: a pie, slices stacked by fill with angles from y (or counts). theta x: a
     * rose, one equal slice per level of x with radius y (or the count).
     */
    private static function polarBar(Context $c): array
    {
        $polar = $c->polar;
        $sweep = 2 * M_PI * $polar['direction'];
        $theta = ['scale' => ['range' => [round($polar['start'], 6), round($polar['start'] + $sweep, 6)]]];
        $value = $c->has('y') ? $c->def('y', 'theta') : null;
        $defaults = $polar['innerRadius'] > 0 && $polar['theta'] === 'y' ? ['innerRadius' => $polar['innerRadius']] : [];
        $common = $c->encode(['color' => 'stroke'] + self::COMMON);
        if ($polar['theta'] === 'y') {
            if ($c->has('x') && $c->data->isInline() && count($c->data->levels((string) $c->name('x'))) > 1) {
                throw new InvalidArgumentException("coordPolar(theta: 'y') draws one ring; map x to a constant or leave it out.");
            }
            $encoding = ['theta' => ($value ?? ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count']) + ['stack' => true] + $theta];
            $c->channels['y'] = 'theta';
            $fill = $c->has('fill') ? 'fill' : ($c->has('color') ? 'color' : null);
            if ($fill !== null) {
                $encoding['color'] = $c->def($fill, 'color');
                $encoding['order'] = ['field' => $c->ref($fill), 'type' => 'ordinal', 'sort' => 'descending'];
            }

            return [['mark' => $c->mark('arc', 'filled', $defaults), 'encoding' => $encoding + $common]];
        }
        $c->require('x');
        $x = $c->ref('x');
        $fill = $c->has('fill') ? 'fill' : ($c->has('color') ? 'color' : null);
        if ($fill !== null && $c->name($fill) !== $c->name('x')) {
            throw new InvalidArgumentException("coordPolar(theta: 'x') draws one slice per level of x; map fill to x itself.");
        }
        // One row per level: its count or summed y, and an equal share of the circle.
        $c->transforms[] = $value === null
            ? ['aggregate' => [['op' => 'count', 'as' => '__radius']], 'groupby' => [$x]]
            : ['aggregate' => [['op' => 'sum', 'field' => $c->ref('y'), 'as' => '__radius']], 'groupby' => [$x]];
        // Slices in level order, each spanning one unit of a theta scale from 0 to the number of levels.
        $levels = $c->fieldOf('x')?->sort ?? null;
        if (is_array($levels)) {
            $c->transforms[] = ['calculate' => 'indexof('.json_encode(array_values($levels)).', datum['.json_encode((string) $c->name('x')).'])', 'as' => '__level'];
        }
        $c->transforms[] = ['window' => [['op' => 'row_number', 'as' => '__slice_end']], 'sort' => [['field' => is_array($levels) ? '__level' : $x]]];
        $c->transforms[] = ['calculate' => 'datum.__slice_end - 1', 'as' => '__slice_start'];
        $c->channels += ['x' => 'color', 'y' => 'radius'];
        $encoding = [
            // The scale's domain comes from the theta field, so it carries the slice ends: 0 to the number of levels.
            'theta' => ['field' => '__slice_end', 'type' => 'quantitative', 'scale' => $theta['scale'] + ['zero' => true, 'nice' => false]],
            'theta2' => ['field' => '__slice_start'],
            'radius' => ['field' => '__radius', 'type' => 'quantitative', 'title' => $value === null ? 'count' : $c->name('y'), 'scale' => ['type' => 'linear', 'zero' => true, 'range' => [$polar['innerRadius'], self::outerRadius($c)]]],
            'color' => ['field' => $x, 'type' => 'nominal', 'title' => $c->name('x')] + (is_array($levels) ? ['sort' => $levels] : []),
        ];

        return [['mark' => $c->mark('arc', 'filled', $defaults + ['stroke' => '#FFFFFF', 'strokeWidth' => 1]), 'encoding' => $encoding + $common]];
    }

    /**
     * The outer radius in pixels: half the smaller side. Vega-Lite's default range reads the width and height
     * signals, which a concatenated view doesn't define, so the radius is fixed here.
     */
    private static function outerRadius(Context $c): int|float
    {
        [$width, $height] = [is_int($c->width) ? $c->width : 300, is_int($c->height) ? $c->height : 300];

        return min($width, $height) / 2;
    }

    private static function arc(Context $c): array
    {
        $encoding = $c->encode(['theta' => 'theta', 'radius' => 'radius', 'fill' => 'color', 'color' => 'stroke', 'order' => 'order'] + self::COMMON);
        if (! isset($encoding['theta'])) {
            $encoding['theta'] = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count'];
            $c->channels['theta'] = 'theta';
        }
        if (isset($encoding['radius'])) {
            $encoding['radius']['scale'] ??= ['type' => 'sqrt', 'zero' => true, 'range' => [$c->param('innerRadius', 0), self::outerRadius($c)]];
        }
        $defaults = $c->param('innerRadius') !== null ? ['innerRadius' => $c->param('innerRadius')] : [];

        return [['mark' => $c->mark('arc', 'filled', $defaults), 'encoding' => $encoding]];
    }

    /**
     * Polygons through the points in data order, one per group (or fill level). Each is a filled line mark, so
     * a group can hold several rings separated by rows with null x and y, and rings drawn the opposite way are holes.
     */
    private static function polygon(Context $c): array
    {
        $c->require('x', 'y');
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'fill' => 'fill', 'color' => 'stroke', 'group' => 'detail', 'detail' => 'detail', 'order' => 'order'] + self::COMMON);
        if (! isset($encoding['order'])) {
            $c->transforms[] = ['window' => [['op' => 'row_number', 'as' => '__order']]];
            $encoding['order'] = ['field' => '__order', 'type' => 'quantitative'];
        }
        if (isset($encoding['fill']['field']) && ! isset($encoding['detail'])) {
            $encoding['detail'] = ['field' => $encoding['fill']['field'], 'type' => 'nominal'];
        }
        $defaults = ['strokeWidth' => $c->has('color') || isset($c->layer->style['color']) || isset($c->layer->style['colour']) ? 1 : 0];
        if (! $c->has('fill')) {
            $defaults['fill'] = '#333333';
        }
        if (isset($encoding['fill']) && ! array_key_exists('legend', $encoding['fill'])) {
            $encoding['fill']['legend'] = ['symbolType' => 'square', 'symbolStrokeWidth' => 0];
        }

        return [['mark' => $c->mark('line', 'filled', $defaults), 'encoding' => $encoding]];
    }

    /**
     * Contour lines or filled contour bands, of a 2D kernel density of x and y (density_2d, as ggplot2 with
     * MASS::kde2d) or of a z field on a regular x-by-y grid (contour). Breaks are shared by every group: bins,
     * binwidth, explicit breaks, or pretty(range of z, 10). Filled bands are drawn lowest first, each as the region
     * above its lower break, so higher bands cover the inside of lower ones.
     */
    private static function contour(Context $c, bool $density, bool $filled): array
    {
        $c->require('x', 'y');
        if (! $density) {
            $c->require('z');
        }
        if (! $c->data->isInline()) {
            throw new InvalidArgumentException("{$c->layer->geom} computes contours in PHP and needs inline data.");
        }
        foreach ($density ? ['x', 'y'] : ['x', 'y', 'z'] as $aesthetic) {
            if ($c->fieldOf($aesthetic)?->calculate !== null || $c->fieldOf($aesthetic)?->aggregate !== null || $c->type($aesthetic) !== 'quantitative') {
                throw new InvalidArgumentException("{$c->layer->geom} needs a plain numeric {$aesthetic} field.");
            }
        }
        [$x, $y] = [(string) $c->name('x'), (string) $c->name('y')];
        $z = $density ? null : (string) $c->name('z');
        $levelColor = ! $filled && $c->name('color') === 'level';
        $groups = array_values(array_diff($c->groupFields(), ['__facet', 'level']));

        $sets = [];
        [$xlo, $xhi, $ylo, $yhi] = [INF, -INF, INF, -INF];
        foreach ($c->data->rows ?? [] as $row) {
            [$xi, $yi] = [$row[$x] ?? null, $row[$y] ?? null];
            if (! is_numeric($xi) || ! is_numeric($yi) || ! is_finite((float) $xi) || ! is_finite((float) $yi)) {
                continue;
            }
            $key = [];
            foreach ($groups as $group) {
                $key[$group] = $row[$group] ?? null;
            }
            $sets[json_encode($key)] ??= ['key' => $key, 'rows' => []];
            $sets[json_encode($key)]['rows'][] = $row;
            [$xlo, $xhi, $ylo, $yhi] = [min($xlo, (float) $xi), max($xhi, (float) $xi), min($ylo, (float) $yi), max($yhi, (float) $yi)];
        }
        if ($sets === []) {
            throw new InvalidArgumentException("{$c->layer->geom} has no rows with numeric x and y.");
        }

        $grids = [];
        foreach ($sets as $id => $set) {
            if (! $density) {
                $grids[$id] = Contour::grid($set['rows'], $x, $y, (string) $z);
                continue;
            }
            $xs = array_map(fn ($r) => (float) $r[$x], $set['rows']);
            $ys = array_map(fn ($r) => (float) $r[$y], $set['rows']);
            if (count($xs) < 2) {
                continue; // ggplot2 drops groups too small for a density.
            }
            $h = $c->param('h');
            $h = $h === null ? [Contour::bandwidthNrd($xs), Contour::bandwidthNrd($ys)] : (is_array($h) ? array_values($h) : [$h, $h]);
            $h = array_map(fn ($v) => $v * $c->param('adjust', 1), $h);
            if (! ($h[0] > 0) || ! ($h[1] > 0)) {
                continue;
            }
            $grid = Contour::kde2d($xs, $ys, $h, [$xlo, $xhi, $ylo, $yhi], (int) $c->param('n', 100));
            $scale = match ($c->param('contourVar', 'density')) {
                'density' => 1.0,
                'count' => (float) count($xs),
                'ndensity' => 1 / max(1e-300, max(array_map(max(...), $grid['z']))),
                default => throw new InvalidArgumentException("contourVar must be 'density', 'ndensity', or 'count'."),
            };
            $grid['z'] = array_map(fn ($column) => array_map(fn ($v) => $v * $scale, $column), $grid['z']);
            $grids[$id] = $grid;
        }
        [$zlo, $zhi] = [INF, -INF];
        foreach ($grids as $grid) {
            foreach ($grid['z'] as $column) {
                foreach ($column as $v) {
                    if ($v !== null) {
                        [$zlo, $zhi] = [min($zlo, $v), max($zhi, $v)];
                    }
                }
            }
        }
        if (! is_finite($zlo)) {
            throw new InvalidArgumentException("{$c->layer->geom} has no group with enough points to contour.");
        }
        $breaks = Contour::breaks($zlo, $zhi, $c->param('bins'), $c->param('binwidth'), $c->param('breaks'));

        $rows = [];
        $order = 0;
        $labels = [];
        if ($filled) {
            // Band labels as ggplot2's: the lower and upper breaks each formatted as R's format(digits = 3).
            $low = Contour::format(array_slice($breaks, 0, -1));
            $high = Contour::format(array_slice($breaks, 1));
            for ($b = 0; $b < count($breaks) - 1; $b++) {
                if ($breaks[$b] > $zhi || $breaks[$b + 1] < $zlo) {
                    continue;
                }
                $label = "({$low[$b]}, {$high[$b]}]";
                $labels[] = $label;
                foreach ($sets as $id => $set) {
                    if (! isset($grids[$id])) {
                        continue;
                    }
                    foreach (Contour::region($grids[$id], $breaks[$b]) as $ring) {
                        foreach ($ring as [$px, $py]) {
                            $rows[] = $set['key'] + [$x => $px, $y => $py, 'level' => $label, '__order' => $order++];
                        }
                        $rows[] = $set['key'] + [$x => null, $y => null, 'level' => $label, '__order' => $order++];
                    }
                }
            }
        } else {
            $piece = 0;
            foreach ($sets as $id => $set) {
                if (! isset($grids[$id])) {
                    continue;
                }
                foreach ($breaks as $level) {
                    foreach (Contour::lines($grids[$id], $level) as $line) {
                        $piece++;
                        foreach ($line as [$px, $py]) {
                            $rows[] = $set['key'] + [$x => $px, $y => $py, 'level' => $level, '__piece' => $piece, '__order' => $order++];
                        }
                    }
                }
            }
        }
        $c->computed = $rows;
        $c->transforms = [];
        $c->channels += ['x' => 'x', 'y' => 'y'];
        $encoding = [
            'x' => ['field' => Field::escape($x), 'type' => 'quantitative', 'title' => $x],
            'y' => ['field' => Field::escape($y), 'type' => 'quantitative', 'title' => $y],
            'order' => ['field' => '__order', 'type' => 'quantitative'],
        ];
        $grouping = array_map(fn ($g) => ['field' => Field::escape($g), 'type' => 'nominal'], $groups);
        if ($filled) {
            $c->channels['fill'] = 'fill';
            $encoding['fill'] = ['field' => 'level', 'type' => 'ordinal', 'sort' => $labels, 'title' => 'level', 'scale' => ['scheme' => 'viridis'], 'legend' => ['symbolType' => 'square', 'symbolStrokeWidth' => 0]];
            $encoding['detail'] = [['field' => 'level', 'type' => 'ordinal'], ...$grouping];
            $encoding += $c->encode(['color' => 'stroke'] + self::COMMON);
            $stroked = $c->has('color') || isset($c->layer->style['color']) || isset($c->layer->style['colour']);

            return [['mark' => $c->mark('line', 'filled', ['strokeWidth' => $stroked ? 0.5 : 0]), 'encoding' => $encoding]];
        }
        $encoding['detail'] = [['field' => '__piece', 'type' => 'nominal'], ...$grouping];
        if ($levelColor) {
            $c->channels['color'] = 'color';
            $encoding['color'] = ['field' => 'level', 'type' => 'quantitative', 'title' => 'level'];
        }
        $encoding += $c->encode(['color' => 'color', 'linetype' => 'strokeDash'] + self::COMMON);
        $mapped = $levelColor || $c->has('color');

        return [['mark' => $c->mark('line', 'line', $mapped ? [] : ['color' => self::SMOOTH_COLOR]), 'encoding' => $encoding]];
    }

    /**
     * Summaries of y at each x (per group), computed in PHP and drawn with another geom, as ggplot2's stat_summary.
     * funData gives the middle and range together (mean_se, mean_cl_normal, mean_sdl, median_hilow, or a callable
     * returning [y, ymin, ymax]); fun, funMin, and funMax give them separately (mean, median, min, max, sum, sd, or
     * callables of the values). With a discrete y and a continuous x, x is summarized at each y instead.
     */
    private static function summary(Context $c): array
    {
        $c->require('x', 'y');
        if (! $c->data->isInline()) {
            throw new InvalidArgumentException('summary computes in PHP and needs inline data.');
        }
        $horizontal = $c->discrete('y') && ! $c->discrete('x');
        [$category, $value] = $horizontal ? ['y', 'x'] : ['x', 'y'];
        foreach ([$category, $value] as $aesthetic) {
            if ($c->fieldOf($aesthetic)?->calculate !== null || $c->fieldOf($aesthetic)?->aggregate !== null) {
                throw new InvalidArgumentException("summary needs a plain {$aesthetic} field.");
            }
        }
        [$at, $of] = [(string) $c->name($category), (string) $c->name($value)];
        $groups = array_values(array_unique([$at, ...array_diff($c->groupFields(), ['__facet'])]));
        $sets = [];
        foreach ($c->data->rows ?? [] as $row) {
            $v = $row[$of] ?? null;
            if (! is_numeric($v) || ! is_finite((float) $v) || ($row[$at] ?? null) === null) {
                continue;
            }
            $key = [];
            foreach ($groups as $group) {
                $key[$group] = $row[$group] ?? null;
            }
            $sets[json_encode($key)] ??= ['key' => $key, 'values' => []];
            $sets[json_encode($key)]['values'][] = (float) $v;
        }
        [$fun, $funData, $funMin, $funMax] = [$c->param('fun'), $c->param('funData'), $c->param('funMin'), $c->param('funMax')];
        if ($fun === null && $funData === null && $funMin === null && $funMax === null) {
            $funData = 'mean_se';
        }
        $args = (array) $c->param('funArgs', []);
        [$min, $max] = $horizontal ? ['xmin', 'xmax'] : ['ymin', 'ymax'];
        $rows = [];
        foreach ($sets as $set) {
            $values = $set['values'];
            if ($funData !== null) {
                $out = self::summarise($funData, $values, $args);
                [$mid, $lo, $hi] = is_array($out) ? array_values($out) + [null, null, null] : [$out, null, null];
            } else {
                $mid = $fun === null ? null : self::summarise($fun, $values, $args);
                $lo = $funMin === null ? null : self::summarise($funMin, $values, $args);
                $hi = $funMax === null ? null : self::summarise($funMax, $values, $args);
            }
            $finite = fn ($v) => is_int($v) || (is_float($v) && is_finite($v)) ? $v : null;
            $rows[] = $set['key'] + array_filter([$of => $finite($mid), $min => $finite($lo), $max => $finite($hi)], fn ($v) => $v !== null);
        }
        $ranged = $funData !== null || $funMin !== null || $funMax !== null;
        $geom = $c->param('geom', $ranged ? 'pointrange' : 'point');
        if (! in_array($geom, ['pointrange', 'errorbar', 'linerange', 'crossbar', 'point', 'line', 'path', 'col', 'bar', 'area', 'ribbon'], true)) {
            throw new InvalidArgumentException("summary cannot draw with geom '{$geom}'.");
        }
        $aes = $c->aes;
        if ($ranged) {
            $aes = $aes->with($min, $min)->with($max, $max);
        }
        $params = array_diff_key($c->layer->params, array_flip(['fun', 'funData', 'funMin', 'funMax', 'funArgs', 'geom']));
        $layer = new Layer($geom === 'bar' ? 'col' : $geom, $aes, $params, $c->layer->style, Data::from($rows), raw: $c->layer->raw);
        $inner = new Context($layer, $aes, Data::from($rows), $c->facetFields, $c->flipped, $c->width, $c->height);
        $specs = self::compile($inner);
        $c->computed = $inner->computed ?? $rows;
        $c->channels = $inner->channels;
        $c->transforms = $inner->transforms;

        return $specs;
    }

    /** One summary function applied to values: a name, or a callable. */
    private static function summarise(string|callable $fn, array $values, array $args): mixed
    {
        if (! is_string($fn)) {
            return $fn($values);
        }
        $n = count($values);
        $mean = array_sum($values) / max(1, $n);
        $sd = $n > 1 ? sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($n - 1)) : NAN;
        $sorted = $values;
        sort($sorted);
        $level = (float) ($args['conf'] ?? $args['level'] ?? 0.95);

        return match ($fn) {
            'mean' => $mean,
            'median' => Fit::quantile($sorted, 0.5),
            'min' => $sorted[0],
            'max' => $sorted[$n - 1],
            'sum' => array_sum($values),
            'sd' => $sd,
            'length', 'n' => $n,
            'mean_se' => [$mean, $mean - ($args['mult'] ?? 1) * $sd / sqrt($n), $mean + ($args['mult'] ?? 1) * $sd / sqrt($n)],
            'mean_cl_normal' => $n < 2 ? [$mean, NAN, NAN] : (function () use ($mean, $sd, $n, $level) {
                $half = Fit::tQuantile((1 + $level) / 2, $n - 1) * $sd / sqrt($n);

                return [$mean, $mean - $half, $mean + $half];
            })(),
            'mean_sdl' => [$mean, $mean - ($args['mult'] ?? 2) * $sd, $mean + ($args['mult'] ?? 2) * $sd],
            'median_hilow' => [Fit::quantile($sorted, 0.5), Fit::quantile($sorted, (1 - $level) / 2), Fit::quantile($sorted, (1 + $level) / 2)],
            default => throw new InvalidArgumentException("Unknown summary function '{$fn}'; use mean_se, mean_cl_normal, mean_sdl, median_hilow, mean, median, min, max, sum, sd, n, or a callable."),
        };
    }

    /** A box from ymin to ymax (or xmin to xmax) with a line across it at y, as ggplot2's geom_crossbar. */
    private static function crossbar(Context $c): array
    {
        $map = self::rangeMap($c);
        $horizontal = isset($map['xmin']);
        $main = $horizontal ? 'x' : 'y';
        $c->require($main);
        $band = ['band' => $c->param('width', 0.9)];
        $size = [$horizontal ? 'height' : 'width' => $band];
        $box = self::ranged($c, $map + ['fill' => 'fill', 'color' => 'stroke', 'group' => 'detail'] + self::COMMON);
        $boxMark = $c->mark('bar', 'filled', $size + ['stroke' => '#000000', 'strokeWidth' => 1] + ($c->has('fill') ? [] : ['fill' => 'transparent']));
        $line = $c->encode([$horizontal ? 'y' : 'x' => $horizontal ? 'y' : 'x', 'color' => 'stroke', 'group' => 'detail'] + self::COMMON);
        $line[$main] = $c->def($main, $main);
        $line[$main.'2'] = ['field' => $c->ref($main)];
        $lineMark = ['type' => 'bar'] + $size + ['fill' => 'transparent', 'stroke' => $boxMark['stroke'], 'strokeWidth' => 2.5 * ($boxMark['strokeWidth'] ?? 1)];
        $box = self::dodge($c, $box);
        $line = self::dodge($c, $line);

        return [['mark' => $boxMark, 'encoding' => $box], ['mark' => $lineMark, 'encoding' => $line]];
    }

    /**
     * Confidence ellipses per group, as ggplot2's stat_ellipse: type t (MASS::cov.trob), norm, or euclid, at level,
     * drawn as closed paths (or filled, with geom: 'polygon').
     */
    private static function ellipse(Context $c): array
    {
        $c->require('x', 'y');
        if (! $c->data->isInline()) {
            throw new InvalidArgumentException('ellipse computes in PHP and needs inline data.');
        }
        [$x, $y] = [(string) $c->name('x'), (string) $c->name('y')];
        $groups = array_values(array_diff($c->groupFields(), ['__facet']));
        $sets = [];
        foreach ($c->data->rows ?? [] as $row) {
            [$xi, $yi] = [$row[$x] ?? null, $row[$y] ?? null];
            if (! is_numeric($xi) || ! is_numeric($yi) || ! is_finite((float) $xi) || ! is_finite((float) $yi)) {
                continue;
            }
            $key = [];
            foreach ($groups as $group) {
                $key[$group] = $row[$group] ?? null;
            }
            $sets[json_encode($key)] ??= ['key' => $key, 'x' => [], 'y' => []];
            $sets[json_encode($key)]['x'][] = (float) $xi;
            $sets[json_encode($key)]['y'][] = (float) $yi;
        }
        $rows = [];
        $order = 0;
        $id = 0;
        foreach ($sets as $set) {
            $id++;
            foreach (Contour::ellipse($set['x'], $set['y'], (string) $c->param('type', 't'), (float) $c->param('level', 0.95), (int) $c->param('segments', 51)) as [$px, $py]) {
                $rows[] = $set['key'] + [$x => $px, $y => $py, '__ellipse' => $id, '__order' => $order++];
            }
        }
        $c->computed = $rows;
        $c->transforms = [];
        $filled = $c->param('geom', 'path') === 'polygon';
        $encoding = $c->encode(['x' => 'x', 'y' => 'y'] + ($filled ? ['fill' => 'fill', 'color' => 'stroke'] : ['color' => 'color', 'linetype' => 'strokeDash']) + self::COMMON);
        $encoding['x']['type'] = $encoding['y']['type'] = 'quantitative';
        $encoding['order'] = ['field' => '__order', 'type' => 'quantitative'];
        $encoding['detail'] = ['field' => '__ellipse', 'type' => 'nominal'];
        if ($filled) {
            return [['mark' => $c->mark('line', 'filled', ['strokeWidth' => 0, 'opacity' => 0.3] + ($c->has('fill') ? [] : ['fill' => '#333333'])), 'encoding' => $encoding]];
        }

        return [['mark' => $c->mark('line', 'line'), 'encoding' => $encoding]];
    }

    /** Points sized by the number of rows at each x and y, as ggplot2's geom_count. */
    private static function count(Context $c): array
    {
        $c->require('x', 'y');
        $encoding = $c->encode(['x' => 'x', 'y' => 'y', 'color' => 'color', 'fill' => 'fill', 'shape' => 'shape', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);
        $encoding['size'] = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'n'];
        $c->channels['size'] = 'size';

        return [['mark' => $c->mark('point', 'point', ['filled' => true]), 'encoding' => $encoding]];
    }

    /** Smallest gap between distinct values, as ggplot2's resolution(). */
    private static function resolution(array $values): float
    {
        $values = array_values(array_unique(array_filter($values, is_numeric(...))));
        sort($values);
        $gap = INF;
        for ($i = 1; $i < count($values); $i++) {
            $gap = min($gap, $values[$i] - $values[$i - 1]);
        }

        return is_finite($gap) && $gap > 0 ? (float) $gap : 1.0;
    }

    public static function number(int|float $x): string
    {
        if (is_int($x) || floor($x) === $x && abs($x) < 1e15) {
            return (string) (int) $x;
        }

        return sprintf('%.15g', $x);
    }
}

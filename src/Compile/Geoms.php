<?php

declare(strict_types=1);

namespace Phiz\Compile;

use InvalidArgumentException;
use Phiz\Expr;
use Phiz\Field;
use Phiz\Stats\Fit;
use Phiz\Style;

/**
 * Compiles each geom to Vega-Lite layers (mark, encoding, transform; data is attached by the Compiler).
 *
 * @internal
 */
final class Geoms
{
    private const SMOOTH_COLOR = '#3366FF';

    private const COMMON = ['opacity' => 'opacity', 'tooltip' => 'tooltip', 'href' => 'href', 'key' => 'key', 'description' => 'description'];

    /** @return list<array<string, mixed>> */
    public static function compile(Context $c): array
    {
        $geom = $c->layer->geom;

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
            'smooth' => self::smooth($c),
            'curve' => self::curve($c),
            'rug' => self::rug($c),
            'ecdf' => self::ecdf($c),
            'qq' => self::qq($c),
            'qq_line' => self::qqLine($c),
            'arc' => self::arc($c),
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
            // Offsets span the band, so a domain of ±0.25/w moves points up to w of a band from its centre.
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
            $bandwidth = ($bandwidth ?? Fit::bandwidth(array_values(array_filter($c->data->column($x), is_numeric(...))))) * $c->param('adjust');
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
            // A boxplot's colour is its rule, box outline, and median.
            $mark['rule'] = ['stroke' => $mark['stroke']];
            $mark['median'] = ['stroke' => $mark['stroke']];
            $mark['box'] = ['stroke' => $mark['stroke']];
            unset($mark['stroke']);
        }

        return [['mark' => $mark, 'encoding' => $encoding]];
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
        $mark = ['type' => 'errorbar', 'ticks' => true];
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

        return ['mark' => $c->mark('rule', 'line'), 'encoding' => self::dodge($c, $encoding)];
    }

    private static function pointrange(Context $c): array
    {
        $map = self::rangeMap($c);
        $horizontal = isset($map['xmin']);
        $c->require($horizontal ? 'x' : 'y');
        $range = self::ranged($c, $map + ['color' => 'color', 'group' => 'detail', 'detail' => 'detail'] + self::COMMON);
        $style = array_filter(Style::mark($c->layer->style, 'point'), fn ($v) => $v !== null);
        $style['linewidth'] = $c->layer->style['linewidth'] ?? null;
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

            return [['mark' => $mark, 'encoding' => [$axis => $c->def($aesthetic, $axis)] + $extra]];
        }
        $c->constant = true;
        $c->channels[$axis] = $axis;
        $layers = [];
        foreach (is_array($values) ? $values : [$values] as $value) {
            $layers[] = ['data' => ['values' => [new \stdClass]], 'mark' => $mark, 'encoding' => [$axis => ['datum' => $value] + ($value instanceof Expr ? ['type' => 'quantitative'] : [])] + $extra];
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
     * Fitted curves. With se (the default for lm and poly on inline data) the fit and its confidence band are computed
     * in PHP; otherwise Vega-Lite's regression or loess transform fits the curve in the browser.
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
        $se = $c->param('se') ?? ($degree !== null && $c->data->isInline());
        $colorMapped = $c->has('color');
        $lineMark = $c->mark('line', 'line', ['strokeWidth' => 2] + ($colorMapped ? [] : ['color' => self::SMOOTH_COLOR]));
        $lineEncoding = $c->encode(['x' => 'x', 'y' => 'y', 'color' => 'color', 'linetype' => 'strokeDash', 'group' => 'detail']);
        $lineEncoding['x']['type'] = 'quantitative';
        $lineEncoding['y']['type'] = 'quantitative';

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

        if ($degree === null) {
            throw new InvalidArgumentException("se bands are computed for method lm, quad, and poly; use se: false with method {$method}.");
        }
        if (! $c->data->isInline()) {
            throw new InvalidArgumentException('se bands need inline data; use se: false with URL data.');
        }
        $c->computed = Fit::smooth($c->data->rows ?? [], (string) $c->name('x'), (string) $c->name('y'), $c->groupFields(), $degree, (float) $c->param('level', 0.95), (int) $c->param('n', 80));
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
        $layers = [];
        if (str_contains($sides, 'b')) {
            $c->require('x');
            $layers[] = ['mark' => $mark, 'encoding' => ['x' => $c->def('x', 'x'), 'y' => ['value' => new Expr('height')], 'y2' => ['value' => new Expr('height - '.$length)]] + $extra];
        }
        if (str_contains($sides, 'l')) {
            $c->require('y');
            $layers[] = ['mark' => $mark, 'encoding' => ['y' => $c->def('y', 'y'), 'x' => ['value' => 0], 'x2' => ['value' => $length]] + $extra];
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

    /** Sample quantiles against standard normal quantiles at (i - 0.5) / n. */
    private static function qqTransforms(Context $c): array
    {
        $c->require('sample');
        $sample = (string) $c->name('sample');
        $n = count(array_filter($c->data->column($sample), is_numeric(...)));
        $quantile = ['quantile' => $c->ref('sample'), 'as' => ['__prob', $sample]];
        if ($n > 0) {
            $quantile['step'] = 1 / $n;
        }
        if ($c->groupby() !== []) {
            $quantile['groupby'] = $c->groupby();
        }

        return [$quantile, ['calculate' => 'quantileNormal(datum.__prob)', 'as' => 'theoretical']];
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

    private static function arc(Context $c): array
    {
        $encoding = $c->encode(['theta' => 'theta', 'radius' => 'radius', 'fill' => 'color', 'color' => 'stroke', 'order' => 'order'] + self::COMMON);
        if (! isset($encoding['theta'])) {
            $encoding['theta'] = ['aggregate' => 'count', 'type' => 'quantitative', 'title' => 'count'];
            $c->channels['theta'] = 'theta';
        }
        if (isset($encoding['radius'])) {
            $encoding['radius']['scale'] ??= ['type' => 'sqrt', 'zero' => true];
        }
        $defaults = $c->param('innerRadius') !== null ? ['innerRadius' => $c->param('innerRadius')] : [];

        return [['mark' => $c->mark('arc', 'filled', $defaults), 'encoding' => $encoding]];
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

        return rtrim(rtrim(sprintf('%.15g', $x), '0'), '.') ?: '0';
    }
}

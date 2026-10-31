<?php

declare(strict_types=1);

namespace Phiz\Compile;

use InvalidArgumentException;
use Phiz\Aes;
use Phiz\Assets;
use Phiz\Config;
use Phiz\Data;
use Phiz\Field;
use Phiz\Palette;
use Phiz\Plot;
use Phiz\Scale;
use Phiz\Theme;

/**
 * Compiles a Plot to a Vega-Lite spec.
 *
 * @internal
 */
final class Compiler
{
    private const AXIS_CHANNELS = ['x', 'y'];

    /** Geoms whose lengths run from zero, so their value scale must include it. */
    private const ZERO_GEOMS = ['bar', 'col', 'histogram', 'area', 'freqpoly', 'density', 'raw'];

    private const SWAP = ['x' => 'y', 'y' => 'x', 'x2' => 'y2', 'y2' => 'x2', 'xOffset' => 'yOffset', 'yOffset' => 'xOffset', 'xError' => 'yError', 'yError' => 'xError', 'xError2' => 'yError2', 'yError2' => 'xError2'];

    /** @return array<string, mixed> */
    public static function compile(Plot $plot, bool $topLevel = true): array
    {
        if ($plot->layers === []) {
            throw new InvalidArgumentException('The plot has no layers; add a geom such as ->point().');
        }
        $data = $plot->data ?? Data::from(null);
        $facetFields = self::facetFields($plot);

        $units = []; // Each: ['specs' => list<array>, 'channels' => array, 'context' => Context]
        foreach ($plot->layers as $layer) {
            $aes = $layer->inheritAes ? $plot->aes->merge($layer->aes) : $layer->aes;
            $polar = ($plot->coord['type'] ?? null) === 'polar' ? $plot->coord : null;
            $context = new Context($layer, $aes, $layer->data ?? $data, $facetFields, $plot->flip, $plot->width, $plot->height, $plot->transforms !== [], $polar);
            $specs = Geoms::compile($context);
            if ($layer->param('annotation')) {
                $context->constant = true; // Annotations draw in every facet panel.
                foreach ($specs as $i => $spec) {
                    // Their end positions are private fields, which must not join the axis titles.
                    foreach (['x2' => 'x', 'y2' => 'y'] as $end => $start) {
                        if (isset($spec['encoding'][$end]['field'], $spec['encoding'][$start]['field'])) {
                            $specs[$i]['encoding'][$end]['title'] = $spec['encoding'][$start]['title'] ?? stripslashes((string) $spec['encoding'][$start]['field']);
                        }
                    }
                }
            }
            foreach ($specs as $i => $spec) {
                $transforms = [...$context->transforms, ...($spec['transform'] ?? [])];
                if ($layer->filter !== null) {
                    array_unshift($transforms, ['filter' => $layer->filter]);
                }
                unset($spec['transform']);
                $spec = ($transforms === [] ? [] : ['transform' => $transforms]) + $spec;
                $specs[$i] = $layer->raw === [] ? $spec : Theme::merge($spec, $layer->raw);
            }
            $units[] = ['specs' => $specs, 'channels' => $context->channels, 'context' => $context];
        }

        $zeroFree = array_intersect(array_map(fn ($l) => $l->geom, $plot->layers), self::ZERO_GEOMS) === [];
        foreach ($units as &$unit) {
            foreach ($unit['specs'] as &$spec) {
                if (! isset($spec['encoding'])) {
                    continue;
                }
                $spec['encoding'] = self::label($spec['encoding'], $unit['channels'], $plot->labels);
                $spec['encoding'] = self::scale($spec['encoding'], $unit['channels'], $plot->scales, $unit['context']->computed === null ? $unit['context']->data : Data::from($unit['context']->computed));
                if ($zeroFree) {
                    $spec['encoding'] = self::unzero($spec['encoding']);
                }
                if ($plot->limits !== []) {
                    $spec = self::limit($spec, $plot->limits);
                }
                if ($plot->tooltip !== null) {
                    $spec = self::tooltip($spec, $plot->tooltip, $unit['context']->data);
                }
                if ($plot->flip) {
                    $spec['encoding'] = self::flip($spec['encoding']);
                    if (is_array($spec['mark'] ?? null) && (isset($spec['mark']['width']) || isset($spec['mark']['height']))) {
                        [$width, $height] = [$spec['mark']['height'] ?? null, $spec['mark']['width'] ?? null];
                        unset($spec['mark']['width'], $spec['mark']['height']);
                        $spec['mark'] += array_filter(['width' => $width, 'height' => $height], fn ($v) => $v !== null);
                    }
                }
            }
            unset($spec);
        }
        unset($unit);

        [$layers, $topData] = self::data($units, $data, $facetFields !== []);
        $layers = self::interact($layers, $units, $plot);
        $layers = self::capAxes($layers, $plot->config, $plot->facet !== null);
        $size = array_filter(['width' => $plot->width ?? self::discreteSize($units, 'x'), 'height' => $plot->height ?? self::discreteSize($units, 'y')], fn ($v) => $v !== null);
        if (($plot->coord['type'] ?? null) === 'fixed') {
            [$layers, $size] = self::fixed($layers, $units, $size, (float) $plot->coord['ratio'], $plot->flip);
        }
        $unit = count($layers) === 1 ? $layers[0] : ['layer' => $layers];
        $spec = $topLevel ? ['$schema' => Assets::SCHEMA] : [];
        if ($plot->description !== null) {
            $spec['description'] = $plot->description;
        }
        if (($title = self::title($plot->labels)) !== null) {
            $spec['title'] = $title;
        }
        if ($topData !== null) {
            $spec['data'] = $topData;
        }
        $topTransforms = [...$plot->transforms, ...self::facetTransforms($plot)];
        if ($topTransforms !== []) {
            $spec['transform'] = $topTransforms;
        }
        $params = array_values(array_filter($plot->params, fn ($p) => ! isset($p['select'])));
        if ($params !== []) {
            $spec['params'] = $params;
        }

        if ($plot->facet !== null) {
            $spec += self::facet($plot, $data) + ['spec' => $size + $unit];
            $resolve = self::resolve($plot->facet['scales'] ?? 'fixed', $plot->flip);
            if ($resolve !== null) {
                $spec['resolve'] = $resolve;
            }
        } else {
            $spec += $size + $unit;
        }
        $config = $plot->config;
        $captionStyle = $config['caption'] ?? []; // phiz's own key: Vega-Lite has no captions.
        unset($config['caption']);
        if (($config['range']['category'] ?? null) === 'hue') {
            $config['range']['category'] = Palette::hue(self::categoryLevels($units) ?: 8);
        }
        if (self::hasLegend($layers) && ! isset($config['legend']['labelLimit'])) {
            // Vega-Lite truncates legend labels at 160 pixels; long category names are common in data.
            $config['legend']['labelLimit'] = 320;
        }
        if (isset($plot->labels['caption'])) {
            $spec = self::caption($spec, $plot->labels['caption'], $captionStyle, $config);
        }
        if ($config !== []) {
            $spec['config'] = $config;
        }

        $spec = $plot->raw === [] ? $spec : Theme::merge($spec, $plot->raw);
        if ($topLevel) {
            self::warnSize($spec);
        }

        return $spec;
    }

    /**
     * coord_fixed(): sizes the panel so that a unit of y is ratio times as long as a unit of x, over the data ranges
     * of the position fields, and keeps those ranges exact (no nice rounding) so the ratio holds.
     *
     * @return array{0: list<array>, 1: array<string, int>}
     */
    private static function fixed(array $layers, array $units, array $size, float $ratio, bool $flip): array
    {
        $ranges = ['x' => [INF, -INF], 'y' => [INF, -INF]];
        foreach ($units as $unit) {
            $context = $unit['context'];
            $rows = $context->computed ?? ($context->data->isInline() ? ($context->data->rows ?? []) : []);
            foreach ($unit['specs'] as $spec) {
                foreach (['x' => 'x', 'x2' => 'x', 'y' => 'y', 'y2' => 'y'] as $channel => $axis) {
                    $def = $spec['encoding'][$channel] ?? null;
                    if (! is_array($def) || ! isset($def['field']) || isset($def['aggregate']) || isset($def['bin']) || in_array($def['type'] ?? 'quantitative', ['nominal', 'ordinal', 'temporal'], true)) {
                        continue;
                    }
                    $field = stripslashes((string) $def['field']);
                    foreach ($rows as $row) {
                        $v = $row[$field] ?? null;
                        if (is_int($v) || (is_float($v) && is_finite($v))) {
                            $ranges[$axis] = [min($ranges[$axis][0], $v), max($ranges[$axis][1], $v)];
                        }
                    }
                }
            }
        }
        [$dx, $dy] = [$ranges['x'][1] - $ranges['x'][0], $ranges['y'][1] - $ranges['y'][0]];
        if (! ($dx > 0) || ! ($dy > 0)) {
            throw new InvalidArgumentException('coordFixed() needs continuous x and y data with a non-zero range.');
        }
        foreach ($layers as $i => $layer) {
            foreach (['x', 'y'] as $channel) {
                if (isset($layer['encoding'][$channel]['field']) && ! isset($layer['encoding'][$channel]['scale']['domain'])) {
                    $layers[$i]['encoding'][$channel]['scale'] = ($layer['encoding'][$channel]['scale'] ?? []) + ['domain' => $ranges[$channel], 'nice' => false];
                }
            }
        }
        // The ranges are of the screen axes (layers are already flipped); coordFlip() puts the data's y along the width.
        $aspect = $flip ? $dy / ($ratio * $dx) : $ratio * $dy / $dx;
        $width = is_int($size['width'] ?? null) ? $size['width'] : null;
        $height = is_int($size['height'] ?? null) ? $size['height'] : null;
        if ($width === null && $height !== null) {
            $width = (int) round($height / $aspect);
        } else {
            $width ??= 300;
            $height = (int) round($width * $aspect);
        }

        return [$layers, ['width' => $width, 'height' => $height]];
    }

    /** Raises an E_USER_WARNING when the spec embeds more rows than Config::$warnRows. */
    public static function warnSize(array $spec): void
    {
        if (Config::$warnRows === null) {
            return;
        }
        $rows = self::countRows($spec);
        if ($rows > Config::$warnRows) {
            $message = sprintf(
                'phiz: this plot embeds %s data rows in its spec, more than Config::$warnRows (%s). Browsers slow down with large inline data. '
                .'Summarize first (bin2d(), hex(), histogram(compute: \'server\'), line(downsample:), Bin::sample(), or phplyr), '
                .'or load the data from a URL with Data::url(). See https://phiz.phpstats.org/docs/large-data.',
                number_format($rows),
                number_format(Config::$warnRows),
            );
            Config::$warn !== null ? (Config::$warn)($message) : trigger_error($message, E_USER_WARNING);
        }
    }

    private static function countRows(array $node): int
    {
        $count = 0;
        foreach ($node as $key => $value) {
            if ($key === 'values' && is_array($value) && array_is_list($value)) {
                $count += count($value);
            } elseif (is_array($value)) {
                $count += self::countRows($value);
            }
        }

        return $count;
    }

    /** The most levels of any nominal color or fill field, for sizing a computed palette. */
    private static function categoryLevels(array $units): int
    {
        $levels = 0;
        foreach ($units as $unit) {
            $data = $unit['context']->data;
            foreach ($unit['specs'] as $layer) {
                foreach (['color', 'fill', 'stroke'] as $channel) {
                    $def = $layer['encoding'][$channel] ?? null;
                    $def = is_array($def) && isset($def['condition']['field']) ? $def['condition'] : $def;
                    if (is_array($def) && isset($def['field']) && is_string($def['field']) && ($def['type'] ?? null) === 'nominal' && $data->isInline()) {
                        $levels = max($levels, count($data->levels(stripslashes($def['field']))));
                    }
                }
            }
        }

        return $levels;
    }

    private static function hasLegend(array $layers): bool
    {
        foreach ($layers as $layer) {
            foreach (['color', 'fill', 'stroke', 'shape', 'size', 'opacity', 'strokeDash', 'strokeWidth'] as $channel) {
                $def = $layer['encoding'][$channel] ?? null;
                if (is_array($def) && (isset($def['field']) || isset($def['aggregate']) || isset($def['condition']['field'])) && ! array_key_exists('legend', $def)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vega-Lite sizes a discrete axis at 20 pixels per level, which squeezes few levels into a sliver. As ggplot2
     * keeps the panel size, a discrete axis gets the 300 pixels a continuous one has, growing past that by 20 pixels
     * per level when there are many.
     */
    private static function discreteSize(array $units, string $channel): ?int
    {
        $size = null;
        foreach ($units as $unit) {
            $data = $unit['context']->data;
            foreach ($unit['specs'] as $layer) {
                $def = $layer['encoding'][$channel] ?? null;
                if (! is_array($def) || array_is_list($def) || ! in_array($def['type'] ?? null, ['nominal', 'ordinal'], true) || isset($def['bin'])) {
                    continue;
                }
                $field = isset($def['field']) && is_string($def['field']) ? stripslashes($def['field']) : null;
                $levels = $field !== null && $data->isInline() ? count($data->levels($field)) : 0;
                $size = max($size ?? 0, 300, 20 * $levels);
            }
        }

        return $size;
    }

    /**
     * Continuous position scales fit the data, as in ggplot2, rather than Vega-Lite's default of including zero.
     * Plots with bars or areas keep zero, which their lengths are measured from.
     */
    private static function unzero(array $encoding): array
    {
        foreach (['x', 'y'] as $channel) {
            $def = $encoding[$channel] ?? null;
            if (! is_array($def) || array_is_list($def) || ($def['type'] ?? null) !== 'quantitative' || ! isset($def['field'])
                || isset($def['bin']) || isset($def['aggregate']) || array_key_exists('zero', $def['scale'] ?? [])
                || in_array($def['scale']['type'] ?? null, ['log', 'symlog'], true) || isset($def['scale']['domain'])) {
                continue;
            }
            $encoding[$channel]['scale']['zero'] = false;
        }

        return $encoding;
    }

    /**
     * The channel an aesthetic landed on. Position aesthetics own their axis whatever mapped to it (ymin, yend,
     * yintercept, a count), as ggplot2's labs(y:) and scale_y_*() do.
     */
    private static function channel(string $aesthetic, array $channels, array $encoding): ?string
    {
        if (in_array($aesthetic, self::AXIS_CHANNELS, true)) {
            return isset($encoding[$aesthetic]) ? $aesthetic : null;
        }

        return $channels[$aesthetic] ?? null;
    }

    /** Sets labs() titles on the channels the aesthetics were encoded on. */
    private static function label(array $encoding, array $channels, array $labels): array
    {
        foreach ($labels as $aesthetic => $title) {
            $channel = self::channel($aesthetic, $channels, $encoding);
            if ($channel === null || ! isset($encoding[$channel]) || array_is_list($encoding[$channel])) {
                continue;
            }
            if (isset($encoding[$channel]['field']) || isset($encoding[$channel]['aggregate']) || isset($encoding[$channel]['datum'])) {
                $encoding[$channel]['title'] = $title;
                // An end channel with its own title (an annotation's) follows, so the axis has one title.
                if (isset($encoding["{$channel}2"]) && array_key_exists('title', $encoding["{$channel}2"])) {
                    $encoding["{$channel}2"]['title'] = $title;
                }
            }
        }

        return $encoding;
    }

    /** @param array<string, Scale> $scales */
    private static function scale(array $encoding, array $channels, array $scales, Data $data): array
    {
        foreach ($scales as $aesthetic => $scale) {
            $channel = self::channel($aesthetic, $channels, $encoding);
            if ($channel === null && $aesthetic === 'fill') {
                $channel = $channels['color'] ?? null;
            }
            if ($channel === null || ! isset($encoding[$channel]) || array_is_list($encoding[$channel])) {
                continue;
            }
            $def = $encoding[$channel];
            if (! isset($def['field']) && ! isset($def['aggregate']) && ! isset($def['datum'])) {
                continue;
            }
            $guide = in_array($channel, self::AXIS_CHANNELS, true) ? 'axis' : 'legend';
            $properties = $scale->scale;
            if (isset($properties['__identity'])) {
                // The values are used as they are, so there is nothing for a legend to explain.
                $def['scale'] = null;
                $def[$guide] = null;
                $encoding[$channel] = $def;
                continue;
            }
            if (isset($properties['__palette'])) {
                $field = isset($def['field']) && is_string($def['field']) ? stripslashes($def['field']) : null;
                $n = isset($properties['domain']) ? count($properties['domain']) : ($field !== null && $data->isInline() ? count($data->levels($field)) : 0);
                $p = $properties['__palette'];
                $properties['range'] = $p[0] === 'grey'
                    ? Palette::grey(max(1, $n ?: 8), (float) ($p[1] ?? 0.2), (float) ($p[2] ?? 0.8))
                    : Palette::hue(max(1, $n ?: 8), (float) ($p[1] ?? 15), (float) ($p[3] ?? 100), (float) ($p[4] ?? 65), (float) ($p[2] ?? 375), (float) ($p[5] ?? 0), (int) ($p[6] ?? 1));
                unset($properties['__palette']);
            }
            if (isset($properties['domain']) && in_array($def['type'] ?? null, ['nominal', 'ordinal'], true) && isset($def['field']) && is_string($def['field']) && $data->isInline()) {
                // Levels are matched as text, so limits: ['4'] finds a level stored as the number 4.
                $levels = [];
                foreach ($data->levels(stripslashes($def['field'])) as $level) {
                    $levels[(string) $level] = $level;
                }
                $properties['domain'] = array_map(fn ($v) => is_scalar($v) ? ($levels[(string) $v] ?? $v) : $v, $properties['domain']);
            }
            if ($properties !== []) {
                $def['scale'] = array_replace($def['scale'] ?? [], $properties);
            }
            if ($scale->guide === false) {
                $def[$guide] = null;
            } elseif ($scale->guide !== null) {
                $def[$guide] = array_replace($def[$guide] ?? [], $scale->guide);
            }
            if ($scale->title !== null) {
                $def['title'] = $scale->title;
            }
            $encoding[$channel] = $def;
        }

        return $encoding;
    }

    /** coord_cartesian(): zooms by fixing the scale domains and clipping marks. */
    private static function limit(array $spec, array $limits): array
    {
        foreach ($limits as $axis => $domain) {
            if (isset($spec['encoding'][$axis]) && ! array_is_list($spec['encoding'][$axis])) {
                $spec['encoding'][$axis]['scale'] = array_replace($spec['encoding'][$axis]['scale'] ?? [], ['domain' => $domain]);
                $spec['mark'] = (is_array($spec['mark']) ? $spec['mark'] : ['type' => $spec['mark']]) + ['clip' => true];
            }
        }

        return $spec;
    }

    private static function tooltip(array $spec, bool|array $tooltip, Data $data): array
    {
        if (isset($spec['encoding']['tooltip']) || (is_array($spec['mark']) && isset($spec['mark']['tooltip']))) {
            return $spec;
        }
        if ($tooltip === true) {
            $spec['mark'] = (is_array($spec['mark']) ? $spec['mark'] : ['type' => $spec['mark']]) + ['tooltip' => true];
        } elseif ($tooltip !== []) {
            $spec['encoding']['tooltip'] = array_map(function ($field) use ($data) {
                if (is_array($field)) {
                    return $field;
                }
                $field = Field::of($field);

                return $field->toEncoding($data->type((string) $field->name) ?? 'nominal');
            }, $tooltip);
        }

        return $spec;
    }

    private static function flip(array $encoding): array
    {
        $out = [];
        foreach ($encoding as $channel => $def) {
            $out[self::SWAP[$channel] ?? $channel] = $def;
        }

        return $out;
    }

    /**
     * Attaches data. Layers that bring their own data (or computed rows) carry it; when faceting, computed rows are
     * unioned into the top-level data with a layer tag so that facets split them too.
     *
     * @return array{0: list<array>, 1: array|null}
     */
    private static function data(array $units, Data $data, bool $faceted): array
    {
        $tagged = [];
        foreach ($units as $u => $unit) {
            $context = $unit['context'];
            if ($faceted && ! $context->constant && $context->computed === null && $context->layer->data !== null && ! $context->layer->data->isInline()) {
                throw new InvalidArgumentException('A faceted plot cannot split a layer with its own URL or named data; give the layer inline rows or use the plot\'s data.');
            }
            if ($faceted && ! $context->constant && ($context->computed !== null || ($context->layer->data?->isInline() ?? false))) {
                $tagged[$u] = $context->computed ?? $context->layer->data->rows;
            }
        }

        $layers = [];
        foreach ($units as $u => $unit) {
            $context = $unit['context'];
            foreach ($unit['specs'] as $spec) {
                if (isset($spec['data'])) {
                    $layers[] = $spec;
                    continue;
                }
                if ($tagged !== []) {
                    $filter = isset($tagged[$u]) ? 'datum.__layer === '.$u : '!isValid(datum.__layer)';
                    $spec = ['transform' => [['filter' => $filter], ...($spec['transform'] ?? [])]] + $spec;
                } elseif ($context->computed !== null) {
                    $spec = ['data' => ['values' => $context->computed]] + $spec;
                } elseif ($context->layer->data !== null) {
                    $spec = ['data' => $context->layer->data->toSpec()] + $spec;
                }
                $layers[] = $spec;
            }
        }

        if ($tagged !== []) {
            if (! $data->isInline()) {
                throw new InvalidArgumentException('Faceting a layer with its own data needs inline plot data.');
            }
            // The raw rows go in only when some layer draws them: binned layers send just their bins.
            $drawsRaw = false;
            foreach ($units as $u => $unit) {
                $drawsRaw = $drawsRaw || (! isset($tagged[$u]) && ! $unit['context']->constant && $unit['context']->layer->data === null);
            }
            $rows = $drawsRaw ? ($data->rows ?? []) : [];
            foreach ($tagged as $u => $extra) {
                foreach ($extra as $row) {
                    $rows[] = $row + ['__layer' => $u];
                }
            }

            return [$layers, ['values' => $rows]];
        }

        $usesTop = false;
        foreach ($units as $unit) {
            $context = $unit['context'];
            if ($context->computed === null && $context->layer->data === null && ! $context->constant) {
                $usesTop = true;
            }
        }

        return [$layers, $usesTop || $faceted ? $data->toSpec() : null];
    }

    /** Selection parameters (zoom, brush, legend highlight) go on the first layer, with their conditions. */
    private static function interact(array $layers, array $units, Plot $plot): array
    {
        $selections = array_values(array_filter($plot->params, fn ($p) => isset($p['select'])));
        if ($selections === []) {
            return $layers;
        }
        $target = 0;
        foreach ($layers as $i => $layer) {
            if (! isset($layer['data']['values'][0]) || ! $layer['data']['values'][0] instanceof \stdClass) {
                $target = $i;
                break;
            }
        }
        $layers[$target]['params'] = $selections;

        foreach ($plot->conditions as $condition) {
            foreach ($layers as $i => $layer) {
                if ($condition['layer'] === 'first' && $i !== $target) {
                    continue;
                }
                if (isset($layer['data']['values'][0]) && $layer['data']['values'][0] instanceof \stdClass) {
                    continue;
                }
                $channel = $condition['channel'];
                $current = $layer['encoding'][$channel] ?? null;
                if (is_array($current) && isset($current['condition'])) {
                    continue;
                }
                if ($current !== null && isset($current['field'])) {
                    // Keep the field definition (and its legend); unselected marks take the fixed value.
                    $layers[$i]['encoding'][$channel] = $current + ['condition' => ['test' => ['not' => ['param' => $condition['param']]], 'value' => $condition['unselected']]];
                    continue;
                }
                $selected = ['param' => $condition['param'], 'value' => $condition['selected'] ?? ($current['value'] ?? self::markValue($layer, $channel))];
                $layers[$i]['encoding'][$channel] = ['condition' => $selected, 'value' => $condition['unselected']];
            }
        }

        return $layers;
    }

    private static function markValue(array $layer, string $channel): mixed
    {
        $mark = is_array($layer['mark']) ? $layer['mark'] : [];

        return $mark[$channel] ?? ($channel === 'opacity' ? 1 : '#4c78a8');
    }

    private static function title(array $labels): string|array|null
    {
        $title = $labels['title'] ?? null;
        $subtitle = $labels['subtitle'] ?? null;
        if ($title === null && $subtitle === null) {
            return null;
        }

        return $subtitle === null ? $title : ['text' => $title ?? '', 'subtitle' => $subtitle];
    }

    /**
     * guide_axis(cap: true) draws the axis line only from the first break to the last, as ggplot2's cap = "both":
     * the axis's own domain line is hidden and a rule along the panel edge takes its place. Facet panels share axes,
     * and the rule would be drawn in every panel, so faceted plots are refused.
     */
    private static function capAxes(array $layers, array $config, bool $faceted): array
    {
        foreach (self::AXIS_CHANNELS as $channel) {
            $capped = null;
            foreach ($layers as $i => $layer) {
                if (! empty($layer['encoding'][$channel]['axis']['cap'])) {
                    $capped ??= $layer['encoding'][$channel];
                }
                if (is_array($layer['encoding'][$channel]['axis'] ?? null) && array_key_exists('cap', $layer['encoding'][$channel]['axis'])) {
                    unset($layers[$i]['encoding'][$channel]['axis']['cap']);
                    $layers[$i]['encoding'][$channel]['axis']['domain'] = false;
                }
            }
            if ($capped === null) {
                continue;
            }
            if ($faceted) {
                throw new InvalidArgumentException('guide_axis(cap: true) does not support faceted plots.');
            }
            $ends = $capped['axis']['values'] ?? $capped['scale']['domain'] ?? throw new InvalidArgumentException("guide_axis(cap: true) on {$channel} needs breaks or a domain.");
            $axis = ($config[$channel === 'x' ? 'axisX' : 'axisY'] ?? []) + ($config['axis'] ?? []);
            unset($capped['field'], $capped['axis']['cap'], $capped['aggregate'], $capped['bin'], $capped['timeUnit']);
            $capped['axis']['domain'] = false;
            $layers[] = [
                'data' => ['values' => [new \stdClass()]],
                'mark' => ['type' => 'rule', 'color' => $axis['domainColor'] ?? '#888888', 'strokeWidth' => $axis['domainWidth'] ?? 1, 'clip' => false],
                'encoding' => [
                    $channel => ['datum' => min($ends)] + $capped,
                    "{$channel}2" => ['datum' => max($ends)],
                    $channel === 'x' ? 'y' : 'x' => ['value' => $channel === 'x' ? 'height' : 0],
                ],
            ];
        }

        return $layers;
    }

    /**
     * A caption is a title below the view, which Vega-Lite lays out under the axes. A view has one title, so a plot
     * with both moves its contents into a single-view vconcat that carries the caption, under the outer title.
     */
    private static function caption(array $spec, string|array $caption, array $style, array $config): array
    {
        $title = ['text' => $caption, 'orient' => 'bottom', 'anchor' => 'end', 'fontWeight' => 'normal', 'fontSize' => 10]
            + array_filter(['color' => $config['title']['subtitleColor'] ?? $config['title']['color'] ?? null, 'font' => $config['title']['font'] ?? null], fn ($v) => $v !== null);
        $title = array_merge($title, $style);
        if (! isset($spec['title'])) {
            return array_intersect_key($spec, array_flip(['$schema', 'description'])) + ['title' => $title] + $spec;
        }
        $outer = array_intersect_key($spec, array_flip(['$schema', 'description', 'title']));
        $inner = array_diff_key($spec, $outer);

        return $outer + ['vconcat' => [['title' => $title] + $inner]];
    }

    /** @return list<string> */
    private static function facetFields(Plot $plot): array
    {
        $facet = $plot->facet;
        if ($facet === null) {
            return [];
        }
        if ($facet['type'] === 'wrap') {
            // With several fields, groups keep the raw fields (for statistics computed in PHP) and the combined
            // __facet field the facet reads (for Vega-Lite transforms, whose output keeps only grouped fields).
            return count($facet['facets']) === 1 ? $facet['facets'] : [...$facet['facets'], '__facet'];
        }

        return array_values(array_filter([$facet['rows'], $facet['cols']]));
    }

    /** The number of distinct combinations of the fields' values. */
    private static function combinations(Data $data, array $fields): int
    {
        $seen = [];
        foreach ($data->rows ?? [] as $row) {
            $seen[json_encode(array_map(fn ($f) => $row[$f] ?? null, $fields))] = true;
        }

        return count($seen);
    }

    private static function facetTransforms(Plot $plot): array
    {
        $facet = $plot->facet;
        if ($facet === null || $facet['type'] !== 'wrap' || count($facet['facets']) === 1) {
            return [];
        }
        $parts = array_map(fn ($f) => 'datum['.json_encode($f).']', $facet['facets']);

        return [['calculate' => implode(" + ', ' + ", $parts), 'as' => '__facet']];
    }

    private static function facet(Plot $plot, Data $data): array
    {
        $facet = $plot->facet;
        $header = $facet['header'] ?? [];
        $def = function (string $field) use ($data, $header, $plot) {
            $type = $data->type($field);
            $out = ['field' => Field::escape($field), 'type' => $type === 'quantitative' || $type === 'temporal' ? 'ordinal' : 'nominal', 'title' => $plot->labels[$field] ?? null];
            $labeller = $plot->facet['labeller'] ?? 'value';
            if ($labeller === 'both' && $field !== '__facet') {
                $header += ['labelExpr' => json_encode($field, JSON_UNESCAPED_UNICODE)." + ': ' + datum.value"];
            } elseif (is_array($labeller)) {
                $map = isset($labeller[$field]) && is_array($labeller[$field]) ? $labeller[$field] : (array_filter($labeller, is_array(...)) === [] ? $labeller : []);
                if ($map !== []) {
                    $expr = 'datum.value';
                    foreach (array_reverse($map, true) as $value => $label) {
                        $expr = 'datum.value == '.json_encode((string) $value, JSON_UNESCAPED_UNICODE).' ? '.json_encode((string) $label, JSON_UNESCAPED_UNICODE).' : '.$expr;
                    }
                    $header += ['labelExpr' => $expr];
                }
            }
            if ($header !== []) {
                $out['header'] = $header;
            }

            return $out;
        };
        if ($facet['type'] === 'wrap') {
            $field = count($facet['facets']) === 1 ? $facet['facets'][0] : '__facet';
            $out = ['facet' => $def($field)];
            $columns = $facet['ncol'];
            $panels = $data->isInline() ? self::combinations($data, $facet['facets']) : null;
            if ($columns === null && $facet['nrow'] !== null && $panels !== null) {
                $columns = max(1, (int) ceil($panels / $facet['nrow']));
            }
            if ($columns === null && $panels !== null) {
                $columns = (int) ceil(sqrt(max(1, $panels)));
            }
            if ($columns === null && $field === '__facet') {
                $columns = 3;
            }

            return $out + ($columns === null ? [] : ['columns' => $columns]);
        }
        $out = [];
        if ($facet['rows'] !== null) {
            $out['row'] = $def($facet['rows']);
            $out['row']['header'] = ($out['row']['header'] ?? []) + ['labelAngle' => 0, 'labelAlign' => 'left', 'labelOrient' => 'right'];
        }
        if ($facet['cols'] !== null) {
            $out['column'] = $def($facet['cols']);
        }

        return ['facet' => $out];
    }

    private static function resolve(string $scales, bool $flip): ?array
    {
        $free = match ($scales) {
            'fixed' => [],
            'free' => ['x', 'y'],
            'free_x' => ['x'],
            'free_y' => ['y'],
            default => throw new InvalidArgumentException("facet scales must be 'fixed', 'free', 'free_x', or 'free_y'."),
        };
        if ($flip) {
            $free = array_map(fn ($a) => $a === 'x' ? 'y' : 'x', $free);
        }
        if ($free === []) {
            return null;
        }

        return ['scale' => array_fill_keys($free, 'independent')];
    }
}

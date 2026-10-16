<?php

declare(strict_types=1);

namespace Phiz\Compile;

use InvalidArgumentException;
use Phiz\Aes;
use Phiz\Assets;
use Phiz\Data;
use Phiz\Field;
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
            $context = new Context($layer, $aes, $layer->data ?? $data, $facetFields, $plot->flip);
            $specs = Geoms::compile($context);
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
                $spec['encoding'] = self::scale($spec['encoding'], $unit['channels'], $plot->scales);
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
        $unit = count($layers) === 1 ? $layers[0] : ['layer' => $layers];

        $size = array_filter(['width' => $plot->width ?? self::discreteSize($units, 'x'), 'height' => $plot->height ?? self::discreteSize($units, 'y')], fn ($v) => $v !== null);
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
        if (self::hasLegend($layers) && ! isset($config['legend']['labelLimit'])) {
            // Vega-Lite truncates legend labels at 160 pixels; long category names are common in data.
            $config['legend']['labelLimit'] = 320;
        }
        if ($config !== []) {
            $spec['config'] = $config;
        }

        return $plot->raw === [] ? $spec : Theme::merge($spec, $plot->raw);
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
            }
        }

        return $encoding;
    }

    /** @param array<string, Scale> $scales */
    private static function scale(array $encoding, array $channels, array $scales): array
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
            if ($scale->scale !== []) {
                $def['scale'] = array_replace($def['scale'] ?? [], $scale->scale);
            }
            $guide = in_array($channel, self::AXIS_CHANNELS, true) ? 'axis' : 'legend';
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
            $rows = $data->rows ?? [];
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
            if (($plot->facet['labeller'] ?? 'value') === 'both' && $field !== '__facet') {
                $header += ['labelExpr' => json_encode($field, JSON_UNESCAPED_UNICODE)." + ': ' + datum.value"];
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

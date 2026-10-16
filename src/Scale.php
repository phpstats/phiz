<?php

declare(strict_types=1);

namespace Phiz;

/**
 * A scale and its guide (axis or legend) for one aesthetic, as ggplot2's scale_*() functions.
 *
 * Scale properties (type, domain, range, scheme, zero, nice, reverse, ...) go to the Vega-Lite scale; name, breaks,
 * labels and format go to the axis or legend. A false guide hides it. Further properties pass through to the
 * Vega-Lite scale as given.
 */
final class Scale
{
    private const TYPES = ['linear', 'log', 'pow', 'sqrt', 'symlog', 'time', 'utc', 'ordinal', 'band', 'point', 'quantile', 'quantize', 'threshold', 'bin-ordinal', 'identity'];

    /**
     * @param array<string, mixed> $scale Vega-Lite scale properties.
     * @param array<string, mixed>|false|null $guide Axis or legend properties; false hides the guide.
     */
    public function __construct(
        public readonly array $scale = [],
        public readonly array|false|null $guide = null,
        public readonly ?string $title = null,
    ) {}

    /**
     * @param string|null $type linear, log, sqrt, pow, symlog, time, utc, ordinal, band, point, quantile, quantize, threshold.
     * @param array|null $domain [min, max] for continuous scales, or the levels in order for discrete ones.
     * @param array|null $range Output values: colors, sizes, shapes, or pixel extents.
     * @param list<mixed>|null $breaks Tick or legend values.
     * @param string|null $labels A Vega expression for tick labels, with datum.value and datum.label.
     * @param string|null $format A d3 number or time format for labels, such as '.1%' or '%Y'.
     * @param array<string, string>|list<string>|null $values Manual palette: a level => value map, or values in level order.
     */
    public static function make(
        ?string $type = null,
        ?array $domain = null,
        ?array $range = null,
        ?string $scheme = null,
        ?bool $zero = null,
        bool|int|null $nice = null,
        ?bool $reverse = null,
        ?bool $clamp = null,
        int|float|null $base = null,
        int|float|null $exponent = null,
        int|float|null $domainMid = null,
        int|float|null $padding = null,
        ?string $interpolate = null,
        ?array $values = null,
        ?string $name = null,
        ?array $breaks = null,
        ?string $labels = null,
        ?string $format = null,
        ?string $position = null,
        array|false|null $guide = null,
        mixed ...$extra,
    ): self {
        if ($type === 'log10') {
            $type = 'log';
        }
        if ($type === 'date' || $type === 'datetime') {
            $type = 'time';
        }
        if ($type !== null && ! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Unknown scale type '{$type}'.");
        }
        if ($values !== null) {
            if (array_is_list($values)) {
                $range = $values;
            } else {
                $domain = array_keys($values);
                $range = array_values($values);
            }
        }
        $scale = array_filter(compact('type', 'domain', 'range', 'scheme', 'zero', 'nice', 'reverse', 'clamp', 'base', 'exponent', 'domainMid', 'padding', 'interpolate'), fn ($v) => $v !== null);
        if ($interpolate === null && isset($scale['range']) && count($scale['range']) > 1 && is_string($scale['range'][0]) && str_starts_with($scale['range'][0], '#') && ($type === null || ! in_array($type, ['ordinal', 'band', 'point'], true))) {
            // Gradients between colors read best interpolated in a perceptual space, as ggplot2 does.
            $scale['interpolate'] = 'hcl';
        }
        $scale = array_replace($scale, $extra);

        if ($guide !== false) {
            $parts = array_filter(['values' => $breaks, 'labelExpr' => $labels, 'format' => $format, 'orient' => $position], fn ($v) => $v !== null);
            $guide = $parts === [] && $guide === null ? null : array_replace($parts, $guide ?? []);
        }

        return new self($scale, $guide, $name);
    }

    /** This scale with $other's settings laid over it. */
    public function merge(self $other): self
    {
        $guide = match (true) {
            $other->guide === false => false,
            $other->guide === null => $this->guide,
            default => array_replace(is_array($this->guide) ? $this->guide : [], $other->guide),
        };

        return new self(array_replace($this->scale, $other->scale), $guide, $other->title ?? $this->title);
    }
}

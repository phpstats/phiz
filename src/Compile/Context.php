<?php

declare(strict_types=1);

namespace Phiz\Compile;

use Phiz\Aes;
use Phiz\Data;
use Phiz\Field;
use Phiz\Layer;
use Phiz\Style;

/**
 * The state of one layer while it compiles: its effective mappings and data, the Vega-Lite channel each aesthetic
 * landed on (so labels and scales find it later), and the transforms its mappings need.
 *
 * @internal
 */
final class Context
{
    private const CONTINUOUS = ['x', 'y', 'xmin', 'xmax', 'ymin', 'ymax', 'xend', 'yend', 'size', 'opacity', 'theta', 'radius', 'weight', 'xintercept', 'yintercept', 'sample'];

    /** Channels that share their primary channel's scale and take no type. */
    private const SECONDARY = ['x2', 'y2', 'theta2', 'radius2', 'latitude2', 'longitude2', 'xError', 'yError', 'xError2', 'yError2'];

    private const GROUPING = ['color', 'fill', 'group', 'detail', 'shape', 'linetype'];

    /** @var array<string, string> Aesthetic => the Vega-Lite channel it was encoded on. */
    public array $channels = [];

    /** @var list<array<string, mixed>> Transforms the layer's mappings need, run before the geom's own. */
    public array $transforms = [];

    /** Rows the geom computed in PHP, which replace the layer's data. */
    public ?array $computed = null;

    /** Whether the layer draws the same thing in every facet panel (reference lines, functions). */
    public bool $constant = false;

    /**
     * @param list<string> $facetFields Raw names of the fields the plot facets by.
     */
    public function __construct(
        public readonly Layer $layer,
        public readonly Aes $aes,
        public readonly Data $data,
        public readonly array $facetFields = [],
        public readonly bool $flipped = false,
        public readonly int|string|null $width = null,
        public readonly int|string|null $height = null,
    ) {}

    public function has(string $aesthetic): bool
    {
        return $this->aes->has($aesthetic);
    }

    public function param(string $name, mixed $default = null): mixed
    {
        return $this->layer->params[$name] ?? $default;
    }

    /** The Field mapped to an aesthetic, or null when it is unmapped or a raw definition. */
    public function fieldOf(string $aesthetic): ?Field
    {
        $value = $this->aes->get($aesthetic);

        return is_string($value) || $value instanceof Field ? Field::of($value) : null;
    }

    /** Raw field name of an aesthetic. */
    public function name(string $aesthetic): ?string
    {
        return $this->aes->field($aesthetic);
    }

    /** Field reference for a transform: the name escaped as Vega-Lite reads it. */
    public function ref(string $aesthetic): ?string
    {
        $name = $this->name($aesthetic);

        return $name === null ? null : Field::escape($name);
    }

    public function require(string ...$aesthetics): void
    {
        foreach ($aesthetics as $aesthetic) {
            if (! $this->has($aesthetic)) {
                throw new \InvalidArgumentException("geom {$this->layer->geom} needs the {$aesthetic} aesthetic; map it with aes({$aesthetic}: 'field').");
            }
        }
    }

    /** The measurement type of a mapped aesthetic: explicit, inferred from the data, or the aesthetic's default. */
    public function type(string $aesthetic): ?string
    {
        $value = $this->aes->get($aesthetic);
        if (is_array($value)) {
            return $value['type'] ?? null;
        }
        $field = $this->fieldOf($aesthetic);
        if ($field === null) {
            return null;
        }
        if ($field->type !== null) {
            return $field->type;
        }
        if ($field->calculate !== null) {
            return in_array($aesthetic, self::CONTINUOUS, true) ? 'quantitative' : 'nominal';
        }
        if (in_array($aesthetic, ['group', 'detail'], true)) {
            return 'nominal';
        }

        return $this->data->type((string) $field->name) ?? (in_array($aesthetic, self::CONTINUOUS, true) ? 'quantitative' : 'nominal');
    }

    public function discrete(string $aesthetic): bool
    {
        return in_array($this->type($aesthetic), ['nominal', 'ordinal'], true);
    }

    /**
     * The Vega-Lite channel definition for an aesthetic, recorded as encoded on $channel.
     *
     * @param array<string, mixed> $override Properties laid over the definition.
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    public function def(string $aesthetic, ?string $channel = null, array $override = []): ?array
    {
        $value = $this->aes->get($aesthetic);
        if ($value === null) {
            return null;
        }
        $this->channels[$aesthetic] ??= $channel ?? $aesthetic;

        if ($aesthetic === 'tooltip') {
            return $this->tooltip($value);
        }
        if (is_array($value)) {
            return array_replace($value, $override);
        }

        $field = Field::of($value);
        if ($field->calculate !== null) {
            $this->calculate($field->calculate, (string) $field->name);
        }

        $def = array_replace($field->toEncoding($this->type($aesthetic)), $override);

        return in_array($channel, self::SECONDARY, true) ? array_intersect_key($def, array_flip(['field', 'aggregate', 'bandPosition', 'bin', 'timeUnit', 'title'])) : $def;
    }

    /**
     * Encodes mapped aesthetics on their channels.
     *
     * @param array<string, string> $map Aesthetic => Vega-Lite channel.
     * @return array<string, mixed>
     */
    public function encode(array $map): array
    {
        $encoding = [];
        foreach ($map as $aesthetic => $channel) {
            $def = $this->def($aesthetic, $channel);
            if ($def !== null && ! isset($encoding[$channel])) {
                $encoding[$channel] = $def;
            }
        }

        return $encoding;
    }

    /** Fields that split the layer into groups for a statistical transform: discrete groupings plus facets. */
    public function groupFields(): array
    {
        $fields = [];
        foreach (self::GROUPING as $aesthetic) {
            $name = $this->name($aesthetic);
            if ($name !== null && ($this->discrete($aesthetic) || in_array($aesthetic, ['group', 'detail'], true))) {
                $fields[] = $name;
            }
        }

        return array_values(array_unique([...$fields, ...$this->facetFields]));
    }

    /** groupFields() escaped for transform groupby. */
    public function groupby(): array
    {
        return array_map(Field::escape(...), $this->groupFields());
    }

    /**
     * The mark: its type and defaults, then the layer's fixed style.
     *
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public function mark(string $type, string $kind, array $defaults = []): array
    {
        return ['type' => $type] + array_replace($defaults, Style::mark($this->layer->style, $kind));
    }

    public function calculate(string $expression, string $as): void
    {
        $transform = ['calculate' => $expression, 'as' => $as];
        if (! in_array($transform, $this->transforms, true)) {
            $this->transforms[] = $transform;
        }
    }

    /** @return list<array<string, mixed>>|null */
    private function tooltip(mixed $value): ?array
    {
        if ($value === true || $value === false) {
            return null; // Handled as the mark's tooltip property.
        }
        $fields = is_array($value) && array_is_list($value) ? $value : [$value];
        $defs = [];
        foreach ($fields as $field) {
            if (is_array($field)) {
                $defs[] = $field;
                continue;
            }
            $field = Field::of($field);
            $type = $field->type ?? $this->data->type((string) $field->name) ?? 'nominal';
            $defs[] = $field->toEncoding($type);
        }

        return $defs;
    }
}

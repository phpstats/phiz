<?php

declare(strict_types=1);

namespace Phiz;

use InvalidArgumentException;

/**
 * Aesthetic mappings from data fields to visual channels, as ggplot2's aes().
 *
 * Each mapping is a field shorthand string ('hwy', 'year:O', 'mean(hwy)'), a Field, or a raw Vega-Lite channel
 * definition array, which is used as given. tooltip also takes a list of fields, or true for every field.
 */
final class Aes
{
    public const AESTHETICS = [
        'x', 'y', 'xmin', 'xmax', 'ymin', 'ymax', 'xend', 'yend', 'color', 'fill', 'size', 'shape', 'opacity',
        'linetype', 'group', 'detail', 'tooltip', 'label', 'order', 'href', 'theta', 'radius', 'weight',
        'xintercept', 'yintercept', 'sample', 'key', 'description',
    ];

    private const ALIASES = ['colour' => 'color', 'alpha' => 'opacity', 'text' => 'label', 'stroke_dash' => 'linetype', 'url' => 'href'];

    /** @param array<string, mixed> $mappings Validated mappings; use Aes::of() to build them. */
    public function __construct(private readonly array $mappings = []) {}

    public static function of(mixed ...$mappings): self
    {
        $out = [];
        foreach ($mappings as $name => $value) {
            if (! is_string($name)) {
                throw new InvalidArgumentException('Aesthetics are named: aes(x: "field", y: "field").');
            }
            $name = self::ALIASES[$name] ?? $name;
            if (! in_array($name, self::AESTHETICS, true)) {
                throw new InvalidArgumentException("Unknown aesthetic '{$name}'. Known: ".implode(', ', self::AESTHETICS).'.');
            }
            if ($value !== null) {
                $out[$name] = $value;
            }
        }

        return new self($out);
    }

    public static function from(self|array|null $aes): self
    {
        return match (true) {
            $aes instanceof self => $aes,
            $aes === null => new self([]),
            default => self::of(...$aes),
        };
    }

    /** These mappings with $other's laid over them. */
    public function merge(self $other): self
    {
        return new self(array_replace($this->mappings, $other->mappings));
    }

    public function has(string $aesthetic): bool
    {
        return isset($this->mappings[$aesthetic]);
    }

    public function get(string $aesthetic): mixed
    {
        return $this->mappings[$aesthetic] ?? null;
    }

    /** The mapped field name, when the mapping names one. */
    public function field(string $aesthetic): ?string
    {
        $value = $this->get($aesthetic);

        return match (true) {
            is_string($value) => Field::of($value)->name,
            $value instanceof Field => $value->name,
            is_array($value) && isset($value['field']) && is_string($value['field']) => $value['field'],
            default => null,
        };
    }

    public function with(string $aesthetic, mixed $value): self
    {
        $mappings = $this->mappings;
        $mappings[$aesthetic] = $value;

        return new self($mappings);
    }

    public function without(string ...$aesthetics): self
    {
        return new self(array_diff_key($this->mappings, array_flip($aesthetics)));
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->mappings;
    }
}

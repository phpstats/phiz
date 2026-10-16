<?php

declare(strict_types=1);

namespace Phit;

use InvalidArgumentException;

/**
 * A data field mapped to an aesthetic, with optional Vega-Lite field-definition properties.
 *
 * Strings are parsed as shorthand: 'hwy' (type inferred from the data), 'year:O' (Q quantitative, N nominal,
 * O ordinal, T temporal), 'mean(hwy)' or 'count()' for aggregates, and 'month(date):T' for time units.
 */
final class Field
{
    private const TYPES = ['Q' => 'quantitative', 'N' => 'nominal', 'O' => 'ordinal', 'T' => 'temporal', 'G' => 'geojson'];

    private const AGGREGATES = ['count', 'valid', 'values', 'missing', 'distinct', 'sum', 'product', 'mean', 'average', 'variance', 'variancep', 'stdev', 'stdevp', 'stderr', 'median', 'q1', 'q3', 'ci0', 'ci1', 'min', 'max', 'argmin', 'argmax'];

    private const TIME_UNITS = ['year', 'quarter', 'month', 'week', 'day', 'dayofyear', 'date', 'hours', 'minutes', 'seconds', 'milliseconds', 'yearmonth', 'yearquarter', 'yearmonthdate', 'yearweek', 'monthdate', 'hoursminutes', 'yearmonthdatehours'];

    /**
     * @param array<string, mixed> $extra Further field-definition properties, merged as given.
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $type = null,
        public readonly ?string $title = null,
        public readonly ?string $aggregate = null,
        public readonly ?string $timeUnit = null,
        public readonly mixed $bin = null,
        public readonly mixed $sort = null,
        public readonly ?string $format = null,
        public readonly ?string $calculate = null,
        public readonly array $extra = [],
    ) {
        if ($type !== null && ! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Unknown field type '{$type}'; use quantitative, nominal, ordinal, or temporal.");
        }
    }

    public static function of(string|self $field): self
    {
        if ($field instanceof self) {
            return $field;
        }

        $type = null;
        if (preg_match('/^(.+):([QNOTG])$/', $field, $m)) {
            [$field, $type] = [$m[1], self::TYPES[$m[2]]];
        }
        if (preg_match('/^(\w+)\((.*)\)$/', $field, $m)) {
            $op = strtolower($m[1]);
            $inner = $m[2] === '' ? null : $m[2];
            if (in_array($op, self::AGGREGATES, true)) {
                return new self($inner, $type ?? 'quantitative', aggregate: $op);
            }
            if (in_array($op, self::TIME_UNITS, true) && $inner !== null) {
                return new self($inner, $type ?? 'temporal', timeUnit: $op);
            }
        }

        return new self($field, $type);
    }

    /** A field computed from a Vega expression, such as 'datum.hwy - datum.cty'. */
    public static function calc(string $expression, string $as, ?string $type = null): self
    {
        return new self($as, $type, calculate: $expression);
    }

    public function withType(string $type): self
    {
        return $this->with(type: $type);
    }

    public function with(mixed ...$changes): self
    {
        $args = get_object_vars($this);
        foreach ($changes as $key => $value) {
            if (! array_key_exists($key, $args)) {
                $args['extra'][$key] = $value;
            } else {
                $args[$key] = $value;
            }
        }

        return new self(...$args);
    }

    /** The field name escaped for Vega-Lite, which reads dots and brackets as nested access. */
    public static function escape(string $name): string
    {
        return strtr($name, ['\\' => '\\\\', '.' => '\\.', '[' => '\\[', ']' => '\\]']);
    }

    /** @return array<string, mixed> */
    public function toEncoding(?string $inferredType = null): array
    {
        $def = [];
        if ($this->name !== null) {
            $def['field'] = self::escape($this->name);
            if ($def['field'] !== $this->name && $this->title === null && $this->aggregate === null) {
                $def['title'] = $this->name; // Otherwise the default title shows the escape backslashes.
            }
        }
        $def['type'] = $this->type ?? $inferredType ?? 'nominal';
        foreach (['aggregate', 'timeUnit', 'bin', 'sort', 'title', 'format'] as $key) {
            if ($this->{$key} !== null) {
                $def[$key] = $this->{$key};
            }
        }
        if ($this->sort === false) {
            $def['sort'] = null; // Data order.
        }

        return array_replace($def, $this->extra);
    }
}

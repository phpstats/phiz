<?php

declare(strict_types=1);

namespace Phiz;

use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;
use Traversable;
use UnitEnum;
use BackedEnum;

/**
 * Plot data: inline rows, or a URL Vega-Lite loads itself.
 *
 * Inline data accepts a list of row arrays, a column-oriented array of equal-length lists, a phibble Table (anything
 * with toRows() or toColumns()), a Laravel Collection (toArray()), or any iterable of rows. Rows may be arrays or
 * objects. Values are made JSON-safe: dates become ISO 8601 strings, enums their values, and NAN or INF null.
 */
final class Data
{
    /** @var array<string, string|null> Cached inferred Vega-Lite types by field. */
    private array $types = [];

    /** @var list<array<string, mixed>>|null Rows built on first use from a table or columns. */
    private ?array $built = null;

    /**
     * Inline rows. A phibble table or an array of columns is kept as given and turned into rows only when the spec
     * embeds it, so binning geoms (bin2d, hex, histogram) can read large tables column by column.
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $rows {
        get {
            if ($this->eager !== null || $this->url !== null || $this->name !== null) {
                return $this->eager;
            }

            return $this->built ??= $this->table !== null ? self::rows($this->table) : self::transpose($this->columns ?? []);
        }
    }

    /**
     * @param list<array<string, mixed>>|null $eager
     * @param array<string, list<mixed>>|null $columns
     * @param array<string, mixed>|null $format Vega-Lite data format for URL data.
     */
    private function __construct(
        private readonly ?array $eager,
        public readonly ?string $url = null,
        public readonly ?array $format = null,
        public readonly ?string $name = null,
        private readonly ?object $table = null,
        private readonly ?array $columns = null,
    ) {}

    public static function from(mixed $data): self
    {
        return match (true) {
            $data instanceof self => $data,
            $data === null => new self([]),
            is_object($data) && method_exists($data, 'column') && method_exists($data, 'nrow') => new self(null, table: $data),
            is_array($data) && $data !== [] && ! array_is_list($data) && self::isColumns($data) => new self(null, columns: array_map(array_values(...), $data)),
            default => new self(self::rows($data)),
        };
    }

    /** Data that Vega-Lite fetches from a URL (CSV, TSV, JSON); types can't be inferred, so use 'field:Q' shorthand. */
    public static function url(string $url, ?string $type = null, ?array $parse = null): self
    {
        $format = array_filter(['type' => $type, 'parse' => $parse], fn ($v) => $v !== null);

        return new self(null, $url, $format === [] ? null : $format);
    }

    /** A named data source the page supplies at runtime (vega-embed's view.data(name, rows)). */
    public static function named(string $name): self
    {
        return new self(null, name: $name);
    }

    public function isInline(): bool
    {
        return $this->url === null && $this->name === null;
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** The number of inline rows, without building them. */
    public function count(): int
    {
        return match (true) {
            ! $this->isInline() => 0,
            $this->table !== null => $this->table->nrow(),
            $this->columns !== null => $this->columns === [] ? 0 : count($this->columns[array_key_first($this->columns)]),
            default => count($this->eager ?? []),
        };
    }

    /**
     * What the binning helpers read: the table, the columns, or the rows.
     *
     * @internal
     */
    public function source(): mixed
    {
        return $this->table ?? $this->columns ?? $this->eager;
    }

    /** @return array<string, mixed> The Vega-Lite data definition. */
    public function toSpec(): array
    {
        if ($this->url !== null) {
            return ['url' => $this->url] + ($this->format === null ? [] : ['format' => $this->format]);
        }
        if ($this->name !== null) {
            return ['name' => $this->name];
        }

        return ['values' => $this->rows];
    }

    /** @return list<string> */
    public function fields(): array
    {
        return match (true) {
            $this->table !== null => self::tableFields($this->table),
            $this->columns !== null => array_keys($this->columns),
            default => array_keys($this->eager[0] ?? []),
        };
    }

    public function has(string $field): bool
    {
        if (! $this->isInline() || $this->count() === 0) {
            return true;
        }
        if ($this->table !== null || $this->columns !== null) {
            return in_array($field, $this->fields(), true);
        }
        foreach ($this->eager as $row) {
            if (array_key_exists($field, $row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A field's values, JSON-safe, read from the table or columns without building rows.
     *
     * @return list<mixed>
     */
    public function column(string $field): array
    {
        if ($this->table !== null) {
            try {
                return array_map(self::value(...), array_values($this->table->column($field)));
            } catch (\Throwable) {
                return array_fill(0, $this->count(), null);
            }
        }
        if ($this->columns !== null) {
            return array_map(self::value(...), $this->columns[$field] ?? array_fill(0, $this->count(), null));
        }

        return array_map(fn (array $row) => $row[$field] ?? null, $this->eager ?? []);
    }

    /**
     * The Vega-Lite measurement type of a field: quantitative when every non-null value is a number, temporal when
     * every value is an ISO date string, nominal otherwise. Null for non-inline data or an all-null column.
     */
    public function type(string $field): ?string
    {
        if (! $this->isInline()) {
            return null;
        }
        if (array_key_exists($field, $this->types)) {
            return $this->types[$field];
        }

        $type = null;
        foreach ($this->column($field) as $value) {
            if ($value === null) {
                continue;
            }
            $kind = match (true) {
                is_int($value), is_float($value) => 'quantitative',
                is_string($value) && self::isDate($value) => 'temporal',
                default => 'nominal',
            };
            if ($type === null) {
                $type = $kind;
            } elseif ($type !== $kind) {
                $type = 'nominal';
                break;
            }
        }

        return $this->types[$field] = $type;
    }

    /** Distinct non-null values of a field in first-seen order. */
    public function levels(string $field): array
    {
        $seen = [];
        foreach ($this->column($field) as $value) {
            if ($value !== null && ! isset($seen[is_scalar($value) ? get_debug_type($value).':'.$value : serialize($value)])) {
                $seen[is_scalar($value) ? get_debug_type($value).':'.$value : serialize($value)] = $value;
            }
        }

        return array_values($seen);
    }

    /** @param list<array<string, mixed>> $rows */
    public function withRows(array $rows): self
    {
        return new self($rows);
    }

    /** Column names of a table: names(), or columns() as a list of names (phibble) or a name => values map. */
    private static function tableFields(object $table): array
    {
        $columns = method_exists($table, 'names') ? $table->names() : $table->columns();

        return array_map('strval', array_is_list($columns) ? $columns : array_keys($columns));
    }

    private static function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?)?$/', $value);
    }

    /** @return list<array<string, mixed>> */
    private static function rows(mixed $data): array
    {
        if (is_object($data)) {
            $data = match (true) {
                method_exists($data, 'toRows') => $data->toRows(),
                method_exists($data, 'toColumns') => $data->toColumns(),
                method_exists($data, 'toArray') => $data->toArray(),
                $data instanceof Traversable => iterator_to_array($data, false),
                $data instanceof JsonSerializable => $data->jsonSerialize(),
                default => throw new InvalidArgumentException('Cannot plot an object of class '.$data::class.'; pass rows, columns, or a table.'),
            };
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data, false);
        }
        if (! is_array($data)) {
            throw new InvalidArgumentException('Plot data must be rows, columns, a table, or a collection; got '.get_debug_type($data).'.');
        }
        if ($data === []) {
            return [];
        }
        if (! array_is_list($data) && self::isColumns($data)) {
            return self::transpose($data);
        }
        if (! array_is_list($data)) {
            throw new InvalidArgumentException('Plot data must be a list of rows or an array of equal-length columns.');
        }

        $rows = [];
        foreach ($data as $i => $row) {
            if (is_object($row)) {
                $row = method_exists($row, 'toArray') ? $row->toArray() : ($row instanceof JsonSerializable ? $row->jsonSerialize() : get_object_vars($row));
            }
            if (! is_array($row)) {
                throw new InvalidArgumentException("Row {$i} is not an array or object.");
            }
            $clean = [];
            foreach ($row as $key => $value) {
                $clean[(string) $key] = self::value($value);
            }
            $rows[] = $clean;
        }

        return $rows;
    }

    private static function isColumns(array $data): bool
    {
        $length = null;
        foreach ($data as $column) {
            if (! is_array($column) || ! array_is_list($column)) {
                return false;
            }
            $length ??= count($column);
            if (count($column) !== $length) {
                throw new InvalidArgumentException('Columns must all have the same length.');
            }
        }

        return true;
    }

    private static function transpose(array $columns): array
    {
        $names = array_keys($columns);
        $n = $columns === [] ? 0 : count(reset($columns));
        $columns = array_map(array_values(...), $columns);
        $rows = [];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            foreach ($names as $name) {
                $row[(string) $name] = self::value($columns[$name][$i]);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public static function value(mixed $value): mixed
    {
        return match (true) {
            is_float($value) && ! is_finite($value) => null,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof \Stringable => (string) $value,
            is_array($value) => array_map(self::value(...), $value),
            default => $value,
        };
    }
}

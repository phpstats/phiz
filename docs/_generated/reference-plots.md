**Construction:** [`Plot::of()`](#of)

**Mappings and data:** [`aes()`](#aes) · [`data()`](#data)

**Data:** [`Data::from()`](#data-from) · [`Data::url()`](#data-url) · [`Data::named()`](#data-named) · [`$data->isInline()`](#data-is-inline) · [`$data->isEmpty()`](#data-is-empty) · [`$data->count()`](#data-count) · [`$data->source()`](#data-source) · [`$data->toSpec()`](#data-to-spec) · [`$data->fields()`](#data-fields) · [`$data->has()`](#data-has) · [`$data->column()`](#data-column) · [`$data->type()`](#data-type) · [`$data->isTime()`](#data-is-time) · [`$data->levels()`](#data-levels) · [`$data->withRows()`](#data-with-rows) · [`Data::value()`](#data-value)

**Datasets:** [`Datasets::mpg()`](#datasets-mpg) · [`Datasets::mtcars()`](#datasets-mtcars) · [`Datasets::economics()`](#datasets-economics) · [`Datasets::faithful()`](#datasets-faithful) · [`Datasets::load()`](#datasets-load)

**Bin:** [`Bin::histogram()`](#bin-histogram) · [`Bin::grid()`](#bin-grid) · [`Bin::hex()`](#bin-hex) · [`Bin::lttb()`](#bin-lttb) · [`Bin::sample()`](#bin-sample) · [`Bin::range()`](#bin-range) · [`Bin::values()`](#bin-values)

**Functions:** [`aes()`](#aes-function) · [`ggplot()`](#ggplot-function) · [`field()`](#field-function) · [`factor()`](#factor-function) · [`calc()`](#calc-function) · [`expr()`](#expr-function)

## Construction

### `Plot::of()` {#of}

```php
Plot::of(mixed $data = null, Aes|array|null $aes = null): Plot
```

A plot of rows, columns, a phibble Table, a Laravel Collection, or Data::url(...), with optional mappings.

## Mappings and data

### `aes()` {#aes}

```php
->aes(mixed ...$mappings): Plot
```

Adds plot-wide aesthetic mappings: x, y, color, fill, size, shape, alpha, group, tooltip, label, ...

### `data()` {#data}

```php
->data(mixed $data): Plot
```

## Data {#class-data}

### `Data::from()` {#data-from}

```php
Data::from(mixed $data): Data
```

### `Data::url()` {#data-url}

```php
Data::url(string $url, ?string $type = null, ?array $parse = null): Data
```

Data that Vega-Lite fetches from a URL (CSV, TSV, JSON); types can't be inferred, so use 'field:Q' shorthand.

### `Data::named()` {#data-named}

```php
Data::named(string $name): Data
```

A named data source the page supplies at runtime (vega-embed's view.data(name, rows)).

### `$data->isInline()` {#data-is-inline}

```php
->isInline(): bool
```

### `$data->isEmpty()` {#data-is-empty}

```php
->isEmpty(): bool
```

### `$data->count()` {#data-count}

```php
->count(): int
```

The number of inline rows, without building them.

### `$data->source()` {#data-source}

```php
->source(): mixed
```

What the binning helpers read: the table, the columns, or the rows.

### `$data->toSpec()` {#data-to-spec}

```php
->toSpec(): array
```

### `$data->fields()` {#data-fields}

```php
->fields(): array
```

### `$data->has()` {#data-has}

```php
->has(string $field): bool
```

### `$data->column()` {#data-column}

```php
->column(string $field): array
```

A field's values, JSON-safe, read from the table or columns without building rows.

### `$data->type()` {#data-type}

```php
->type(string $field): ?string
```

The Vega-Lite measurement type of a field: quantitative when every non-null value is a number, temporal when every value is an ISO date string, nominal otherwise. Null for non-inline data or an all-null column.

### `$data->isTime()` {#data-is-time}

```php
->isTime(string $field): bool
```

Whether a field held phibble times, which are plotted as seconds on a continuous scale, as ggplot2 plots hms.

### `$data->levels()` {#data-levels}

```php
->levels(string $field): array
```

Distinct non-null values of a field in first-seen order.

### `$data->withRows()` {#data-with-rows}

```php
->withRows(array $rows): Data
```

### `Data::value()` {#data-value}

```php
Data::value(mixed $value): mixed
```

## Datasets {#class-datasets}

### `Datasets::mpg()` {#datasets-mpg}

```php
Datasets::mpg(): array
```

### `Datasets::mtcars()` {#datasets-mtcars}

```php
Datasets::mtcars(): array
```

### `Datasets::economics()` {#datasets-economics}

```php
Datasets::economics(): array
```

### `Datasets::faithful()` {#datasets-faithful}

```php
Datasets::faithful(): array
```

### `Datasets::load()` {#datasets-load}

```php
Datasets::load(string $name): array
```

## Bin {#class-bin}

### `Bin::histogram()` {#bin-histogram}

```php
Bin::histogram(
    mixed $source,
    string $x,
    int $bins = 30,
    int|float|null $binwidth = null,
    int|float|null $boundary = null,
    ?array $limits = null,
    array $groups = [],
): array
```

Equal-width bins of x: rows with x0, x1, count, and density (count / (n * width)), per group.

### `Bin::grid()` {#bin-grid}

```php
Bin::grid(
    mixed $source,
    string $x,
    string $y,
    array|int $bins = 30,
    ?array $xlim = null,
    ?array $ylim = null,
    array $groups = [],
): array
```

Counts in a grid of x-by-y rectangles: rows with x_start, x_end, y_start, y_end (named after the fields) and count. Empty cells are omitted.

### `Bin::hex()` {#bin-hex}

```php
Bin::hex(
    mixed $source,
    string $x,
    string $y,
    int $bins = 30,
    ?array $xlim = null,
    ?array $ylim = null,
    float $aspect = 1.0,
    array $groups = [],
): array
```

Counts in a hexagonal grid, as ggplot2's geom_hex: rows with the hexagon centers (under the x and y names) and count. bins hexagons span the x range; aspect (height / width of the panel in pixels) keeps them regular on screen.

### `Bin::lttb()` {#bin-lttb}

```php
Bin::lttb(
    array $rows,
    string $x,
    string $y,
    int $threshold = 1000,
    array $groups = [],
): array
```

Downsamples a line to about $threshold points with Largest-Triangle-Three-Buckets, which keeps the visual shape (peaks and troughs). Rows are sorted by x; groups are downsampled separately.

### `Bin::sample()` {#bin-sample}

```php
Bin::sample(mixed $source, int $n, int $seed = 1): array
```

A uniform random sample of n rows (reservoir sampling, one pass), reproducible with a seed.

### `Bin::range()` {#bin-range}

```php
Bin::range(mixed $source, string $field): array
```

The [min, max] of a numeric field, padded when every value is equal.

### `Bin::values()` {#bin-values}

```php
Bin::values(mixed $source, array $fields): Generator
```

The values of the named fields, one list per record.

## Functions

### `aes()` {#aes-function}

```php
aes(mixed ...$mappings): Aes
```

Aesthetic mappings: aes(x: 'displ', y: 'hwy', color: 'class').

### `ggplot()` {#ggplot-function}

```php
ggplot(mixed $data = null, Aes|array|null $mapping = null): Plot
```

A plot of data with optional mappings.

### `field()` {#field-function}

```php
field(string $name, ?string $type = null, mixed ...$properties): Field
```

A field with an explicit type or other Vega-Lite field-definition properties.

### `factor()` {#factor-function}

```php
factor(string $name, ?array $levels = null): Field
```

A field treated as discrete categories, like R's factor(cyl). Levels set the order.

### `calc()` {#calc-function}

```php
calc(string $expression, string $as, ?string $type = null): Field
```

A field computed from a Vega expression: calc('datum.hwy - datum.cty', as: 'gap').

### `expr()` {#expr-function}

```php
expr(string $expression): Expr
```

A Vega expression, such as a slider parameter's name.


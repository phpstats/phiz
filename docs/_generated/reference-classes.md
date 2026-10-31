### Compose

```php
Compose::beside(Chart ...$charts): Compose
```

```php
Compose::stack(Chart ...$charts): Compose
```

```php
Compose::grid(array $charts, int $columns = 2): Compose
```

A grid filled row by row with the given number of columns.

```php
->title(string $title, ?string $subtitle = null): Compose
```

```php
->theme(?string $preset = null, mixed ...$options): Compose
```

A theme preset and options for the whole composition, as Plot::theme().

```php
->resolve(array $resolve): Compose
```

Shares or separates scales, axes, and legends across the plots, e.g. ['scale' => ['color' => 'independent']].

```php
->raw(array $spec): Compose
```

```php
->toArray(): array
```

```php
->toSpec(): array
```

### Data

```php
Data::from(mixed $data): Data
```

```php
Data::url(string $url, ?string $type = null, ?array $parse = null): Data
```

Data that Vega-Lite fetches from a URL (CSV, TSV, JSON); types can't be inferred, so use 'field:Q' shorthand.

```php
Data::named(string $name): Data
```

A named data source the page supplies at runtime (vega-embed's view.data(name, rows)).

```php
->isInline(): bool
```

```php
->isEmpty(): bool
```

```php
->count(): int
```

The number of inline rows, without building them.

```php
->source(): mixed
```

What the binning helpers read: the table, the columns, or the rows.

```php
->toSpec(): array
```

```php
->fields(): array
```

```php
->has(string $field): bool
```

```php
->column(string $field): array
```

A field's values, JSON-safe, read from the table or columns without building rows.

```php
->type(string $field): ?string
```

The Vega-Lite measurement type of a field: quantitative when every non-null value is a number, temporal when every value is an ISO date string, nominal otherwise. Null for non-inline data or an all-null column.

```php
->levels(string $field): array
```

Distinct non-null values of a field in first-seen order.

```php
->withRows(array $rows): Data
```

```php
Data::value(mixed $value): mixed
```

### Export

```php
Export::find(): ?string
```

The vl-convert binary path, or null when none is found.

```php
Export::available(): bool
```

```php
->svg(Chart|array $chart): string
```

```php
->png(Chart|array $chart, float $scale = 2.0, ?int $ppi = null): string
```

PNG bytes; scale multiplies the pixel size (2 for high-DPI screens), ppi sets the embedded resolution.

```php
->jpeg(Chart|array $chart, float $scale = 2.0, ?int $quality = null): string
```

```php
->pdf(Chart|array $chart): string
```

```php
->vega(Chart|array $chart): array
```

The compiled Vega (not Vega-Lite) spec.

```php
->save(Chart|array $chart, string $path, float $scale = 2.0): string
```

Writes the chart to a file whose extension picks the format: svg, png, jpg/jpeg, pdf, html, or json.

### Html

```php
Html::container(Chart|array $chart, array $options = [], array $attributes = []): string
```

The container element for a chart.

```php
Html::deferred(string $url, array $options = [], array $attributes = [], string $text = 'Vega-Lite specification'): string
```

A container that loads its spec from a URL when it scrolls into view, for pages with many charts. The URL is the href of a link inside the container, so the page still links to each spec without JavaScript, and tools that rewrite links move the spec files with the page.

```php
Html::embed(Chart|array $chart, array $options = [], Assets|false|null $assets = null, array $attributes = []): string
```

A self-contained fragment: the container plus script tags (null assets means the pinned CDN copies).

```php
Html::page(Chart|array|null $chart, ?string $title = null, array $options = [], ?Assets $assets = null, string $body = ''): string
```

```php
Html::attributes(array $attributes): string
```

### Bin

```php
Bin::histogram(mixed $source, string $x, int $bins = 30, int|float|null $binwidth = null, int|float|null $boundary = null, ?array $limits = null, array $groups = []): array
```

Equal-width bins of x: rows with x0, x1, count, and density (count / (n * width)), per group.

```php
Bin::grid(mixed $source, string $x, string $y, array|int $bins = 30, ?array $xlim = null, ?array $ylim = null, array $groups = []): array
```

Counts in a grid of x-by-y rectangles: rows with x_start, x_end, y_start, y_end (named after the fields) and count. Empty cells are omitted.

```php
Bin::hex(mixed $source, string $x, string $y, int $bins = 30, ?array $xlim = null, ?array $ylim = null, float $aspect = 1.0, array $groups = []): array
```

Counts in a hexagonal grid, as ggplot2's geom_hex: rows with the hexagon centers (under the x and y names) and count. bins hexagons span the x range; aspect (height / width of the panel in pixels) keeps them regular on screen.

```php
Bin::lttb(array $rows, string $x, string $y, int $threshold = 1000, array $groups = []): array
```

Downsamples a line to about $threshold points with Largest-Triangle-Three-Buckets, which keeps the visual shape (peaks and troughs). Rows are sorted by x; groups are downsampled separately.

```php
Bin::sample(mixed $source, int $n, int $seed = 1): array
```

A uniform random sample of n rows (reservoir sampling, one pass), reproducible with a seed.

```php
Bin::range(mixed $source, string $field): array
```

The [min, max] of a numeric field, padded when every value is equal.

```php
Bin::values(mixed $source, array $fields): Generator
```

The values of the named fields, one list per record.


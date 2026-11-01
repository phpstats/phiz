**Output:** [`toArray()`](#to-array) · [`toSpec()`](#to-spec) · [`toJson()`](#to-json) · [`embed()`](#embed) · [`html()`](#html) · [`svg()`](#svg) · [`png()`](#png) · [`pdf()`](#pdf) · [`save()`](#save)

**Escape hatches:** [`transform()`](#transform) · [`raw()`](#raw) · [`apply()`](#apply)

**Html:** [`Html::container()`](#html-container) · [`Html::deferred()`](#html-deferred) · [`Html::embed()`](#html-embed) · [`Html::page()`](#html-page) · [`Html::attributes()`](#html-attributes)

**Assets:** [`Assets::cdn()`](#assets-cdn) · [`Assets::local()`](#assets-local) · [`Assets::urls()`](#assets-urls) · [`Assets::inline()`](#assets-inline) · [`Assets::none()`](#assets-none) · [`$assets->scripts()`](#assets-scripts) · [`Assets::loader()`](#assets-loader)

**Export:** [`Export::find()`](#export-find) · [`Export::available()`](#export-available) · [`$export->svg()`](#export-svg) · [`$export->png()`](#export-png) · [`$export->jpeg()`](#export-jpeg) · [`$export->pdf()`](#export-pdf) · [`$export->vega()`](#export-vega) · [`$export->save()`](#export-save)

## Output

### `toArray()` {#to-array}

```php
->toArray(): array
```

### `toSpec()` {#to-spec}

```php
->toSpec(): array
```

### `toJson()` {#to-json}

```php
->toJson(bool $pretty = false): string
```

The specification as JSON; pretty adds indentation.

### `embed()` {#embed}

```php
->embed(
    array $options = [],
    Assets|false|null $assets = null,
    array $attributes = [],
): string
```

An HTML fragment that renders the chart: a container div plus, unless $assets is false, the script tags.

### `html()` {#html}

```php
->html(?string $title = null, array $options = [], ?Assets $assets = null): string
```

A standalone HTML page.

### `svg()` {#svg}

```php
->svg(?Export $export = null): string
```

An SVG image rendered by vl-convert.

### `png()` {#png}

```php
->png(float $scale = 2.0, ?Export $export = null): string
```

A PNG image rendered by vl-convert; scale multiplies the pixel dimensions.

### `pdf()` {#pdf}

```php
->pdf(?Export $export = null): string
```

A PDF document rendered by vl-convert.

### `save()` {#save}

```php
->save(string $path, float $scale = 2.0, ?Export $export = null): string
```

Writes to a file whose extension picks the format: svg, png, jpg, pdf, html, or json.

## Escape hatches

### `transform()` {#transform}

```php
->transform(array ...$transforms): Plot
```

Vega-Lite transforms applied to the data before every layer.

### `raw()` {#raw}

```php
->raw(array $spec): Plot
```

Raw Vega-Lite merged into the top-level spec last (nested objects merge; lists and scalars replace).

### `apply()` {#apply}

```php
->apply(callable ...$fns): Plot
```

Applies a function to the plot: pipe-friendly composition of reusable plot fragments.

## Html {#class-html}

### `Html::container()` {#html-container}

```php
Html::container(
    Chart|array $chart,
    array $options = [],
    array $attributes = [],
): string
```

The container element for a chart.

### `Html::deferred()` {#html-deferred}

```php
Html::deferred(
    string $url,
    array $options = [],
    array $attributes = [],
    string $text = 'Vega-Lite specification',
): string
```

A container that loads its spec from a URL when it scrolls into view, for pages with many charts. The URL is the href of a link inside the container, so the page still links to each spec without JavaScript, and tools that rewrite links move the spec files with the page.

### `Html::embed()` {#html-embed}

```php
Html::embed(
    Chart|array $chart,
    array $options = [],
    Assets|false|null $assets = null,
    array $attributes = [],
): string
```

A self-contained fragment: the container plus script tags (null assets means the pinned CDN copies).

### `Html::page()` {#html-page}

```php
Html::page(
    Chart|array|null $chart,
    ?string $title = null,
    array $options = [],
    ?Assets $assets = null,
    string $body = '',
): string
```

A complete HTML page that draws the chart, or holds body when chart is null; assets defaults to the CDN.

### `Html::attributes()` {#html-attributes}

```php
Html::attributes(array $attributes): string
```

## Assets {#class-assets}

### `Assets::cdn()` {#assets-cdn}

```php
Assets::cdn(string $base = 'https://cdn.jsdelivr.net/npm'): Assets
```

The pinned libraries from jsDelivr, with subresource integrity; base replaces the CDN address.

### `Assets::local()` {#assets-local}

```php
Assets::local(string $base): Assets
```

Copies served from your own URL prefix: {base}/vega.min.js, {base}/vega-lite.min.js, {base}/vega-embed.min.js.

### `Assets::urls()` {#assets-urls}

```php
Assets::urls(string $vega, string $vegaLite, string $vegaEmbed): Assets
```

Explicit URLs for each library.

### `Assets::inline()` {#assets-inline}

```php
Assets::inline(string $directory): Assets
```

Embeds the three files from a directory containing vega.min.js, vega-lite.min.js and vega-embed.min.js.

### `Assets::none()` {#assets-none}

```php
Assets::none(): Assets
```

No library tags: for pages that already load vega-embed (bundled with npm, say).

### `$assets->scripts()` {#assets-scripts}

```php
->scripts(bool $loader = true): string
```

Script tags for the libraries, followed by the phiz loader.

### `Assets::loader()` {#assets-loader}

```php
Assets::loader(): string
```

The phiz loader script: renders every [data-phiz] element and re-renders when its spec attribute changes.

## Export {#class-export}

### `Export::find()` {#export-find}

```php
Export::find(): ?string
```

The vl-convert binary path, or null when none is found.

### `Export::available()` {#export-available}

```php
Export::available(): bool
```

Whether the vl-convert binary can be found.

### `$export->svg()` {#export-svg}

```php
->svg(Chart|array $chart): string
```

The chart rendered to SVG.

### `$export->png()` {#export-png}

```php
->png(Chart|array $chart, float $scale = 2.0, ?int $ppi = null): string
```

PNG bytes; scale multiplies the pixel size (2 for high-DPI screens), ppi sets the embedded resolution.

### `$export->jpeg()` {#export-jpeg}

```php
->jpeg(Chart|array $chart, float $scale = 2.0, ?int $quality = null): string
```

The chart rendered to JPEG; scale multiplies the pixel dimensions, and quality runs from 0 to 100.

### `$export->pdf()` {#export-pdf}

```php
->pdf(Chart|array $chart): string
```

The chart rendered to PDF.

### `$export->vega()` {#export-vega}

```php
->vega(Chart|array $chart): array
```

The compiled Vega (not Vega-Lite) spec.

### `$export->save()` {#export-save}

```php
->save(Chart|array $chart, string $path, float $scale = 2.0): string
```

Writes the chart to a file whose extension picks the format: svg, png, jpg/jpeg, pdf, html, or json.


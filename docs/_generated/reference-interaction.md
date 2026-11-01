**Methods:** [`tooltip()`](#tooltip) · [`interactive()`](#interactive) · [`brush()`](#brush) · [`highlight()`](#highlight) · [`slider()`](#slider) · [`dropdown()`](#dropdown) · [`param()`](#param) · [`select()`](#select)

**Functions:** [`tooltip()`](#tooltip-function) · [`interactive()`](#interactive-function) · [`brush()`](#brush-function) · [`highlight()`](#highlight-function)

### `tooltip()` {#tooltip}

```php
->tooltip(array|string|bool $fields = true): Plot
```

Tooltips on every layer: true shows the encoded fields, a list shows those fields.

Function: [`tooltip()`](#tooltip-function).

### `interactive()` {#interactive}

```php
->interactive(array $encodings = [0 => 'x',1 => 'y'], string $name = 'zoom'): Plot
```

Pan and zoom by dragging and scrolling (scales bound to an interval selection).

Function: [`interactive()`]{#interactive-function}.

### `brush()` {#brush}

```php
->brush(
    string $name = 'brush',
    array $encodings = [0 => 'x'],
    ?string $unselected = '#cccccc',
    string $channel = 'color',
): Plot
```

A drag-to-select interval named \$name. Unselected marks of the first layer turn \$unselected; other layers can react with filter: ['param' => \$name], and linked plots in a composition share it.

Function: [`brush()`]{#brush-function}.

### `highlight()` {#highlight}

```php
->highlight(
    string $aesthetic = 'color',
    string $name = 'highlight',
    ?string $bind = 'legend',
    float $opacity = 0.15,
): Plot
```

Click legend entries (or marks, with bind: null) to highlight their group; others fade to \$opacity.

Function: [`highlight()`]{#highlight-function}.

### `slider()` {#slider}

```php
->slider(
    string $name,
    int|float $min,
    int|float $max,
    int|float|null $step = null,
    int|float|null $value = null,
    ?string $label = null,
): Plot
```

A slider bound to a parameter; use expr(\$name) in geoms or a filter to react to it.

### `dropdown()` {#dropdown}

```php
->dropdown(
    string $name,
    array $options,
    mixed $value = null,
    ?string $label = null,
): Plot
```

A dropdown bound to a parameter.

### `param()` {#param}

```php
->param(
    string $name,
    mixed $value = null,
    ?array $bind = null,
    ?string $expr = null,
): Plot
```

A Vega-Lite parameter: a value, optionally bound to an input, or an expr.

### `select()` {#select}

```php
->select(array $param): Plot
```

A raw selection parameter, attached to the first layer.

## Functions

### `tooltip()` {#tooltip-function}

```php
tooltip(array|string|bool $fields = true): Closure
```

The pipe form of [`->tooltip()`](#tooltip).


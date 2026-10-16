<?php

declare(strict_types=1);

namespace Phit;

use JsonSerializable;

/**
 * Anything that compiles to a Vega-Lite spec, with the ways to render it: JSON, HTML, and static images.
 */
abstract class Chart implements JsonSerializable
{
    public const JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /** @return array<string, mixed> The Vega-Lite spec. */
    abstract public function toArray(): array;

    /** The spec for nesting inside a composition: no $schema, no config. */
    abstract public function toSpec(): array;

    public function toJson(bool $pretty = false): string
    {
        return json_encode($this->toArray(), self::JSON_FLAGS | ($pretty ? JSON_PRETTY_PRINT : 0));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * An HTML fragment that renders the chart: a container div plus, unless $assets is false, the script tags.
     *
     * @param array<string, mixed> $options vega-embed options, such as ['actions' => false, 'theme' => 'auto'].
     */
    public function embed(array $options = [], Assets|false|null $assets = null, array $attributes = []): string
    {
        return Html::embed($this, $options, $assets, $attributes);
    }

    /** A standalone HTML page. */
    public function html(?string $title = null, array $options = [], ?Assets $assets = null): string
    {
        return Html::page($this, $title, $options, $assets);
    }

    public function svg(?Export $export = null): string
    {
        return ($export ?? new Export)->svg($this);
    }

    public function png(float $scale = 2.0, ?Export $export = null): string
    {
        return ($export ?? new Export)->png($this, $scale);
    }

    public function pdf(?Export $export = null): string
    {
        return ($export ?? new Export)->pdf($this);
    }

    /** Writes to a file whose extension picks the format: svg, png, jpg, pdf, html, or json. */
    public function save(string $path, float $scale = 2.0, ?Export $export = null): string
    {
        return ($export ?? new Export)->save($this, $path, $scale);
    }
}

<?php

declare(strict_types=1);

namespace Phiz;

/**
 * One geom with its own mappings, options, and optional data. Plot's geom methods create layers; Compiler turns each
 * into one or more Vega-Lite layers.
 */
final class Layer
{
    /**
     * @param array<string, mixed> $params Geom options (bins, method, position, ...).
     * @param array<string, mixed> $style Fixed visual properties, ggplot2 names (alpha, size, linetype) or Vega-Lite mark properties.
     * @param string|array<string, mixed>|null $filter A Vega expression, or a Vega-Lite filter predicate such as ['param' => 'brush'].
     * @param array<string, mixed> $raw Vega-Lite merged into each compiled layer.
     */
    public function __construct(
        public readonly string $geom,
        public readonly Aes $aes,
        public readonly array $params = [],
        public readonly array $style = [],
        public readonly ?Data $data = null,
        public readonly bool $inheritAes = true,
        public readonly string|array|null $filter = null,
        public readonly array $raw = [],
    ) {}

    public function param(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }
}

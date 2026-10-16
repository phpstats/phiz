<?php

declare(strict_types=1);

namespace Phiz;

use InvalidArgumentException;

/**
 * Plots side by side, stacked, or in a grid, as patchwork does for ggplot2. Selections (brush) defined in one plot
 * can filter or highlight the others, which makes linked views.
 *
 *     Compose::beside($scatter, $histogram)->title('Two views');
 */
final class Compose extends Chart
{
    /** @param list<Chart> $charts */
    private function __construct(
        public readonly string $kind,
        public readonly array $charts,
        public readonly ?int $columns = null,
        public readonly string|array|null $title = null,
        public readonly array $config = [],
        public readonly ?array $resolve = null,
        public readonly array $raw = [],
    ) {
        if ($charts === []) {
            throw new InvalidArgumentException('A composition needs at least one chart.');
        }
    }

    public static function beside(Chart ...$charts): self
    {
        return new self('hconcat', array_values($charts));
    }

    public static function stack(Chart ...$charts): self
    {
        return new self('vconcat', array_values($charts));
    }

    /** A grid filled row by row with the given number of columns. */
    public static function grid(array $charts, int $columns = 2): self
    {
        if ($columns < 1) {
            throw new InvalidArgumentException('columns must be positive.');
        }

        return new self('concat', array_values($charts), $columns);
    }

    public function title(string $title, ?string $subtitle = null): self
    {
        return clone($this, ['title' => $subtitle === null ? $title : ['text' => $title, 'subtitle' => $subtitle]]);
    }

    /** A theme preset and options for the whole composition, as Plot::theme(). */
    public function theme(?string $preset = null, mixed ...$options): self
    {
        $config = Plot::of()->theme($preset, ...$options)->config;

        return clone($this, ['config' => Theme::merge($this->config, $config)]);
    }

    /** Shares or separates scales, axes, and legends across the plots, e.g. ['scale' => ['color' => 'independent']]. */
    public function resolve(array $resolve): self
    {
        return clone($this, ['resolve' => $resolve]);
    }

    public function raw(array $spec): self
    {
        return clone($this, ['raw' => Theme::merge($this->raw, $spec)]);
    }

    public function toArray(): array
    {
        $spec = ['$schema' => Assets::SCHEMA] + $this->toSpec();
        $config = $this->config;
        foreach ($this->charts as $chart) {
            if ($config === [] && ($chart instanceof Plot || $chart instanceof self) && $chart->config !== []) {
                $config = $chart->toArray()['config'] ?? []; // A composition takes its theme from the first themed plot.
            }
        }
        if (($config['range']['category'] ?? null) === 'hue') {
            $config['range']['category'] = Palette::hue(8);
        }
        if ($config !== []) {
            $spec['config'] = $config;
        }

        $spec = $this->raw === [] ? $spec : Theme::merge($spec, $this->raw);
        Compile\Compiler::warnSize($spec);

        return $spec;
    }

    public function toSpec(): array
    {
        $spec = [];
        if ($this->title !== null) {
            $spec['title'] = $this->title;
        }
        $children = array_map(fn (Chart $chart) => $chart->toSpec(), $this->charts);
        // Vega-Lite declares variable parameters (sliders, dropdowns) only at the top level of a composition.
        $params = [];
        foreach ($children as &$child) {
            $kept = [];
            foreach ($child['params'] ?? [] as $param) {
                if (isset($param['select'])) {
                    $kept[] = $param; // Selections stay in the view that defines them.
                } elseif (! in_array($param, $params, true)) {
                    $params[] = $param;
                }
            }
            if ($kept === []) {
                unset($child['params']);
            } else {
                $child['params'] = $kept;
            }
        }
        unset($child);
        if ($params !== []) {
            $spec['params'] = $params;
        }
        $spec[$this->kind] = $children;
        if ($this->columns !== null) {
            $spec['columns'] = $this->columns;
        }
        if ($this->resolve !== null) {
            $spec['resolve'] = $this->resolve;
        }

        return $spec;
    }
}

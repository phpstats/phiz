<?php

declare(strict_types=1);

/*
 * Sizes a chart for a documentation page. A plot whose width is not set fills its container, at its own height or a
 * default one. Wrapped facets split the content width between their columns instead. Compositions keep their sizes,
 * since Vega-Lite cannot fit them to a container.
 */

use Phiz\Chart;
use Phiz\Plot;

/** @return array<string, mixed> The chart's specification. */
function docs_fit(Chart $chart, int $width = 680, int $height = 320): array
{
    if (! $chart instanceof Plot || $chart->width !== null) {
        return $chart->toArray();
    }
    $spec = $chart->toArray();
    if (! isset($spec['facet'])) {
        return $chart->size('container', $chart->height ?? $height)->toArray();
    }
    $columns = $spec['columns'] ?? null;
    if (! is_int($columns)) {
        return $spec;
    }
    $panel = intdiv($width - 60 - ($columns - 1) * 20, $columns);

    return $chart->size($panel, $chart->height ?? min($panel, $height))->toArray();
}

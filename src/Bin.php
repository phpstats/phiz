<?php

declare(strict_types=1);

namespace Phiz;

use Generator;
use InvalidArgumentException;

/**
 * Server-side summaries for data too large to send to the browser: histograms, rectangular and hexagonal 2D bins,
 * line downsampling, and sampling. Each returns a small list of rows to plot.
 *
 * Sources are read without building row arrays when possible: a phibble Table (column() and nrow()), an array of
 * columns, a list of rows, or any iterable of rows (a generator streams in one pass when limits are given).
 */
final class Bin
{
    /**
     * Equal-width bins of x: rows with x0, x1, count, and density (count / (n * width)), per group.
     *
     * @param list<string> $groups
     * @param array{0: float, 1: float}|null $limits The range to bin; null reads the data's range first.
     * @return list<array<string, mixed>>
     */
    public static function histogram(mixed $source, string $x, int $bins = 30, int|float|null $binwidth = null, int|float|null $boundary = null, ?array $limits = null, array $groups = []): array
    {
        [$lo, $hi] = $limits ?? self::range($source, $x);
        [$start, $width, $count] = self::edges($lo, $hi, $bins, $binwidth, $boundary);

        $cells = [];
        $totals = [];
        foreach (self::values($source, [$x, ...$groups]) as $values) {
            $v = $values[0];
            if (! self::finite($v) || $v < $lo || $v > $hi) {
                continue;
            }
            // The epsilon keeps values on an edge in the bin they start, despite rounding, as Vega's bin does.
            $i = min($count - 1, max(0, (int) floor(($v - $start) / $width + 1e-9)));
            $key = self::key($values, 1);
            $cells[$key][$i] = ($cells[$key][$i] ?? 0) + 1;
            $totals[$key] = ($totals[$key] ?? 0) + 1;
        }

        $out = [];
        foreach ($cells as $key => $counts) {
            ksort($counts);
            foreach ($counts as $i => $n) {
                $out[] = self::groupValues($key, $groups) + [
                    $x.'_start' => $start + $i * $width,
                    $x.'_end' => $start + ($i + 1) * $width,
                    'count' => $n,
                    'density' => $n / ($totals[$key] * $width),
                ];
            }
        }

        return $out;
    }

    /**
     * Counts in a grid of x-by-y rectangles: rows with x_start, x_end, y_start, y_end (named after the fields) and
     * count. Empty cells are omitted.
     *
     * @param int|array{0: int, 1: int} $bins
     * @return list<array<string, mixed>>
     */
    public static function grid(mixed $source, string $x, string $y, int|array $bins = 30, ?array $xlim = null, ?array $ylim = null, array $groups = []): array
    {
        [$nx, $ny] = is_array($bins) ? $bins : [$bins, $bins];
        [$x0, $x1] = $xlim ?? self::range($source, $x);
        [$y0, $y1] = $ylim ?? self::range($source, $y);
        [$wx, $wy] = [($x1 - $x0) / $nx, ($y1 - $y0) / $ny];

        $cells = [];
        foreach (self::values($source, [$x, $y, ...$groups]) as $values) {
            [$vx, $vy] = $values;
            if (! self::finite($vx) || ! self::finite($vy) || $vx < $x0 || $vx > $x1 || $vy < $y0 || $vy > $y1) {
                continue;
            }
            $i = min($nx - 1, (int) floor(($vx - $x0) / $wx + 1e-9));
            $j = min($ny - 1, (int) floor(($vy - $y0) / $wy + 1e-9));
            $key = self::key($values, 2);
            $cells[$key][$i * $ny + $j] = ($cells[$key][$i * $ny + $j] ?? 0) + 1;
        }

        $out = [];
        foreach ($cells as $key => $counts) {
            ksort($counts);
            foreach ($counts as $cell => $n) {
                [$i, $j] = [intdiv($cell, $ny), $cell % $ny];
                $out[] = self::groupValues($key, $groups) + [
                    $x.'_start' => $x0 + $i * $wx, $x.'_end' => $x0 + ($i + 1) * $wx,
                    $y.'_start' => $y0 + $j * $wy, $y.'_end' => $y0 + ($j + 1) * $wy,
                    'count' => $n,
                ];
            }
        }

        return $out;
    }

    /**
     * Counts in a hexagonal grid, as ggplot2's geom_hex: rows with the hexagon centers (under the x and y names)
     * and count. bins hexagons span the x range; aspect (height / width of the panel in pixels) keeps them regular
     * on screen.
     *
     * @return list<array<string, mixed>>
     */
    public static function hex(mixed $source, string $x, string $y, int $bins = 30, ?array $xlim = null, ?array $ylim = null, float $aspect = 1.0, array $groups = []): array
    {
        [$x0, $x1] = $xlim ?? self::range($source, $x);
        [$y0, $y1] = $ylim ?? self::range($source, $y);
        $r = 1 / sqrt(3);            // Pointy-top hexagons one unit wide.
        $rowHeight = 1.5 * $r;
        [$sx, $sy] = [$bins / ($x1 - $x0), $bins * $aspect / ($y1 - $y0)];

        $cells = [];
        foreach (self::values($source, [$x, $y, ...$groups]) as $values) {
            [$vx, $vy] = $values;
            if (! self::finite($vx) || ! self::finite($vy) || $vx < $x0 || $vx > $x1 || $vy < $y0 || $vy > $y1) {
                continue;
            }
            [$u, $v] = [($vx - $x0) * $sx, ($vy - $y0) * $sy];
            // The nearest center is in one of the two rows around v.
            $best = null;
            $row = (int) floor($v / $rowHeight);
            foreach ([$row, $row + 1] as $candidate) {
                $offset = ($candidate & 1) ? 0.5 : 0.0;
                $col = (int) round($u - $offset);
                $d = ($u - $col - $offset) ** 2 + ($v - $candidate * $rowHeight) ** 2;
                if ($best === null || $d < $best[2]) {
                    $best = [$candidate, $col, $d];
                }
            }
            $key = self::key($values, 2);
            $id = $best[0].':'.$best[1];
            $cells[$key][$id] = ($cells[$key][$id] ?? 0) + 1;
        }

        $out = [];
        foreach ($cells as $key => $counts) {
            foreach ($counts as $id => $n) {
                [$row, $col] = array_map('intval', explode(':', $id));
                $u = $col + (($row & 1) ? 0.5 : 0.0);
                $out[] = self::groupValues($key, $groups) + [$x => $x0 + $u / $sx, $y => $y0 + $row * $rowHeight / $sy, 'count' => $n];
            }
        }

        return $out;
    }

    /**
     * Downsamples a line to about $threshold points with Largest-Triangle-Three-Buckets, which keeps the visual
     * shape (peaks and troughs). Rows are sorted by x; groups are downsampled separately.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public static function lttb(array $rows, string $x, string $y, int $threshold = 1000, array $groups = []): array
    {
        if ($threshold < 3) {
            throw new InvalidArgumentException('lttb needs a threshold of at least 3 points.');
        }
        $sets = [];
        foreach ($rows as $row) {
            if (is_numeric($row[$y] ?? null) && ($row[$x] ?? null) !== null) {
                $sets[self::key(array_map(fn ($g) => $row[$g] ?? null, $groups), 0)][] = $row;
            }
        }
        $out = [];
        foreach ($sets as $set) {
            $xs = array_map(fn ($row) => self::number($row[$x]), $set);
            array_multisort($xs, SORT_NUMERIC, $set);
            $n = count($set);
            if ($n <= $threshold) {
                array_push($out, ...$set);
                continue;
            }
            $every = ($n - 2) / ($threshold - 2);
            $a = 0;
            $out[] = $set[0];
            for ($i = 0; $i < $threshold - 2; $i++) {
                $start = (int) floor(($i + 1) * $every) + 1;
                $end = min($n, (int) floor(($i + 2) * $every) + 1);
                $avgX = 0.0;
                $avgY = 0.0;
                for ($j = $start; $j < $end; $j++) {
                    $avgX += $xs[$j];
                    $avgY += $set[$j][$y];
                }
                $span = max(1, $end - $start);
                [$avgX, $avgY] = [$avgX / $span, $avgY / $span];
                $from = (int) floor($i * $every) + 1;
                $to = (int) floor(($i + 1) * $every) + 1;
                $max = -1.0;
                $pick = $from;
                for ($j = $from; $j < $to; $j++) {
                    $area = abs(($xs[$a] - $avgX) * ($set[$j][$y] - $set[$a][$y]) - ($xs[$a] - $xs[$j]) * ($avgY - $set[$a][$y]));
                    if ($area > $max) {
                        [$max, $pick] = [$area, $j];
                    }
                }
                $out[] = $set[$pick];
                $a = $pick;
            }
            $out[] = $set[$n - 1];
        }

        return $out;
    }

    /**
     * A uniform random sample of n rows (reservoir sampling, one pass), reproducible with a seed.
     *
     * @return list<array<string, mixed>>
     */
    public static function sample(mixed $source, int $n, int $seed = 1): array
    {
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937($seed));
        $reservoir = [];
        $i = 0;
        foreach (self::rows($source) as $row) {
            if ($i < $n) {
                $reservoir[] = $row;
            } else {
                $j = $rng->getInt(0, $i);
                if ($j < $n) {
                    $reservoir[$j] = $row;
                }
            }
            $i++;
        }

        return $reservoir;
    }

    /** The [min, max] of a numeric field, padded when every value is equal. */
    public static function range(mixed $source, string $field): array
    {
        if ($source instanceof Generator) {
            throw new InvalidArgumentException("A generator can be read only once; pass the limits for '{$field}'.");
        }
        $lo = INF;
        $hi = -INF;
        foreach (self::values($source, [$field]) as [$v]) {
            if (self::finite($v)) {
                $lo = min($lo, (float) $v);
                $hi = max($hi, (float) $v);
            }
        }
        if (! is_finite($lo)) {
            throw new InvalidArgumentException("'{$field}' has no numeric values to bin.");
        }
        if ($hi - $lo <= 1e-12 * max(1.0, abs($hi))) {
            [$lo, $hi] = [$lo - 0.5, $hi + 0.5];
        }

        return [$lo, $hi];
    }

    /**
     * The values of the named fields, one list per record.
     *
     * @return Generator<int, list<mixed>>
     */
    public static function values(mixed $source, array $fields): Generator
    {
        if ($source instanceof Data) {
            $source = $source->source();
        }
        if (is_object($source) && method_exists($source, 'column') && method_exists($source, 'nrow')) {
            $columns = array_map(fn ($f) => array_values($source->column($f)), $fields);
            $n = $source->nrow();
            for ($i = 0; $i < $n; $i++) {
                yield array_map(fn ($column) => Data::value($column[$i] ?? null), $columns);
            }

            return;
        }
        if (is_array($source) && $source !== [] && ! array_is_list($source)) {
            $columns = array_map(fn ($f) => $source[$f] ?? throw new InvalidArgumentException("No column '{$f}'."), $fields);
            $n = count($columns[0]);
            for ($i = 0; $i < $n; $i++) {
                yield array_map(fn ($column) => Data::value($column[$i] ?? null), $columns);
            }

            return;
        }
        foreach (self::rows($source) as $row) {
            yield array_map(fn ($f) => Data::value($row[$f] ?? null), $fields);
        }
    }

    /** @return iterable<array<string, mixed>> */
    private static function rows(mixed $source): iterable
    {
        if ($source instanceof Data) {
            $source = $source->rows ?? [];
        }
        if (is_object($source) && method_exists($source, 'toRows')) {
            $source = $source->toRows();
        } elseif (is_array($source) && $source !== [] && ! array_is_list($source)) {
            $source = Data::from($source)->rows;
        }
        if (! is_iterable($source)) {
            throw new InvalidArgumentException('Bin needs rows, columns, a table, or an iterable of rows.');
        }
        foreach ($source as $row) {
            yield is_object($row) ? (method_exists($row, 'toArray') ? $row->toArray() : get_object_vars($row)) : $row;
        }
    }

    /** Bin start, width, and count covering [lo, hi]. */
    private static function edges(float $lo, float $hi, int $bins, int|float|null $binwidth, int|float|null $boundary): array
    {
        if ($binwidth !== null) {
            if (! ($binwidth > 0)) {
                throw new InvalidArgumentException('binwidth must be positive.');
            }
            $anchor = $boundary ?? 0.0;
            $start = $anchor + floor(($lo - $anchor) / $binwidth) * $binwidth;

            return [$start, (float) $binwidth, max(1, (int) ceil(($hi - $start) / $binwidth - 1e-9))];
        }
        if ($bins < 1) {
            throw new InvalidArgumentException('bins must be positive.');
        }

        return [$lo, ($hi - $lo) / $bins, $bins];
    }

    private static function key(array $values, int $offset): string
    {
        return json_encode(array_slice($values, $offset));
    }

    private static function groupValues(string $key, array $groups): array
    {
        return $groups === [] ? [] : array_combine($groups, json_decode($key, true));
    }

    /** A finite number: NAN, INF, numeric strings, and dates are not binned. */
    private static function finite(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value);
    }

    private static function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : (float) strtotime((string) $value);
    }
}

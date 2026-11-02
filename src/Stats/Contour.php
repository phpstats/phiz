<?php

declare(strict_types=1);

namespace Phiz\Stats;

use InvalidArgumentException;

/**
 * Two-dimensional densities and contours, for density2d() and contour(): MASS::kde2d, R's pretty() breaks, and
 * marching squares for contour lines and filled contour bands.
 *
 * A grid is ['x' => list<float>, 'y' => list<float>, 'z' => list<list<float|null>>] with z[i][j] at (x[i], y[j]).
 */
final class Contour
{
    /** MASS::bandwidth.nrd: 4 · 1.06 · min(sd, IQR / 1.34) · n^(-1/5). */
    public static function bandwidthNrd(array $values): float
    {
        $values = array_map(floatval(...), array_values($values));
        $n = count($values);
        if ($n < 2) {
            throw new InvalidArgumentException('A 2D density needs at least 2 points per group.');
        }
        sort($values);
        $mean = array_sum($values) / $n;
        $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($n - 1));
        $iqr = Fit::quantile($values, 0.75) - Fit::quantile($values, 0.25);

        return 4 * 1.06 * min($sd, $iqr / 1.34) * $n ** -0.2;
    }

    /**
     * MASS::kde2d: a bivariate normal kernel density on an n × n grid over the limits. h holds the full bandwidths
     * (kde2d divides them by 4 to get the kernel standard deviations).
     *
     * @param list<float> $x
     * @param list<float> $y
     * @param array{0: float, 1: float} $h
     * @param array{0: float, 1: float, 2: float, 3: float} $lims xmin, xmax, ymin, ymax.
     */
    public static function kde2d(array $x, array $y, array $h, array $lims, int $n = 100): array
    {
        $count = count($x);
        if ($count === 0 || $count !== count($y) || $n < 2 || ! ($h[0] > 0) || ! ($h[1] > 0)) {
            throw new InvalidArgumentException('kde2d needs points, positive bandwidths, and n >= 2.');
        }
        [$hx, $hy] = [$h[0] / 4, $h[1] / 4];
        $gx = self::seq($lims[0], $lims[1], $n);
        $gy = self::seq($lims[2], $lims[3], $n);
        $norm = 1 / sqrt(2 * M_PI);
        $kernel = function (array $grid, array $values, float $bw) use ($norm): array {
            $out = [];
            foreach ($grid as $g) {
                $row = [];
                foreach ($values as $v) {
                    $u = ($g - $v) / $bw;
                    $row[] = $u > -40 && $u < 40 ? $norm * exp(-0.5 * $u * $u) : 0.0;
                }
                $out[] = $row;
            }

            return $out;
        };
        $ax = $kernel($gx, $x, $hx);
        $ay = $kernel($gy, $y, $hy);
        $z = [];
        $scale = 1 / ($count * $hx * $hy);
        foreach ($ax as $i => $rx) {
            foreach ($ay as $j => $ry) {
                $sum = 0.0;
                foreach ($rx as $k => $v) {
                    $sum += $v * $ry[$k];
                }
                $z[$i][$j] = $sum * $scale;
            }
        }

        return ['x' => $gx, 'y' => $gy, 'z' => $z];
    }

    /** A grid from rows of a regular x-by-y lattice (as R's volcano or ggplot2's faithfuld), missing cells null. */
    public static function grid(array $rows, string $x, string $y, string $z): array
    {
        $xs = [];
        $ys = [];
        $cells = [];
        foreach ($rows as $row) {
            [$xi, $yi, $zi] = [$row[$x] ?? null, $row[$y] ?? null, $row[$z] ?? null];
            if (! is_numeric($xi) || ! is_numeric($yi)) {
                continue;
            }
            $xs[(string) (float) $xi] = (float) $xi;
            $ys[(string) (float) $yi] = (float) $yi;
            if (is_numeric($zi) && is_finite((float) $zi)) {
                $cells[(string) (float) $xi][(string) (float) $yi] = (float) $zi;
            }
        }
        sort($xs);
        sort($ys);
        if (count($xs) < 2 || count($ys) < 2) {
            throw new InvalidArgumentException('contour needs z on a grid of at least 2 distinct x and 2 distinct y values.');
        }
        $grid = [];
        foreach ($xs as $i => $xi) {
            foreach ($ys as $j => $yi) {
                $grid[$i][$j] = $cells[(string) $xi][(string) $yi] ?? null;
            }
        }

        return ['x' => $xs, 'y' => $ys, 'z' => $grid];
    }

    /**
     * About n + 1 equally spaced round values covering [lo, hi], as R's pretty(c(lo, hi), n) returns with its default
     * settings. The step is 1, 2, or 5 times a power of ten.
     *
     * Written from R's documentation of pretty() and from R's output, not from R's source. The documentation gives
     * the cell (the range over n, or for a range near zero three quarters of the values' size over n %/% 3) and its
     * power-of-ten base b. The unit's switch points, c/b above 1.4, 2.8, and 7, the near-zero threshold, and the order
     * of widening come from R 4.6's output, against which this matches on 10,052 random and edge-case ranges.
     *
     * @return list<float>
     */
    public static function pretty(float $lo, float $hi, int $n = 10): array
    {
        $intervals = max(1, $n);
        $fewest = intdiv($n, 3);
        $size = max(abs($lo), abs($hi));
        $flat = $hi == $lo || $hi - $lo < $size * PHP_FLOAT_EPSILON * (4.25 * $intervals + 0.25);
        $cell = $flat ? ($size == 0.0 ? 1.0 : $size) * 0.75 / max(1, $fewest) : ($hi - $lo) / $intervals;
        $cell = max($cell, 2 ** -20 * PHP_FLOAT_MIN);

        // b <= cell < 10 b, corrected for log10's rounding at exact powers of ten.
        $base = 10 ** floor(log10($cell));
        $base = match (true) {
            $cell / $base >= 10 => $base * 10,
            $cell / $base < 1 => $base / 10,
            default => $base,
        };
        $ratio = $cell / $base;
        $unit = $base * match (true) {
            $ratio <= 1.4 => 1,
            $ratio <= 2.8 => 2,
            $ratio <= 7 => 5,
            default => 10,
        };

        // Whole units covering the range; the tolerance absorbs the rounding error of the division.
        $first = (int) floor($lo / $unit + 1e-7);
        $last = (int) ceil($hi / $unit - 1e-7);
        // Fewer than n %/% 3 intervals: widen by one unit at a time, alternately, starting toward zero.
        $upward = $lo + $hi < 0;
        while ($last - $first < $fewest) {
            if ((($last - $first) % 2 === 0) === $upward) {
                $last++;
            } else {
                $first--;
            }
        }

        // Each value has at most as many decimals as the unit, so rounding there removes k · unit's error exactly.
        $decimals = max(0, -(int) floor(log10($unit) + 1e-9));
        $out = [];
        for ($k = $first; $k <= $last; $k++) {
            $out[] = round($k * $unit, $decimals);
        }

        return $out;
    }

    /**
     * Breaks for contouring z values in [lo, hi], as ggplot2's contour_breaks(): explicit breaks; bins, over the range
     * rounded out to a tenth of its leading digit; a binwidth (scales::fullseq); or pretty(range, 10).
     *
     * @return list<float>
     */
    public static function breaks(float $lo, float $hi, ?int $bins = null, int|float|null $binwidth = null, ?array $breaks = null): array
    {
        if ($breaks !== null) {
            return array_values(array_map(floatval(...), $breaks));
        }
        if ($bins === null && $binwidth === null) {
            return self::pretty($lo, $hi, 10);
        }
        if ($bins !== null) {
            if ($bins < 1) {
                throw new InvalidArgumentException('contour bins must be at least 1.');
            }
            $span = $hi - $lo;
            if ($span > 0) {
                $accuracy = (float) sprintf('%.0e', $span) / 10;
                [$lo, $hi] = [floor($lo / $accuracy) * $accuracy, ceil($hi / $accuracy) * $accuracy];
            }
            if ($bins === 1) {
                return [$lo, $hi];
            }
            $out = self::fullseq($lo, $hi, ($hi - $lo) / ($bins - 1));
            // The sequence can come out one bin short; ggplot2 then divides the range into bins instead.
            return count($out) < $bins + 1 ? self::fullseq($lo, $hi, ($hi - $lo) / $bins) : $out;
        }
        if (! ($binwidth > 0)) {
            throw new InvalidArgumentException('contour binwidth must be positive.');
        }

        return self::fullseq($lo, $hi, (float) $binwidth);
    }

    /** scales::fullseq(): multiples of size from below lo to above hi. */
    private static function fullseq(float $lo, float $hi, float $size): array
    {
        if (! ($size > 0) || $hi - $lo <= 1e-12 * max(1.0, abs($hi))) {
            return [$lo - $size / 2, $hi + $size / 2];
        }
        [$from, $to] = [floor($lo / $size), ceil($hi / $size)];
        $out = [];
        for ($k = $from; $k <= $to + 1e-10; $k++) {
            $out[] = $k * $size;
        }

        return $out;
    }

    /**
     * Numbers formatted with a shared number of decimals, as R's format(x, digits = 3): enough for each value to
     * show its first three significant digits.
     *
     * @param list<float> $values
     * @return list<string>
     */
    public static function format(array $values, int $digits = 3): array
    {
        $decimals = 0;
        foreach ($values as $v) {
            if ($v == 0 || ! is_finite($v)) {
                continue;
            }
            $mantissa = rtrim(rtrim(explode('e', sprintf('%.'.($digits - 1).'e', $v))[0], '0'), '.');
            $exponent = (int) explode('e', sprintf('%.'.($digits - 1).'e', $v))[1];
            $significant = strlen(str_replace(['-', '.'], '', $mantissa));
            $decimals = max($decimals, $significant - 1 - $exponent);
        }

        return array_map(fn ($v) => number_format($v, max(0, $decimals), '.', ''), $values);
    }

    /**
     * Contour lines of the grid at a level: polylines, each a list of [x, y] points. Lines that cross the grid end
     * at its edges; the others close on themselves.
     *
     * @return list<list<array{0: float, 1: float}>>
     */
    public static function lines(array $grid, float $level): array
    {
        return self::trace($grid, $level);
    }

    /**
     * The region where z >= level, as closed rings: outer boundaries run counterclockwise and holes clockwise, so a
     * nonzero fill draws the region with its holes. The grid's edges close rings that reach them.
     *
     * @return list<list<array{0: float, 1: float}>>
     */
    public static function region(array $grid, float $level): array
    {
        // Pad with a value below every level at the edge coordinates, so contours run along the edges and close.
        [$xs, $ys, $z] = [$grid['x'], $grid['y'], $grid['z']];
        $nx = count($xs);
        $ny = count($ys);
        $low = -INF;
        $px = [$xs[0], ...$xs, $xs[$nx - 1]];
        $py = [$ys[0], ...$ys, $ys[$ny - 1]];
        $pz = [array_fill(0, $ny + 2, $low)];
        foreach ($z as $column) {
            $pz[] = [$low, ...$column, $low];
        }
        $pz[] = array_fill(0, $ny + 2, $low);

        return array_values(array_filter(self::trace(['x' => $px, 'y' => $py, 'z' => $pz], $level), fn ($ring) => count($ring) >= 4));
    }

    /**
     * Marching squares. Each segment runs with the region z >= level on its left; saddles are resolved by the cell's
     * mean. Missing values leave their cells empty.
     */
    private static function trace(array $grid, float $level): array
    {
        [$xs, $ys, $z] = [$grid['x'], $grid['y'], $grid['z']];
        $nx = count($xs);
        $ny = count($ys);
        // Edges of a cell in counterclockwise order: bottom, right, top, left, each from its first corner to its second.
        $next = [];
        $points = [];
        $point = function (int $i1, int $j1, int $i2, int $j2) use ($xs, $ys, $z, $level, &$points): string {
            $key = $i1 === $i2 ? "v{$i1}:".min($j1, $j2) : 'h'.min($i1, $i2).":{$j1}";
            if (! isset($points[$key])) {
                [$a, $b] = [$z[$i1][$j1], $z[$i2][$j2]];
                $t = $a === $b || is_infinite($a) || is_infinite($b) ? (is_infinite($a) ? 1.0 : (is_infinite($b) ? 0.0 : 0.5)) : ($level - $a) / ($b - $a);
                $points[$key] = [$xs[$i1] + $t * ($xs[$i2] - $xs[$i1]), $ys[$j1] + $t * ($ys[$j2] - $ys[$j1])];
            }

            return $key;
        };
        for ($i = 0; $i < $nx - 1; $i++) {
            for ($j = 0; $j < $ny - 1; $j++) {
                $corners = [[$i, $j], [$i + 1, $j], [$i + 1, $j + 1], [$i, $j + 1]];
                $values = array_map(fn ($c) => $z[$c[0]][$c[1]], $corners);
                if (in_array(null, $values, true)) {
                    continue;
                }
                $above = array_map(fn ($v) => $v >= $level, $values);
                if (! in_array(true, $above, true) || ! in_array(false, $above, true)) {
                    continue;
                }
                // Crossings in counterclockwise order: exits (above to below) start segments, entries end them.
                $exits = [];
                $entries = [];
                for ($e = 0; $e < 4; $e++) {
                    $f = ($e + 1) % 4;
                    if ($above[$e] !== $above[$f]) {
                        $key = $point(...$corners[$e], ...$corners[$f]);
                        $above[$e] ? $exits[$e] = $key : $entries[$e] = $key;
                    }
                }
                $finite = array_filter($values, is_finite(...));
                $center = $finite === [] ? -INF : array_sum($finite) / count($finite);
                $connected = count($exits) === 2 && $center >= $level;
                foreach ($exits as $e => $from) {
                    // A segment ends at the next entry counterclockwise when the above corners connect through the
                    // cell, and at the previous one (clockwise) when they are cut off.
                    for ($s = 1; $s < 4; $s++) {
                        $edge = $connected ? ($e + $s) % 4 : ($e - $s + 4) % 4;
                        if (isset($entries[$edge])) {
                            $next[$from] = $entries[$edge];
                            break;
                        }
                    }
                }
            }
        }

        // Chain segments: open lines start where no segment ends; what remains are closed rings.
        $incoming = array_flip(array_values($next));
        $lines = [];
        $seen = [];
        $starts = array_merge(array_values(array_filter(array_keys($next), fn ($k) => ! isset($incoming[$k]))), array_keys($next));
        foreach ($starts as $start) {
            if (isset($seen[$start])) {
                continue;
            }
            $line = [$points[$start]];
            $key = $start;
            $seen[$key] = true;
            while (isset($next[$key])) {
                $key = $next[$key];
                $line[] = $points[$key];
                if (isset($seen[$key])) {
                    break;
                }
                $seen[$key] = true;
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * A confidence ellipse for bivariate data, as ggplot2's stat_ellipse(): type t (a multivariate t fit, as MASS's
     * cov.trob makes), norm (the sample covariance), or euclid (a circle of radius level). The radius for t and norm is
     * √(2 F(level; 2, n − 1)). Returns segments + 1 points, closed; empty for fewer than 4 points.
     *
     * @param list<float> $x
     * @param list<float> $y
     * @return list<array{0: float, 1: float}>
     */
    public static function ellipse(array $x, array $y, string $type = 't', float $level = 0.95, int $segments = 51): array
    {
        $n = count($x);
        if (! in_array($type, ['t', 'norm', 'euclid'], true)) {
            throw new InvalidArgumentException("ellipse type must be 't', 'norm', or 'euclid'.");
        }
        if ($n - 1 < 3) {
            return [];
        }
        [$center, $shape] = $type === 't' ? self::multivariateT($x, $y) : self::covariance($x, $y);
        if ($type === 'euclid') {
            $shape = [[min($shape[0][0], $shape[1][1]), 0.0], [0.0, min($shape[0][0], $shape[1][1])]];
        }
        // Upper Cholesky factor R, with RᵀR = shape.
        $r11 = sqrt($shape[0][0]);
        $r12 = $shape[0][1] / $r11;
        $r22 = sqrt(max(0.0, $shape[1][1] - $r12 * $r12));
        if (! is_finite($r11) || ! is_finite($r22) || $r11 <= 0) {
            return [];
        }
        $d = $n - 1;
        // The F(2, d) quantile has a closed form: d/2 · ((1 − p)^(−2/d) − 1).
        $radius = $type === 'euclid' ? $level / max($r11, $r12, $r22) : sqrt(2 * $d / 2 * ((1 - $level) ** (-2 / $d) - 1));
        $out = [];
        for ($i = 0; $i <= $segments; $i++) {
            $angle = $i * 2 * M_PI / $segments;
            [$c, $s] = [cos($angle), sin($angle)];
            $out[] = [$center[0] + $radius * $c * $r11, $center[1] + $radius * ($c * $r12 + $s * $r22)];
        }

        return $out;
    }

    /** The mean and the sample covariance (n − 1), as stats::cov.wt. */
    private static function covariance(array $x, array $y): array
    {
        $n = count($x);
        [$mx, $my] = [array_sum($x) / $n, array_sum($y) / $n];
        [$sxx, $sxy, $syy] = [0.0, 0.0, 0.0];
        foreach ($x as $i => $xi) {
            [$dx, $dy] = [$xi - $mx, $y[$i] - $my];
            [$sxx, $sxy, $syy] = [$sxx + $dx * $dx, $sxy + $dx * $dy, $syy + $dy * $dy];
        }

        return [[$mx, $my], [[$sxx / ($n - 1), $sxy / ($n - 1)], [$sxy / ($n - 1), $syy / ($n - 1)]]];
    }

    /**
     * The location and scatter of a bivariate t distribution with nu degrees of freedom, fitted by the EM algorithm of
     * Kent, Tyler & Vardi (1994), "A curious likelihood identity for the multivariate t-distribution", Communications
     * in Statistics: Simulation and Computation 23, 441-453: each point is weighted (nu + 2) / (nu + d²) by its squared
     * Mahalanobis distance d² under the current fit, and the location and scatter are re-estimated from the weights.
     *
     * The defaults are those MASS::cov.trob documents (nu 5, 25 steps, tolerance 0.01), which ggplot2's stat_ellipse()
     * uses. The stopping rule (every weight within the tolerance of its previous value) and the returned covariance
     * (the last weights about the step's starting location, over n) were settled against cov.trob's output on the
     * faithful data, to 1e-13. MASS's source was not used.
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}}
     */
    private static function multivariateT(array $x, array $y, float $nu = 5, int $steps = 25, float $tolerance = 0.01): array
    {
        $count = count($x);
        // Weighted sums of squares and cross products about a center.
        $moments = function (array $center, array $weights) use ($x, $y): array {
            [$xx, $xy, $yy] = [0.0, 0.0, 0.0];
            foreach ($weights as $i => $weight) {
                [$u, $v] = [$x[$i] - $center[0], $y[$i] - $center[1]];
                $xx += $weight * $u * $u;
                $xy += $weight * $u * $v;
                $yy += $weight * $v * $v;
            }

            return [$xx, $xy, $yy];
        };

        $center = [array_sum($x) / $count, array_sum($y) / $count];
        $weights = array_fill(0, $count, 1.0);
        $from = $center;
        for ($step = 0; $step < $steps; $step++) {
            $from = $center;
            $total = array_sum($weights);
            [$sxx, $sxy, $syy] = array_map(fn (float $m) => $m / $total, $moments($center, $weights));
            $det = $sxx * $syy - $sxy * $sxy;
            if (! ($det > 0)) {
                throw new InvalidArgumentException('stat_ellipse needs points that are not all on a line.');
            }
            $previous = $weights;
            foreach ($weights as $i => $_) {
                [$u, $v] = [$x[$i] - $center[0], $y[$i] - $center[1]];
                $distance = ($syy * $u * $u - 2 * $sxy * $u * $v + $sxx * $v * $v) / $det;
                $weights[$i] = ($nu + 2) / ($nu + $distance);
            }
            $total = array_sum($weights);
            $center = [0.0, 0.0];
            foreach ($weights as $i => $weight) {
                $center[0] += $weight * $x[$i] / $total;
                $center[1] += $weight * $y[$i] / $total;
            }
            $change = max(array_map(fn (float $now, float $before) => abs($now - $before), $weights, $previous));
            if ($change < $tolerance) {
                break;
            }
        }
        [$xx, $xy, $yy] = array_map(fn (float $m) => $m / $count, $moments($from, $weights));

        return [$center, [[$xx, $xy], [$xy, $yy]]];
    }

    /** @return list<float> */
    private static function seq(float $from, float $to, int $n): array
    {
        return array_map(fn ($i) => $from + ($to - $from) * $i / ($n - 1), range(0, $n - 1));
    }
}

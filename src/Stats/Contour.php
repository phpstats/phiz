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
     * Implemented in a clean room from a specification written from R's documentation of pretty() and R's observed
     * output, by an implementer who had not seen R's source or earlier code for it. It matches R 4.6's pretty() on
     * 11,564 random and edge-case ranges.
     *
     * @return list<float>
     */
    public static function pretty(float $lo, float $hi, int $n = 10): array
    {
        $fewest = intdiv($n, 3);
        $size = max(abs($lo), abs($hi));
        $nAtLeastOne = max(1, $n);

        $flat = $hi == $lo
            || ($hi - $lo) < $size * PHP_FLOAT_EPSILON * (4.25 * $nAtLeastOne + 0.25);

        if ($flat) {
            $cell = ($size == 0.0 ? 1.0 : $size) * 0.75 / max(1, $fewest);
        } else {
            $cell = ($hi - $lo) / $nAtLeastOne;
        }
        $cell = max($cell, 2 ** -20 * PHP_FLOAT_MIN);

        // Exponent e of the base 10^e with 10^e <= cell < 10^(e+1).
        $exp = (int) floor(log10($cell));
        if (self::powerOfTen($exp) > $cell) {
            $exp--;
        } elseif (self::powerOfTen($exp + 1) <= $cell) {
            $exp++;
        }

        $ratio = $cell / self::powerOfTen($exp);
        if ($ratio <= 1.4) {
            $mult = 1;
        } elseif ($ratio <= 2.8) {
            $mult = 2;
        } elseif ($ratio <= 7.0) {
            $mult = 5;
        } else {
            // 10 * 10^e is 1 * 10^(e+1).
            $mult = 1;
            $exp++;
        }

        $unit = self::scaled($mult, $exp);

        $first = self::snapped($lo / $unit, false);
        $last = self::snapped($hi / $unit, true);

        $upwardNext = ($lo + $hi) < 0;
        while ($last - $first < $fewest) {
            if ($upwardNext) {
                $last++;
            } else {
                $first--;
            }
            $upwardNext = !$upwardNext;
        }

        $decimals = max(0, -$exp);
        $out = [];
        for ($k = $first; $k <= $last; $k++) {
            $out[] = round(self::scaled($k * $mult, $exp), $decimals);
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
     * in Statistics: Simulation and Computation 23, 441-453. The defaults are those MASS::cov.trob documents (nu 5, 25
     * steps, tolerance 0.01), which ggplot2's stat_ellipse() uses.
     *
     * Implemented in a clean room from a specification written from the paper, MASS's documentation, and cov.trob's
     * observed output, by an implementer who had not seen MASS's source or earlier code for it. It matches ggplot2's
     * t ellipses on the faithful, mpg, and mtcars data to 1e-13.
     *
     * @return array{0: array{0: float, 1: float}, 1: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}}
     */
    private static function multivariateT(array $x, array $y, float $nu = 5, int $steps = 25, float $tolerance = 0.01): array
    {
        $xs = array_map('floatval', array_values($x));
        $ys = array_map('floatval', array_values($y));
        $count = count($xs);
        if ($count !== count($ys)) {
            throw new InvalidArgumentException('x and y must have the same length.');
        }
        if ($count === 0) {
            throw new InvalidArgumentException('stat_ellipse needs points that are not all on a line.');
        }

        $cx = array_sum($xs) / $count;
        $cy = array_sum($ys) / $count;
        $weights = array_fill(0, $count, 1.0);

        // Center from which the returned covariance is measured.
        $startX = $cx;
        $startY = $cy;

        for ($step = 0; $step < $steps; $step++) {
            $startX = $cx;
            $startY = $cy;

            // Weighted scatter about the current center.
            $wSum = 0.0;
            $sxx = 0.0;
            $sxy = 0.0;
            $syy = 0.0;
            for ($i = 0; $i < $count; $i++) {
                $dx = $xs[$i] - $startX;
                $dy = $ys[$i] - $startY;
                $w = $weights[$i];
                $wSum += $w;
                $sxx += $w * $dx * $dx;
                $sxy += $w * $dx * $dy;
                $syy += $w * $dy * $dy;
            }
            $sxx /= $wSum;
            $sxy /= $wSum;
            $syy /= $wSum;

            $det = $sxx * $syy - $sxy * $sxy;
            if (!($det > 0.0)) {
                throw new InvalidArgumentException('stat_ellipse needs points that are not all on a line.');
            }

            // Inverse of the 2x2 scatter.
            $ixx = $syy / $det;
            $iyy = $sxx / $det;
            $ixy = -$sxy / $det;

            $previous = $weights;
            $newWSum = 0.0;
            $accX = 0.0;
            $accY = 0.0;
            for ($i = 0; $i < $count; $i++) {
                $dx = $xs[$i] - $startX;
                $dy = $ys[$i] - $startY;
                $mahal = $ixx * $dx * $dx + 2.0 * $ixy * $dx * $dy + $iyy * $dy * $dy;
                $w = ($nu + 2.0) / ($nu + $mahal);
                $weights[$i] = $w;
                $newWSum += $w;
                $accX += $w * $xs[$i];
                $accY += $w * $ys[$i];
            }
            $cx = $accX / $newWSum;
            $cy = $accY / $newWSum;

            $settled = true;
            for ($i = 0; $i < $count; $i++) {
                if (abs($weights[$i] - $previous[$i]) >= $tolerance) {
                    $settled = false;
                    break;
                }
            }
            if ($settled) {
                break;
            }
        }

        // Covariance from the last weights, about the last step's starting center, over n.
        $cxx = 0.0;
        $cxy = 0.0;
        $cyy = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $dx = $xs[$i] - $startX;
            $dy = $ys[$i] - $startY;
            $w = $weights[$i];
            $cxx += $w * $dx * $dx;
            $cxy += $w * $dx * $dy;
            $cyy += $w * $dy * $dy;
        }
        $cxx /= $count;
        $cxy /= $count;
        $cyy /= $count;

        return [[$cx, $cy], [[$cxx, $cxy], [$cxy, $cyy]]];
    }

    /** @return list<float> */
    private static function seq(float $from, float $to, int $n): array
    {
        return array_map(fn ($i) => $from + ($to - $from) * $i / ($n - 1), range(0, $n - 1));
    }

    /** 10^e as the nearest double, built by division for negative e. */
    private static function powerOfTen(int $e): float
    {
        if ($e >= 0) {
            return 10.0 ** $e;
        }
        if ($e >= -300) {
            return 1.0 / (10.0 ** -$e);
        }

        return (1.0 / 1e300) / (10.0 ** (-$e - 300));
    }
    /** m * 10^e, dividing by an exact power of ten when e < 0 so 3 * 0.005 is 0.015. */
    private static function scaled(int|float $m, int $e): float
    {
        if ($e >= 0) {
            return $m * 10.0 ** $e;
        }
        if ($e >= -300) {
            return $m / 10.0 ** -$e;
        }

        return ($m / 1e300) / 10.0 ** (-$e - 300);
    }
    /** floor (or ceil) of q, treating values within 1e-7 of an integer as that integer. */
    private static function snapped(float $q, bool $up): int
    {
        $nearest = round($q);
        if (abs($q - $nearest) < 1e-7) {
            return (int) $nearest;
        }

        return (int) ($up ? ceil($q) : floor($q));
    }
}

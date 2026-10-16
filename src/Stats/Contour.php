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
     * R's pretty(c(lo, hi), n): about n + 1 equally spaced round values covering the range.
     *
     * @return list<float>
     */
    public static function pretty(float $lo, float $hi, int $n = 10): array
    {
        $minN = intdiv($n, 3);
        $h = 1.5;
        $h5 = 0.5 + 1.5 * $h;
        $dx = $hi - $lo;
        if ($dx == 0 && $hi == 0) {
            $cell = 1.0;
            $small = true;
        } else {
            $cell = max(abs($lo), abs($hi));
            $u = 1 + ($h5 >= 1.5 * $h + 0.5 ? 1 / (1 + $h) : 1.5 / (1 + $h5));
            $u *= max(1, $n) * PHP_FLOAT_EPSILON;
            $small = $dx < $cell * $u * 3;
        }
        if ($small) {
            if ($cell > 10) {
                $cell = 9 + $cell / 10;
            }
            $cell *= 0.75;
            if ($minN > 1) {
                $cell /= $minN;
            }
        } else {
            $cell = $dx;
            if ($n > 1) {
                $cell /= $n;
            }
        }
        if ($cell < 20 * PHP_FLOAT_MIN) {
            $cell = 20 * PHP_FLOAT_MIN;
        }
        $base = 10 ** floor(log10($cell));
        $unit = $base;
        if (2 * $base - $cell < $h * ($cell - $unit)) {
            $unit = 2 * $base;
            if (5 * $base - $cell < $h5 * ($cell - $unit)) {
                $unit = 5 * $base;
                if (10 * $base - $cell < $h * ($cell - $unit)) {
                    $unit = 10 * $base;
                }
            }
        }
        $ns = floor($lo / $unit + 1e-10);
        $nu = ceil($hi / $unit - 1e-10);
        while ($ns * $unit > $lo + 1e-10 * $unit) {
            $ns--;
        }
        while ($nu * $unit < $hi - 1e-10 * $unit) {
            $nu++;
        }
        $k = (int) (0.5 + $nu - $ns);
        if ($k < $minN) {
            $k = $minN - $k;
            if ($ns >= 0) {
                $nu += intdiv($k, 2);
                $ns -= intdiv($k, 2) + $k % 2;
            } else {
                $ns -= intdiv($k, 2);
                $nu += intdiv($k, 2) + $k % 2;
            }
        }
        $out = [];
        for ($i = $ns; $i <= $nu + 1e-9; $i++) {
            // Round away the floating error of i · unit, as R's seq() output prints.
            $out[] = round($i * $unit, 12 - (int) floor(log10($unit)));
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
     * A confidence ellipse for bivariate data, as ggplot2's stat_ellipse(): type t (a multivariate t fit, MASS's
     * cov.trob), norm (the sample covariance), or euclid (a circle of radius level). The radius for t and norm is
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
        [$center, $shape] = $type === 't' ? self::covTrob($x, $y) : self::covariance($x, $y);
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

    /** MASS::cov.trob(cbind(x, y)): a multivariate t (nu = 5) location and scatter by iterative reweighting. */
    private static function covTrob(array $x, array $y, float $nu = 5, int $maxit = 25, float $tol = 0.01): array
    {
        $n = count($x);
        $p = 2;
        $loc = [array_sum($x) / $n, array_sum($y) / $n];
        $w = array_fill(0, $n, 1.0);
        $centered = [];
        for ($iter = 0; $iter < $maxit; $iter++) {
            $w0 = $w;
            $centered = array_map(fn ($xi, $yi) => [$xi - $loc[0], $yi - $loc[1]], $x, $y);
            // Mahalanobis distances under the weighted scatter Σ (w / Σw) X Xᵀ.
            $total = array_sum($w);
            [$a, $b, $c] = [0.0, 0.0, 0.0];
            foreach ($centered as $i => [$dx, $dy]) {
                $f = $w[$i] / $total;
                [$a, $b, $c] = [$a + $f * $dx * $dx, $b + $f * $dx * $dy, $c + $f * $dy * $dy];
            }
            $det = $a * $c - $b * $b;
            if (! ($det > 0)) {
                throw new InvalidArgumentException('stat_ellipse needs points that are not all on a line.');
            }
            foreach ($centered as $i => [$dx, $dy]) {
                $q = ($c * $dx * $dx - 2 * $b * $dx * $dy + $a * $dy * $dy) / $det;
                $w[$i] = ($nu + $p) / ($nu + $q);
            }
            $sum = array_sum($w);
            $loc = [0.0, 0.0];
            foreach ($w as $i => $wi) {
                $loc[0] += $wi * $x[$i] / $sum;
                $loc[1] += $wi * $y[$i] / $sum;
            }
            $converged = true;
            foreach ($w as $i => $wi) {
                if (abs($wi - $w0[$i]) >= $tol) {
                    $converged = false;
                    break;
                }
            }
            if ($converged) {
                break;
            }
        }
        // The scatter uses the deviations from the location at the start of the last iteration, as cov.trob does.
        [$a, $b, $c] = [0.0, 0.0, 0.0];
        foreach ($centered as $i => [$dx, $dy]) {
            [$a, $b, $c] = [$a + $w[$i] * $dx * $dx, $b + $w[$i] * $dx * $dy, $c + $w[$i] * $dy * $dy];
        }

        return [$loc, [[$a / $n, $b / $n], [$b / $n, $c / $n]]];
    }

    /** @return list<float> */
    private static function seq(float $from, float $to, int $n): array
    {
        return array_map(fn ($i) => $from + ($to - $from) * $i / ($n - 1), range(0, $n - 1));
    }
}

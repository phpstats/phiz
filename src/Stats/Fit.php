<?php

declare(strict_types=1);

namespace Phiz\Stats;

use InvalidArgumentException;

/**
 * The small amount of numerics phiz needs to draw statistics Vega-Lite can't: least-squares fits with confidence
 * bands (smooth with se), quantiles of the normal and t distributions, and R's bw.nrd0 bandwidth.
 */
final class Fit
{
    /**
     * Least squares of y on the columns of x (include a column of ones for an intercept).
     *
     * @param list<list<float>> $x
     * @param list<float> $y
     * @return array{coefficients: list<float>, covariance: list<list<float>>, std_errors: list<float>, fitted: list<float>, residuals: list<float>, df_residual: int, sigma: float}
     */
    public static function ols(array $x, array $y): array
    {
        $n = count($y);
        $p = count($x[0] ?? []);
        if ($n !== count($x) || $p === 0) {
            throw new InvalidArgumentException('ols needs a non-empty design with one row per response.');
        }
        if ($n <= $p) {
            throw new InvalidArgumentException("ols needs more observations ({$n}) than coefficients ({$p}).");
        }
        $xtx = array_fill(0, $p, array_fill(0, $p, 0.0));
        $xty = array_fill(0, $p, 0.0);
        foreach ($x as $i => $row) {
            for ($a = 0; $a < $p; $a++) {
                $xty[$a] += $row[$a] * $y[$i];
                for ($b = 0; $b <= $a; $b++) {
                    $xtx[$a][$b] += $row[$a] * $row[$b];
                }
            }
        }
        for ($a = 0; $a < $p; $a++) {
            for ($b = $a + 1; $b < $p; $b++) {
                $xtx[$a][$b] = $xtx[$b][$a];
            }
        }
        $inverse = self::invert($xtx);
        $beta = array_map(fn (array $row) => self::dot($row, $xty), $inverse);
        $fitted = array_map(fn (array $row) => self::dot($row, $beta), $x);
        $residuals = array_map(fn ($yi, $fi) => $yi - $fi, $y, $fitted);
        $df = $n - $p;
        $sigma2 = array_sum(array_map(fn ($r) => $r * $r, $residuals)) / $df;
        $covariance = array_map(fn (array $row) => array_map(fn ($v) => $v * $sigma2, $row), $inverse);

        return [
            'coefficients' => $beta,
            'covariance' => $covariance,
            'std_errors' => array_map(fn ($i) => sqrt(max(0.0, $covariance[$i][$i])), range(0, $p - 1)),
            'fitted' => $fitted,
            'residuals' => $residuals,
            'df_residual' => $df,
            'sigma' => sqrt($sigma2),
        ];
    }

    /**
     * Fits of y on x per group, evaluated on n points across each group's x range with a pointwise confidence band
     * for the mean, as ggplot2's geom_smooth(se = TRUE): polynomials of the given degree (method = "lm"), or loess
     * with the given span (a local quadratic, as R's loess() with surface = "direct").
     *
     * @param list<array<string, mixed>> $rows
     * @param list<string> $groups
     * @return list<array<string, mixed>> Rows with x, y, __ymin, __ymax, and the group fields.
     */
    public static function smooth(array $rows, string $x, string $y, array $groups, int $degree = 1, float $level = 0.95, int $n = 80, ?float $span = null): array
    {
        if (! ($level > 0 && $level < 1)) {
            throw new InvalidArgumentException('level must be between 0 and 1.');
        }
        $sets = [];
        foreach ($rows as $row) {
            $xi = $row[$x] ?? null;
            $yi = $row[$y] ?? null;
            if (! is_numeric($xi) || ! is_numeric($yi) || ! is_finite((float) $xi) || ! is_finite((float) $yi)) {
                continue;
            }
            $key = [];
            foreach ($groups as $group) {
                $key[$group] = $row[$group] ?? null;
            }
            $id = json_encode($key);
            $sets[$id] ??= ['key' => $key, 'x' => [], 'y' => []];
            $sets[$id]['x'][] = (float) $xi;
            $sets[$id]['y'][] = (float) $yi;
        }

        $out = [];
        foreach ($sets as $set) {
            $count = count($set['x']);
            if ($span !== null) {
                [$lo, $hi] = [min($set['x']), max($set['x'])];
                $grid = array_map(fn ($i) => $lo + ($hi - $lo) * $i / ($n - 1), range(0, $n - 1));
                try {
                    $fit = $hi > $lo ? self::loess($set['x'], $set['y'], $grid, $span) : null;
                } catch (InvalidArgumentException) {
                    $fit = null; // Too few points for the span; ggplot2 drops such groups with a warning.
                }
                if ($fit === null || ! ($fit['df'] > 0) || ! is_finite($fit['sigma'])) {
                    continue;
                }
                $t = self::tQuantile((1 + $level) / 2, $fit['df']);
                foreach ($grid as $i => $xi) {
                    [$pred, $se] = [$fit['fit'][$i], $fit['se'][$i]];
                    $out[] = $set['key'] + [$x => $xi, $y => $pred, '__ymin' => $pred - $t * $se, '__ymax' => $pred + $t * $se];
                }
                continue;
            }
            if ($count <= $degree + 1 || count(array_unique($set['x'])) <= $degree) {
                continue; // Too few points to fit and estimate error, as ggplot2 drops such groups.
            }
            $mean = array_sum($set['x']) / $count;
            $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $set['x'])) / ($count - 1));
            if (! ($sd > 0)) {
                continue;
            }
            $design = fn (float $v) => array_map(fn ($k) => (($v - $mean) / $sd) ** $k, range(0, $degree));
            try {
                $fit = self::ols(array_map($design, $set['x']), $set['y']);
            } catch (InvalidArgumentException) {
                continue; // A singular design in one group leaves the others' fits intact.
            }
            $t = self::tQuantile((1 + $level) / 2, $fit['df_residual']);
            [$lo, $hi] = [min($set['x']), max($set['x'])];
            for ($i = 0; $i < $n; $i++) {
                $xi = $lo + ($hi - $lo) * $i / ($n - 1);
                $d = $design($xi);
                $pred = self::dot($d, $fit['coefficients']);
                $se = sqrt(max(0.0, self::dot($d, array_map(fn (array $row) => self::dot($row, $d), $fit['covariance']))));
                $out[] = $set['key'] + [$x => $xi, $y => $pred, '__ymin' => $pred - $t * $se, '__ymax' => $pred + $t * $se];
            }
        }

        return $out;
    }

    /**
     * Local polynomial regression of y on x, as R's loess(y ~ x, span, degree, surface = "direct"): at each point a
     * weighted least-squares fit over the nearest span·n observations with tricube weights. Returns the fit and its
     * standard error at each of $at, and the residual degrees of freedom R's predict(se = TRUE) uses for intervals.
     * The error statistics are exact (R's statistics = "exact") up to 500 observations; above that the lookup
     * degrees of freedom use δ₂ ≈ δ₁, which changes the t quantile by well under 1%.
     *
     * @param list<float> $x
     * @param list<float> $y
     * @param list<float> $at
     * @return array{fit: list<float>, se: list<float>, df: float, sigma: float, trace: float}
     */
    public static function loess(array $x, array $y, array $at, float $span = 0.75, int $degree = 2): array
    {
        $n = count($x);
        if ($n !== count($y) || $n === 0) {
            throw new InvalidArgumentException('loess needs x and y of the same, non-zero length.');
        }
        if (! in_array($degree, [0, 1, 2], true) || ! ($span > 0)) {
            throw new InvalidArgumentException('loess needs degree 0, 1, or 2 and a positive span.');
        }
        array_multisort($x, $y);
        $q = (int) floor($n * $span);
        if ($q < $degree + 1) {
            throw new InvalidArgumentException("loess span {$span} is too small for {$n} points; it needs at least ".($degree + 1).' neighbors.');
        }
        $q = min($q, $n);

        // The operator row of each observation, for the residual scale and the error degrees of freedom.
        $rows = [];
        $rss = 0.0;
        $trace = 0.0;
        $frobenius = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $l = self::loessRow($x, $x[$i], $q, $span, $degree);
            $fit = 0.0;
            foreach ($l as $j => $w) {
                $fit += $w * $y[$j];
                $frobenius += $w * $w;
            }
            $trace += $l[$i] ?? 0.0;
            $rss += ($y[$i] - $fit) ** 2;
            $rows[] = $l;
        }
        $delta1 = $n - 2 * $trace + $frobenius;
        $delta2 = $n <= 500 ? self::loessDelta2($rows, $n) : $delta1;
        $sigma = sqrt($rss / $delta1);

        $fits = [];
        $ses = [];
        foreach ($at as $x0) {
            $l = self::loessRow($x, (float) $x0, $q, $span, $degree);
            $fit = 0.0;
            $norm = 0.0;
            foreach ($l as $j => $w) {
                $fit += $w * $y[$j];
                $norm += $w * $w;
            }
            $fits[] = $fit;
            $ses[] = $sigma * sqrt($norm);
        }

        return ['fit' => $fits, 'se' => $ses, 'df' => $delta1 * $delta1 / $delta2, 'sigma' => $sigma, 'trace' => $trace];
    }

    /**
     * The weights l with fit(x0) = Σ l_j y_j: the intercept row of the local weighted least-squares solution.
     *
     * @param list<float> $x Sorted.
     * @return array<int, float> Sparse, keyed by observation.
     */
    private static function loessRow(array $x, float $x0, int $q, float $span, int $degree): array
    {
        $n = count($x);
        // The q nearest neighbors are a window of the sorted x: grow it from the insertion point.
        [$lo, $hi] = [0, $n];
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            $x[$mid] < $x0 ? $lo = $mid + 1 : $hi = $mid;
        }
        [$left, $right] = [$lo - 1, $lo];
        for ($k = 0; $k < $q; $k++) {
            if ($right >= $n || ($left >= 0 && $x0 - $x[$left] <= $x[$right] - $x0)) {
                $left--;
            } else {
                $right++;
            }
        }
        // Above a span of 1, R widens the farthest neighbor's distance by √span (it scales squared distances).
        $rho = max($x0 - $x[$left + 1], $x[$right - 1] - $x0) * sqrt(max(1.0, $span));
        if (! ($rho > 0)) {
            throw new InvalidArgumentException('loess span is too small: the neighborhood of a point has zero width.');
        }
        // Points outside the window are at least rho away, so their tricube weight is zero.
        $p = $degree + 1;
        $xtwx = array_fill(0, $p, array_fill(0, $p, 0.0));
        $terms = [];
        for ($j = $left + 1; $j < $right; $j++) {
            $d = abs($x[$j] - $x0) / $rho;
            if ($d >= 1) {
                continue;
            }
            $w = (1 - $d * $d * $d) ** 3;
            $u = ($x[$j] - $x0) / $rho;
            $basis = $degree === 0 ? [1.0] : ($degree === 1 ? [1.0, $u] : [1.0, $u, $u * $u]);
            for ($a = 0; $a < $p; $a++) {
                for ($b = 0; $b < $p; $b++) {
                    $xtwx[$a][$b] += $w * $basis[$a] * $basis[$b];
                }
            }
            $terms[$j] = [$w, $basis];
        }
        $first = self::invert($xtwx)[0];
        $l = [];
        foreach ($terms as $j => [$w, $basis]) {
            $l[$j] = $w * self::dot($first, $basis);
        }

        return $l;
    }

    /** δ₂ = tr(((I − L)ᵀ(I − L))²), from the sparse operator rows. */
    private static function loessDelta2(array $rows, int $n): float
    {
        // A = I − L, column by column (A[k][i] is row k, column i).
        $columns = array_fill(0, $n, []);
        foreach ($rows as $k => $l) {
            foreach ($l as $i => $w) {
                $columns[$i][$k] = -$w;
            }
            $columns[$k][$k] = ($columns[$k][$k] ?? 0.0) + 1.0;
        }
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i; $j < $n; $j++) {
                $m = 0.0;
                $ci = $columns[$i];
                foreach ($columns[$j] as $k => $v) {
                    if (isset($ci[$k])) {
                        $m += $ci[$k] * $v;
                    }
                }
                $sum += $i === $j ? $m * $m : 2 * $m * $m;
            }
        }

        return $sum;
    }

    /** Standard normal quantile (Acklam's approximation with one Halley refinement; about 1e-15 relative error). */
    public static function normalQuantile(float $p): float
    {
        if ($p <= 0 || $p >= 1) {
            return $p == 0 ? -INF : ($p == 1 ? INF : NAN);
        }
        $a = [-3.969683028665376e+01, 2.209460984245205e+02, -2.759285104469687e+02, 1.383577518672690e+02, -3.066479806614716e+01, 2.506628277459239e+00];
        $b = [-5.447609879822406e+01, 1.615858368580409e+02, -1.556989798598866e+02, 6.680131188771972e+01, -1.328068155288572e+01];
        $c = [-7.784894002430293e-03, -3.223964580411365e-01, -2.400758277161838e+00, -2.549732539343734e+00, 4.374664141464968e+00, 2.938163982698783e+00];
        $d = [7.784695709041462e-03, 3.224671290700398e-01, 2.445134137142996e+00, 3.754408661907416e+00];
        $low = 0.02425;
        if ($p < $low) {
            $q = sqrt(-2 * log($p));
            $x = ((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1);
        } elseif ($p <= 1 - $low) {
            $q = $p - 0.5;
            $r = $q * $q;
            $x = ((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q / ((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1);
        } else {
            $q = sqrt(-2 * log(1 - $p));
            $x = -((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1);
        }
        $e = 0.5 * self::erfc(-$x / M_SQRT2) - $p;
        $u = $e * sqrt(2 * M_PI) * exp($x * $x / 2);

        return $x - $u / (1 + $x * $u / 2);
    }

    /** Student t quantile with df degrees of freedom, by Newton steps on the t CDF from a normal start. */
    public static function tQuantile(float $p, float $df): float
    {
        if (! ($df > 0)) {
            throw new InvalidArgumentException('df must be positive.');
        }
        if ($p <= 0 || $p >= 1) {
            return $p == 0 ? -INF : ($p == 1 ? INF : NAN);
        }
        if ($df > 1e7) {
            return self::normalQuantile($p);
        }
        if ($p < 0.5) {
            return -self::tQuantile(1 - $p, $df);
        }
        // Cornish-Fisher start, then Newton on the CDF.
        $z = self::normalQuantile($p);
        $t = $z + ($z ** 3 + $z) / (4 * $df) + (5 * $z ** 5 + 16 * $z ** 3 + 3 * $z) / (96 * $df ** 2);
        if ($df < 3 || ! is_finite($t)) {
            $t = max($t, 1.0);
        }
        for ($i = 0; $i < 100; $i++) {
            $f = self::tCdf($t, $df) - $p;
            $density = exp(self::lgamma(($df + 1) / 2) - self::lgamma($df / 2)) / sqrt($df * M_PI) * (1 + $t * $t / $df) ** (-($df + 1) / 2);
            $step = $f / $density;
            if (! is_finite($step)) {
                break;
            }
            $next = $t - $step;
            if ($next <= 0) {
                $next = $t / 2;
            }
            if (abs($next - $t) < 1e-13 * max(1.0, abs($t))) {
                return $next;
            }
            $t = $next;
        }

        return $t;
    }

    public static function normalCdf(float $x): float
    {
        return 0.5 * self::erfc(-$x / M_SQRT2);
    }

    public static function tCdf(float $t, float $df): float
    {
        $x = $df / ($df + $t * $t);
        $tail = 0.5 * self::betaRegularized($df / 2, 0.5, $x);

        return $t > 0 ? 1 - $tail : $tail;
    }

    /**
     * Gaussian kernel density of $values at n points evenly spaced over [from, to], as R's density(). Up to two
     * million kernel evaluations are exact; beyond that the values are linearly binned onto 2048 points first, as
     * density() does.
     *
     * @return list<array{0: float, 1: float}> [x, density] pairs.
     */
    public static function kde(array $values, float $bw, float $from, float $to, int $n = 512): array
    {
        $values = array_values(array_filter($values, fn ($v) => (is_int($v) || is_float($v)) && is_finite((float) $v)));
        $count = count($values);
        if ($count === 0 || ! ($bw > 0) || $n < 2) {
            throw new InvalidArgumentException('kde needs values, a positive bandwidth, and n >= 2.');
        }
        $weights = array_fill(0, $count, 1.0);
        if ($count * $n > 2_000_000) {
            // Linear binning: each value splits its weight between the two nearest grid points.
            $m = 2048;
            [$lo, $hi] = [min(min($values), $from) - 4 * $bw, max(max($values), $to) + 4 * $bw];
            $step = ($hi - $lo) / ($m - 1);
            $grid = array_fill(0, $m, 0.0);
            foreach ($values as $v) {
                $pos = ($v - $lo) / $step;
                $i = min($m - 2, (int) floor($pos));
                $f = $pos - $i;
                $grid[$i] += 1 - $f;
                $grid[$i + 1] += $f;
            }
            $values = [];
            $weights = [];
            foreach ($grid as $i => $w) {
                if ($w > 0) {
                    $values[] = $lo + $i * $step;
                    $weights[] = $w;
                }
            }
        }
        $norm = 1 / ($count * $bw * sqrt(2 * M_PI));
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $x = $from + ($to - $from) * $i / ($n - 1);
            $sum = 0.0;
            foreach ($values as $j => $v) {
                $u = ($x - $v) / $bw;
                if ($u > -8 && $u < 8) {
                    $sum += $weights[$j] * exp(-0.5 * $u * $u);
                }
            }
            $out[] = [$x, $sum * $norm];
        }

        return $out;
    }

    /** R's bw.nrd0: 0.9 * min(sd, IQR / 1.34) * n^-0.2. */
    public static function bandwidth(array $values): float
    {
        $values = array_map('floatval', array_values($values));
        $n = count($values);
        if ($n < 2) {
            throw new InvalidArgumentException('Need at least 2 values for a bandwidth.');
        }
        sort($values);
        $mean = array_sum($values) / $n;
        $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / ($n - 1));
        $iqr = self::quantile($values, 0.75) - self::quantile($values, 0.25);
        $lo = min($sd, $iqr / 1.34);
        if (! ($lo > 0)) {
            $lo = $sd ?: abs($values[0]) ?: 1.0;
        }

        return 0.9 * $lo * $n ** -0.2;
    }

    /** R's type 7 quantile of sorted values. */
    public static function quantile(array $sorted, float $p): float
    {
        $h = (count($sorted) - 1) * $p;
        $lo = (int) floor($h);
        $hi = min($lo + 1, count($sorted) - 1);

        return $sorted[$lo] + ($h - $lo) * ($sorted[$hi] - $sorted[$lo]);
    }

    private static function betaRegularized(float $a, float $b, float $x): float
    {
        if ($x <= 0) {
            return 0.0;
        }
        if ($x >= 1) {
            return 1.0;
        }
        $front = exp(self::lgamma($a + $b) - self::lgamma($a) - self::lgamma($b) + $a * log($x) + $b * log(1 - $x));
        if ($x > ($a + 1) / ($a + $b + 2)) {
            return 1 - $front * self::betaFraction($b, $a, 1 - $x) / $b;
        }

        return $front * self::betaFraction($a, $b, $x) / $a;
    }

    /** Lentz's continued fraction for the incomplete beta function. */
    private static function betaFraction(float $a, float $b, float $x): float
    {
        $tiny = 1e-300;
        $c = 1.0;
        $d = 1 - ($a + $b) * $x / ($a + 1);
        $d = 1 / (abs($d) < $tiny ? $tiny : $d);
        $h = $d;
        for ($m = 1; $m <= 300; $m++) {
            $m2 = 2 * $m;
            $aa = $m * ($b - $m) * $x / (($a + $m2 - 1) * ($a + $m2));
            $d = 1 + $aa * $d;
            $d = 1 / (abs($d) < $tiny ? $tiny : $d);
            $c = 1 + $aa / $c;
            $c = abs($c) < $tiny ? $tiny : $c;
            $h *= $d * $c;
            $aa = -($a + $m) * ($a + $b + $m) * $x / (($a + $m2) * ($a + $m2 + 1));
            $d = 1 + $aa * $d;
            $d = 1 / (abs($d) < $tiny ? $tiny : $d);
            $c = 1 + $aa / $c;
            $c = abs($c) < $tiny ? $tiny : $c;
            $delta = $d * $c;
            $h *= $delta;
            if (abs($delta - 1) < 1e-15) {
                break;
            }
        }

        return $h;
    }

    /** Lanczos approximation of ln Γ(x) for x > 0. */
    private static function lgamma(float $x): float
    {
        $g = [0.99999999999980993, 676.5203681218851, -1259.1392167224028, 771.32342877765313, -176.61502916214059, 12.507343278686905, -0.13857109526572012, 9.9843695780195716e-6, 1.5056327351493116e-7];
        if ($x < 0.5) {
            return log(M_PI / abs(sin(M_PI * $x))) - self::lgamma(1 - $x);
        }
        $x -= 1;
        $a = $g[0];
        $t = $x + 7.5;
        for ($i = 1; $i < 9; $i++) {
            $a += $g[$i] / ($x + $i);
        }

        return 0.5 * log(2 * M_PI) + ($x + 0.5) * log($t) - $t + log($a);
    }

    /** Complementary error function (Numerical Recipes erfcc refined; about 1e-16 relative error near 0, adequate here). */
    private static function erfc(float $x): float
    {
        $z = abs($x);
        $t = 2 / (2 + $z);
        $ty = 4 * $t - 2;
        $cof = [-1.3026537197817094, 6.4196979235649026e-1, 1.9476473204185836e-2, -9.561514786808631e-3, -9.46595344482036e-4, 3.66839497852761e-4, 4.2523324806907e-5, -2.0278578112534e-5, -1.624290004647e-6, 1.303655835580e-6, 1.5626441722e-8, -8.5238095915e-8, 6.529054439e-9, 5.059343495e-9, -9.91364156e-10, -2.27365122e-10, 9.6467911e-11, 2.394038e-12, -6.886027e-12, 8.94487e-13, 3.13092e-13, -1.12708e-13, 3.81e-16, 7.106e-15, -1.523e-15, -9.4e-17, 1.21e-16, -2.8e-17];
        $d = 0.0;
        $dd = 0.0;
        for ($j = count($cof) - 1; $j > 0; $j--) {
            $tmp = $d;
            $d = $ty * $d - $dd + $cof[$j];
            $dd = $tmp;
        }
        $result = $t * exp(-$z * $z + 0.5 * ($cof[0] + $ty * $d) - $dd);

        return $x >= 0 ? $result : 2 - $result;
    }

    /** Gauss-Jordan inverse with partial pivoting. */
    private static function invert(array $m): array
    {
        $n = count($m);
        $inv = [];
        for ($i = 0; $i < $n; $i++) {
            $inv[$i] = array_fill(0, $n, 0.0);
            $inv[$i][$i] = 1.0;
        }
        for ($col = 0; $col < $n; $col++) {
            $pivot = $col;
            for ($row = $col + 1; $row < $n; $row++) {
                if (abs($m[$row][$col]) > abs($m[$pivot][$col])) {
                    $pivot = $row;
                }
            }
            if (abs($m[$pivot][$col]) < 1e-12 * max(1.0, abs($m[$col][$col]))) {
                throw new InvalidArgumentException('The design matrix is singular.');
            }
            [$m[$col], $m[$pivot]] = [$m[$pivot], $m[$col]];
            [$inv[$col], $inv[$pivot]] = [$inv[$pivot], $inv[$col]];
            $scale = $m[$col][$col];
            for ($k = 0; $k < $n; $k++) {
                $m[$col][$k] /= $scale;
                $inv[$col][$k] /= $scale;
            }
            for ($row = 0; $row < $n; $row++) {
                if ($row !== $col && $m[$row][$col] != 0) {
                    $factor = $m[$row][$col];
                    for ($k = 0; $k < $n; $k++) {
                        $m[$row][$k] -= $factor * $m[$col][$k];
                        $inv[$row][$k] -= $factor * $inv[$col][$k];
                    }
                }
            }
        }

        return $inv;
    }

    private static function dot(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $v) {
            $sum += $v * $b[$i];
        }

        return $sum;
    }
}

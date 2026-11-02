# Clean-room specifications: pretty() and the multivariate t fit

These are the specifications from which `Contour::pretty()` and `Contour::multivariateT()` were implemented, by an implementer who had not seen R's or MASS's source or the earlier routines. They were written from R's documentation of `pretty()`, Kent, Tyler & Vardi (1994), MASS's documentation of `cov.trob()`, and those functions' observed output.


Implement both functions in plain PHP 8.3 in the file `implementation.php` in this folder. Use your own structure,
names, and comments. Write them only from this specification; do not look at any other code for them.

```php
<?php

declare(strict_types=1);

final class CleanRoom
{
    /** @return list<float> */
    public static function pretty(float $lo, float $hi, int $n = 10): array { ... }

    /** @return array{0: array{0: float, 1: float}, 1: array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}} */
    public static function multivariateT(array $x, array $y, float $nu = 5, int $steps = 25, float $tolerance = 0.01): array { ... }
}
```

## 1. pretty(lo, hi, n)

Returns about n + 1 equally spaced "round" values covering [lo, hi] (lo <= hi). The step is 1, 2, or 5 times a power
of ten. The behavior below was established from R's documentation of `pretty()` and from R's output.

Definitions:

- `fewest` = n integer-divided by 3 (the minimum number of intervals).
- `size` = max(|lo|, |hi|). `eps` = PHP_FLOAT_EPSILON.

Rules:

1. The range counts as a single value ("flat") when hi == lo, or when hi − lo < size · eps · (4.25 · max(1, n) + 0.25).
2. The cell c:
   - flat: (size, or 1 if size is 0) · 0.75 / max(1, fewest);
   - otherwise: (hi − lo) / max(1, n).
   Then c = max(c, 2^−20 · PHP_FLOAT_MIN).
3. The base b is the power of ten with b <= c < 10 b. Compute it robustly; log10 can round at exact powers of ten.
4. The unit u, from r = c / b:
   - r <= 1.4: u = b;
   - r <= 2.8: u = 2 b;
   - r <= 7: u = 5 b;
   - otherwise: u = 10 b.
   The boundaries are inclusive: r exactly 1.4, 2.8, or 7 takes the smaller unit.
5. Integers first and last:
   - first = floor(lo / u) and last = ceil(hi / u);
   - a quotient within 1e-7 of an integer counts as that integer (so lo = 3u gives first = 3, not 2).
6. While last − first < fewest, widen by one unit at a time, alternating between the ends:
   - the first step goes toward zero: upward (last + 1) when lo + hi < 0, otherwise downward (first − 1);
   - then the other end, and so on.
7. Return k · u for every integer k from first to last, each rounded to as many decimals as u has. u is m · 10^p with m
   in {1, 2, 5, 10}, so that is max(0, −p) decimals; derive p robustly. 0.005 · 3 must come out as exactly 0.015.

Examples, from R:

| lo | hi | n | result |
| --- | --- | --- | --- |
| 1 | 15 | 5 | 0, 2, 4, 6, 8, 10, 12, 14, 16 |
| 1 | 15 | 10 | 1, 2, 3, …, 15 |
| 43 | 96 | 5 | 40, 50, 60, 70, 80, 90, 100 |
| 0.000123 | 0.0371 | 10 | 0, 0.005, 0.01, …, 0.04 (step 0.005) |
| −3.2 | 7.9 | 10 | −4, −3, …, 8 |
| 3 | 197 | 5 | 0, 50, 100, 150, 200 |
| 0 | 0 | 5 | −1, 0 |
| 0 | 0 | 10 | −0.4, −0.2, 0, 0.2 |
| 5 | 5 | 5 | 0, 5 |
| 5 | 5 | 10 | 3, 4, 5, 6 |
| −2 | −2 | 5 | −2, 0 |
| −2 | −2 | 10 | −2.5, −2, −1.5, −1 |

## 2. multivariateT(x, y, nu, steps, tolerance)

Fits the location and scatter of a bivariate t distribution with `nu` degrees of freedom to the points (x[i], y[i]),
by the EM algorithm of Kent, Tyler & Vardi (1994), "A curious likelihood identity for the multivariate
t-distribution". The step order and outputs below were established from the reference implementation's observed
output.

Algorithm:

1. Start with center = (mean of x, mean of y) and every weight w[i] = 1.
2. Repeat at most `steps` times:
   1. Remember the current center as the step's starting center.
   2. Scatter S: the weighted mean of r r^T over the points, Σ w[i] r_i r_i^T / Σ w[i], where r_i is point i minus
      the current center. If det S <= 0, throw InvalidArgumentException with the message
      'stat_ellipse needs points that are not all on a line.'
   3. New weights: w[i] = (nu + 2) / (nu + d_i²), where d_i² = r_i^T S^−1 r_i, computed with the center and S from
      this step. Keep the previous weights for step 5.
   4. New center: Σ w[i] · point_i / Σ w[i], with the new weights.
   5. Stop if every weight changed by less than `tolerance` (absolute) from its previous value.
3. Return:
   - the center after the last step;
   - the covariance Σ w[i] r_i r_i^T / n, using the last weights and r_i measured from that last step's starting
     center (not the new center), divided by n, the number of points (not the weights' sum).

Output: `[[cx, cy], [[sxx, sxy], [sxy, syy]]]`.

Example: Old Faithful (272 points; x = eruption length, y = waiting time). The reference stops after the 4th pass of
step 2 and returns center (3.5844837884397203, 71.87665004628009) and covariance
[[1.1917480258334427, 12.763774274971921], [12.763774274971921, 164.53655568742863]], to about 1e-13.

# Saved model fits

These are phit's fits as arrays (what `toArray()` returns on a `Phit\Fit` from phit 0.6), saved as JSON so the
documentation examples and the statistics tests run without the phit extension. `tests/Spec/StatsTest.php` refits each one with `phpstats/phit-polyfill` 0.4.1 and checks the
coefficients and covariance matrix to 1e-9.

| File | phit call | Data |
| --- | --- | --- |
| `mtcars-lm.json` | `fit_ols('mpg ~ wt + hp + am', $data)` | `mtcars` |
| `mtcars-lm-reduced.json` | `fit_ols('mpg ~ wt + hp', $data)` | `mtcars` |
| `mtcars-lm-factors.json` | `fit_ols('mpg ~ factor(cyl) + factor(am) + wt', $data)` | `mtcars` |
| `mtcars-glm.json` | `fit_generalized_linear('am ~ wt + hp', $data, 'binomial')` | `mtcars` |
| `mtcars-glm-factor.json` | `fit_generalized_linear('am ~ factor(cyl) + wt', $data, 'binomial')` | `mtcars` |
| `mpg-lmer-slopes.json` | `fit_mixed_gaussian('hwy ~ displ + (1 + displ \| manufacturer)', $data)` | `mpg` |
| `eight-schools-draws.json` | CmdStanPHP `draws_columns()` | the eight schools model |

`mpg-lmer-slopes.json` is the fit from polyfill 0.4.1. Polyfill 0.4 factors a Gaussian mixed model's random-effect
blocks separately, as phit 0.4.3 does. The changed order of floating-point operations moves the REML optimum on a
nearly flat surface: against the 0.3 fit, the coefficients, covariance, and theta differ by at most 2.2e-7 and the
conditional modes by at most 5.2e-6, with the same log-likelihood, optimizer, and 93 evaluations.

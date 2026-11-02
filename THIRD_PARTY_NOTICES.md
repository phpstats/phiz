# Third-party notices

phiz is released under the MIT license in [LICENSE](LICENSE). Some of its code follows the behavior of the projects
below, which keep their own licenses.

## Code

### ggplot2

- Version: 4.0.3
- URL: <https://ggplot2.tidyverse.org>
- License: MIT
- Copyright: Copyright (c) 2025 ggplot2 core developer team
- In phiz: `Contour::breaks()` in `src/Stats/Contour.php` chooses contour breaks as ggplot2's `contour_breaks()` does.

### scales

- Version: 1.4.0
- URL: <https://scales.r-lib.org>
- License: MIT
- Copyright: Copyright (c) 2025 scales authors
- In phiz: `Contour::fullseq()` in `src/Stats/Contour.php` follows `fullseq()`, and `Palette::hue()` in
  `src/Palette.php` follows `hue_pal()`.

### MIT license text

The license text below applies to ggplot2 and scales, with the copyright lines above.

```
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit
persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```

## Independently written routines

These routines reproduce the results of R and its packages, but were written from published methods, documentation,
and observed output, not from those projects' source code. `scripts/evidence/parity.php` and the verification records
compare them with R.

| phiz | Matches | Written from |
| --- | --- | --- |
| `Contour::pretty()` | R's `pretty()` | R's documentation of `pretty()`, and R's output on random and edge-case ranges |
| `Contour::multivariateT()`, behind `stat_ellipse(type: 't')` | MASS's `cov.trob()` | Kent, Tyler & Vardi (1994); MASS's documented defaults; and its output |
| `Fit::loess()` | R's `loess(surface = "direct")` | Cleveland & Grosse's local regression method and its error statistics |
| `Fit::normalQuantile()` | R's `qnorm()` | Acklam's published approximation, with a Halley step |
| `Fit::tQuantile()` | R's `qt()` | Newton steps on the t CDF, with a continued fraction and a Lanczos ln Γ |
| `Contour::kde2d()`, `Contour::bandwidthNrd()` | MASS's `kde2d()` and `bandwidth.nrd()` | the documented kernel-density and bandwidth formulas |
| `Fit::bandwidth()`, `Fit::quantile()` | R's `bw.nrd0()` and type 7 quantiles | the documented formulas (Hyndman & Fan, 1996) |
| `Palette::grey()`, `Palette::hcl()` | R's `grey.colors()` and `hcl()` | the documented gamma 2.2 rule, and the CIE-LUV and sRGB formulas |
| `Contour::lines()`, `Contour::region()` | ggplot2's contours | the marching squares algorithm |

## Data

The bundled datasets keep their own licenses:

| Data | Source | License |
| --- | --- | --- |
| `resources/data/mpg.csv`, `resources/data/economics.csv` | ggplot2 4.0.3 (fueleconomy.gov and FRED data) | MIT |
| `resources/data/mtcars.csv`, `resources/data/faithful.csv` | R's datasets package | GPL-2 or GPL-3, as part of R |
| `examples/data/gapminder.csv` | the gapminder R package 1.0.1, via github.com/phpstats/datasets | CC0 |

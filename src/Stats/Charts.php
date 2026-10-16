<?php

declare(strict_types=1);

namespace Phiz\Stats;

use InvalidArgumentException;
use Phiz\Compose;
use Phiz\Data;
use Phiz\Plot;

use function Phiz\aes;

/**
 * Plots of fitted models and posterior draws, built from ordinary Plot layers so they can be restyled and extended.
 *
 * Fits are the arrays php-stats returns: stats_lm, stats_glm, stats_lmer and stats_glmer (which name their
 * coefficients in 'columns'), or stats_ols and stats_logistic (pass names:). Each needs 'coefficients', and
 * 'std_errors' or 'conf_int'; the residual plots need 'fitted' and 'residuals'.
 */
final class Charts
{
    /**
     * Coefficient (forest) plot: estimates with confidence intervals and a reference line at no effect.
     *
     * @param array $fits One fit, or several keyed by label to compare side by side.
     * @param list<string>|null $names Coefficient names when the fit has no 'columns'.
     * @param bool $intercept Whether to include the intercept.
     * @param float|null $level Interval level; null uses the fit's conf_int (95%) when present.
     * @param bool $exponentiate Show exp(estimate), as odds or rate ratios, on a log scale with the reference at 1.
     * @param list<string>|null $terms Only these terms, in this order.
     */
    public static function coefficients(array $fits, ?array $names = null, bool $intercept = false, ?float $level = null, bool $exponentiate = false, ?array $terms = null, bool $sort = false): Plot
    {
        $labelled = ! isset($fits['coefficients']);
        $fits = $labelled ? $fits : ['model' => $fits];
        if ($fits === []) {
            throw new InvalidArgumentException('coefplot needs a fit.');
        }
        $rows = [];
        foreach ($fits as $label => $fit) {
            foreach (self::terms($fit, $names, $level) as $row) {
                if (! $intercept && $row['term'] === '(Intercept)') {
                    continue;
                }
                if ($terms !== null && ! in_array($row['term'], $terms, true)) {
                    continue;
                }
                if ($exponentiate) {
                    foreach (['estimate', 'lower', 'upper'] as $key) {
                        $row[$key] = exp($row[$key]);
                    }
                }
                $rows[] = $row + ($labelled ? ['model' => (string) $label] : []);
            }
        }
        if ($rows === []) {
            throw new InvalidArgumentException('No coefficients to plot; set intercept: true or check terms:.');
        }

        $order = $terms ?? array_values(array_unique(array_column($rows, 'term')));
        if ($sort) {
            $means = [];
            foreach ($rows as $row) {
                $means[$row['term']][] = $row['estimate'];
            }
            usort($order, fn ($a, $b) => array_sum($means[$b]) / count($means[$b]) <=> array_sum($means[$a]) / count($means[$a]));
        }
        $percent = rtrim(rtrim(sprintf('%.1f', 100 * ($level ?? 0.95)), '0'), '.');
        $what = $exponentiate ? 'exp(estimate)' : 'Estimate';

        $plot = Plot::of($rows)
            ->aes(x: 'estimate', y: \Phiz\field('term', 'nominal', sort: $order), xmin: 'lower', xmax: 'upper', color: $labelled ? 'model' : null)
            ->vline($exponentiate ? 1 : 0, linetype: 'dashed', color: '#888888')
            ->pointrange(position: $labelled && count($fits) > 1 ? 'dodge' : null)
            ->labs(x: "{$what} ({$percent}% CI)", y: null)
            ->tooltip(['term', ...($labelled ? ['model'] : []), 'estimate', 'lower', 'upper', 'std_error', 'p_value']);

        return $exponentiate ? $plot->scaleX(type: 'log') : $plot;
    }

    /** Residuals against fitted values, with a zero line and a loess trend (R's plot(fit, 1)). */
    public static function residuals(array $fit): Plot
    {
        return Plot::of(self::fitRows($fit))
            ->aes(x: 'fitted', y: 'residual')
            ->hline(0, linetype: 'dashed', color: '#888888')
            ->point(alpha: 0.6)
            ->smooth(method: 'loess', se: false, color: '#D62728', linewidth: 1.5)
            ->labs(title: 'Residuals vs fitted', x: 'Fitted values', y: 'Residuals');
    }

    /** Normal QQ plot of a fit's residuals (or any list of values), with the line through the quartiles. */
    public static function qq(array $fitOrValues, bool $standardize = false): Plot
    {
        if (isset($fitOrValues['residuals'])) {
            $values = $fitOrValues['residuals'];
            $label = 'Residuals';
        } elseif (array_is_list($fitOrValues)) {
            $values = $fitOrValues;
            $label = 'Sample';
        } else {
            throw new InvalidArgumentException('qq_plot needs a fit with residuals or a list of values.');
        }
        $values = array_values(array_filter($values, fn ($v) => is_numeric($v) && is_finite((float) $v)));
        if ($standardize && count($values) > 1) {
            $mean = array_sum($values) / count($values);
            $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / (count($values) - 1));
            $values = array_map(fn ($v) => ($v - $mean) / $sd, $values);
            $label = 'Standardized '.strtolower($label);
        }

        return Plot::of(array_map(fn ($v) => ['sample' => $v], $values))
            ->aes(sample: 'sample')
            ->qqLine(color: '#888888', linetype: 'dashed')
            ->qq(alpha: 0.7)
            ->labs(title: 'Normal QQ', x: 'Theoretical quantiles', y: $label);
    }

    /** Observed against fitted values with the identity line. */
    public static function fitted(array $fit, ?array $observed = null): Plot
    {
        $rows = self::fitRows($fit, $observed);

        return Plot::of($rows)
            ->aes(x: 'fitted', y: 'observed')
            ->point(alpha: 0.6)
            ->abline(0, 1, linetype: 'dashed', color: '#888888')
            ->labs(title: 'Observed vs fitted', x: 'Fitted values', y: 'Observed');
    }

    /** sqrt(|residual / sigma|) against fitted values, to check for non-constant variance (R's plot(fit, 3)). */
    public static function scaleLocation(array $fit): Plot
    {
        $rows = self::fitRows($fit);
        $residuals = array_column($rows, 'residual');
        $sigma = $fit['sigma'] ?? sqrt(array_sum(array_map(fn ($r) => $r * $r, $residuals)) / max(1, ($fit['df_residual'] ?? count($residuals) - 1)));
        foreach ($rows as &$row) {
            $row['root_abs'] = sqrt(abs($row['residual'] / $sigma));
        }
        unset($row);

        return Plot::of($rows)
            ->aes(x: 'fitted', y: 'root_abs')
            ->point(alpha: 0.6)
            ->smooth(method: 'loess', se: false, color: '#D62728', linewidth: 1.5)
            ->labs(title: 'Scale-location', x: 'Fitted values', y: '√|standardized residual|');
    }

    /** The four standard diagnostic panels in a grid. */
    public static function diagnostics(array $fit, int $width = 260, int $height = 200): Compose
    {
        $panels = [self::residuals($fit), self::qq($fit), self::scaleLocation($fit), self::fitted($fit)];

        return Compose::grid(array_map(fn (Plot $p) => $p->size($width, $height), $panels), columns: 2);
    }

    /**
     * Predicted values with confidence bands over a grid: x varies across its observed range (n points), $by takes
     * each of its levels, and every other column is held at its mean (numbers) or most common value.
     *
     * Formula fits (stats_lm, stats_glm) build the grid's design with stats_model_matrix, so pass the data they
     * were fitted on; for matrix fits (stats_ols, stats_logistic) pass design: fn (array $row) => [1, $row['x'], ...].
     * GLM predictions are on the response scale, with the interval computed on the link scale.
     */
    public static function effects(array $fit, mixed $data, string $x, ?string $by = null, int $n = 50, float $level = 0.95, ?callable $design = null, bool $points = true): Plot
    {
        $rows = Data::from($data)->rows ?? [];
        if ($rows === []) {
            throw new InvalidArgumentException('effect_plot needs the data the model was fitted on.');
        }
        $grid = self::grid($rows, $x, $by, $n, isset($fit['response']) ? [$fit['response']] : []);
        $predicted = self::predictions($fit, $grid, $level, $design, $rows);
        $response = $fit['response'] ?? 'y';

        $plot = Plot::of($predicted)->aes(x: $x, y: 'estimate', color: $by === null ? null : \Phiz\factor($by));
        if ($points && isset($rows[0][$response])) {
            $plot = $plot->point(aes(x: $x, y: $response, color: $by === null ? null : \Phiz\factor($by)), data: $rows, inheritAes: false, alpha: 0.35);
        }

        return $plot
            ->geom('ribbon', aes(ymin: 'lower', ymax: 'upper', fill: $by === null ? null : \Phiz\factor($by)), style: ['alpha' => 0.2] + ($by === null ? ['fill' => '#3366FF'] : []), raw: $by === null ? [] : ['encoding' => ['color' => ['legend' => null]]])
            ->line(linewidth: 2, color: $by === null ? '#3366FF' : null)
            ->labs(y: $response, fill: $by, color: $by);
    }

    /**
     * Predictions for grid rows: each row plus estimate, lower, upper (and std_error on the link scale).
     *
     * @param list<array<string, mixed>> $grid
     * @param list<array<string, mixed>> $data The fitting data, so factor levels match (formula fits only).
     * @return list<array<string, mixed>>
     */
    public static function predictions(array $fit, array $grid, float $level = 0.95, ?callable $design = null, array $data = []): array
    {
        $beta = $fit['coefficients'] ?? throw new InvalidArgumentException('The fit has no coefficients.');
        $covariance = $fit['covariance'] ?? throw new InvalidArgumentException('Predictions need the fit\'s covariance matrix.');
        $matrix = $design !== null ? array_map(fn ($row) => array_values($design($row)), $grid) : self::design($fit, $grid, $data);
        $critical = isset($fit['t_statistics']) && isset($fit['df_residual'])
            ? Fit::tQuantile((1 + $level) / 2, (float) $fit['df_residual'])
            : Fit::normalQuantile((1 + $level) / 2);
        $inverse = self::inverseLink($fit['link'] ?? 'identity');

        $out = [];
        foreach ($grid as $i => $row) {
            $d = $matrix[$i];
            if (count($d) !== count($beta)) {
                throw new InvalidArgumentException('The design row has '.count($d).' columns; the fit has '.count($beta).' coefficients.');
            }
            $eta = 0.0;
            foreach ($d as $j => $v) {
                $eta += $v * $beta[$j];
            }
            $variance = 0.0;
            foreach ($d as $j => $vj) {
                foreach ($d as $k => $vk) {
                    $variance += $vj * $covariance[$j][$k] * $vk;
                }
            }
            $se = sqrt(max(0.0, $variance));
            $out[] = $row + ['estimate' => $inverse($eta), 'lower' => $inverse($eta - $critical * $se), 'upper' => $inverse($eta + $critical * $se), 'std_error' => $se];
        }

        return $out;
    }

    /**
     * Posterior intervals by variable, as bayesplot's mcmc_intervals: a thick inner interval, a thin outer one, and
     * the median (or mean). Takes draws as columns (CmdStanPHP's draws_columns(): variable => draws, plus .chain,
     * .iteration and .draw) or a summary (summary_columns(): 'variable' plus quantile columns such as q5 and q95).
     */
    public static function intervals(array $draws, ?array $variables = null, float $prob = 0.5, float $probOuter = 0.9, string $pointEst = 'median'): Plot
    {
        if (! ($prob > 0 && $prob <= $probOuter && $probOuter < 1)) {
            throw new InvalidArgumentException('Require 0 < prob <= probOuter < 1.');
        }
        if (! in_array($pointEst, ['median', 'mean'], true)) {
            throw new InvalidArgumentException("pointEst must be 'median' or 'mean'.");
        }
        $rows = isset($draws['variable']) ? self::summaryRows($draws, $variables, $pointEst) : self::drawRows($draws, $variables, $prob, $probOuter, $pointEst);
        $order = array_column($rows, 'variable');
        $inner = isset($rows[0]['l']);
        $percent = fn (float $p) => rtrim(rtrim(sprintf('%.1f', 100 * $p), '0'), '.').'%';

        $plot = Plot::of($rows)
            ->aes(y: \Phiz\field('variable', 'nominal', sort: $order))
            ->vline(0, color: '#CCCCCC')
            ->rule(aes(x: 'll', xend: 'hh'), linewidth: 1.5, color: '#6497B1');
        if ($inner) {
            $plot = $plot->rule(aes(x: 'l', xend: 'h'), linewidth: 5, color: '#03396C');
        }

        return $plot
            ->point(aes(x: 'm'), size: 70, fill: '#FFFFFF', stroke: '#03396C', strokeWidth: 1.5, filled: true)
            ->labs(x: 'value', y: null, subtitle: ($inner ? $percent($prob).' and ' : '').$percent($probOuter).' intervals, '.$pointEst)
            ->tooltip(['variable', 'm', 'll', 'hh']);
    }

    /** @return list<array<string, mixed>> */
    private static function drawRows(array $draws, ?array $variables, float $prob, float $probOuter, string $pointEst): array
    {
        $names = array_values(array_filter(array_keys($draws), fn ($name) => ! str_starts_with((string) $name, '.') && ! str_ends_with((string) $name, '__')));
        if ($variables !== null) {
            $names = array_values(array_filter($names, fn ($name) => in_array($name, $variables, true) || in_array(explode('[', (string) $name)[0], $variables, true)));
        }
        if ($names === []) {
            throw new InvalidArgumentException('No variables selected.');
        }
        $rows = [];
        foreach ($names as $name) {
            $values = array_map('floatval', array_values($draws[$name]));
            sort($values);
            $q = fn (float $p) => Fit::quantile($values, $p);
            $rows[] = [
                'variable' => (string) $name,
                'll' => $q((1 - $probOuter) / 2), 'l' => $q((1 - $prob) / 2),
                'm' => $pointEst === 'median' ? $q(0.5) : array_sum($values) / count($values),
                'h' => $q((1 + $prob) / 2), 'hh' => $q((1 + $probOuter) / 2),
            ];
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    private static function summaryRows(array $summary, ?array $variables, string $pointEst): array
    {
        $rows = Data::from($summary)->rows ?? [];
        $quantiles = array_values(array_filter(array_keys($rows[0] ?? []), fn ($k) => (bool) preg_match('/^q\d+(\.\d+)?$/', (string) $k)));
        usort($quantiles, fn ($a, $b) => (float) substr($a, 1) <=> (float) substr($b, 1));
        if (count($quantiles) < 2) {
            throw new InvalidArgumentException('A summary needs at least two quantile columns, such as q5 and q95.');
        }
        $lo = $quantiles[0];
        $hi = $quantiles[count($quantiles) - 1];
        $out = [];
        foreach ($rows as $row) {
            if ($variables !== null && ! in_array($row['variable'], $variables, true) && ! in_array(explode('[', (string) $row['variable'])[0], $variables, true)) {
                continue;
            }
            $out[] = ['variable' => (string) $row['variable'], 'll' => $row[$lo], 'm' => $row[$pointEst] ?? $row['mean'] ?? null, 'hh' => $row[$hi]];
        }

        return $out;
    }

    /** @return list<array{term: string, estimate: float, std_error: ?float, lower: float, upper: float, p_value: ?float}> */
    private static function terms(array $fit, ?array $names, ?float $level): array
    {
        $beta = array_values($fit['coefficients'] ?? throw new InvalidArgumentException('A fit needs coefficients.'));
        $names = $fit['columns'] ?? $names;
        if ($names === null) {
            $names = [];
            $hasIntercept = $fit['intercept'] ?? true;
            foreach ($beta as $i => $_) {
                $names[] = $hasIntercept && $i === 0 ? '(Intercept)' : 'x'.($hasIntercept ? $i : $i + 1);
            }
        } elseif (count($names) === count($beta) - 1 && ($fit['intercept'] ?? true)) {
            $names = ['(Intercept)', ...$names];
        }
        if (count($names) !== count($beta)) {
            throw new InvalidArgumentException('Got '.count($names).' names for '.count($beta).' coefficients.');
        }
        $se = $fit['std_errors'] ?? null;
        if ($level === null && isset($fit['conf_int'])) {
            $intervals = $fit['conf_int'];
        } else {
            if ($se === null) {
                throw new InvalidArgumentException('Intervals at a chosen level need std_errors.');
            }
            $level ??= 0.95;
            $critical = isset($fit['t_statistics'], $fit['df_residual']) ? Fit::tQuantile((1 + $level) / 2, (float) $fit['df_residual']) : Fit::normalQuantile((1 + $level) / 2);
            $intervals = array_map(fn ($b, $s) => [$b - $critical * $s, $b + $critical * $s], $beta, $se);
        }

        return array_map(fn ($i) => [
            'term' => (string) $names[$i],
            'estimate' => (float) $beta[$i],
            'std_error' => isset($se[$i]) ? (float) $se[$i] : null,
            'lower' => (float) $intervals[$i][0],
            'upper' => (float) $intervals[$i][1],
            'p_value' => isset($fit['p_values'][$i]) ? (float) $fit['p_values'][$i] : null,
        ], array_keys($beta));
    }

    /** @return list<array{fitted: float, residual: float, observed: float, row: int}> */
    private static function fitRows(array $fit, ?array $observed = null): array
    {
        if (! isset($fit['fitted'], $fit['residuals'])) {
            throw new InvalidArgumentException('The fit needs fitted and residuals.');
        }
        $rows = [];
        foreach (array_values($fit['fitted']) as $i => $f) {
            $r = $fit['residuals'][$i] ?? null;
            if (! is_numeric($f) || ! is_numeric($r)) {
                continue;
            }
            $rows[] = ['row' => (int) ($fit['retained_rows'][$i] ?? $i), 'fitted' => (float) $f, 'residual' => (float) $r, 'observed' => (float) ($observed[$i] ?? $f + $r)];
        }

        return $rows;
    }

    /** Grid rows: x across its range, $by at each level, other columns at a typical value. */
    private static function grid(array $rows, string $x, ?string $by, int $n, array $exclude): array
    {
        if (! array_key_exists($x, $rows[0])) {
            throw new InvalidArgumentException("The data has no column '{$x}'.");
        }
        $typical = [];
        foreach (array_keys($rows[0]) as $column) {
            if ($column === $x || $column === $by || in_array($column, $exclude, true)) {
                continue;
            }
            $values = array_values(array_filter(array_column($rows, $column), fn ($v) => $v !== null));
            if ($values === []) {
                continue;
            }
            $numeric = array_filter($values, is_numeric(...)) === $values && ! is_bool($values[0]);
            if ($numeric && count(array_unique($values)) > 2) {
                $typical[$column] = array_sum($values) / count($values);
            } else {
                $counts = array_count_values(array_map(fn ($v) => is_bool($v) ? (int) $v : $v, $values));
                arsort($counts);
                $typical[$column] = array_key_first($counts);
            }
        }
        $xs = array_values(array_filter(array_column($rows, $x), is_numeric(...)));
        [$lo, $hi] = [min($xs), max($xs)];
        $levels = $by === null ? [null] : array_values(array_unique(array_column($rows, $by)));
        sort($levels);
        $grid = [];
        foreach ($levels as $level) {
            for ($i = 0; $i < $n; $i++) {
                $row = $typical;
                $row[$x] = $lo + ($hi - $lo) * $i / max(1, $n - 1);
                if ($by !== null) {
                    $row[$by] = $level;
                }
                $grid[] = $row;
            }
        }

        return $grid;
    }

    /** The design matrix for grid rows from the fit's formula, built alongside the fitting data so factor levels match. */
    private static function design(array $fit, array $grid, array $data): array
    {
        if (! isset($fit['formula']) || ! function_exists('stats_model_matrix')) {
            throw new InvalidArgumentException('Predictions for this fit need design: fn (array $row) => [...] (formula fits need the php_stats extension).');
        }
        if ($data === []) {
            throw new InvalidArgumentException('Pass the fitting data so factor levels in the grid match the fit.');
        }
        $rhs = '~ '.trim(explode('~', (string) $fit['formula'], 2)[1] ?? '');
        $rhs = preg_replace('/\s*\+\s*\(([^()]|\([^()]*\))*\|([^()]|\([^()]*\))*\)/', '', $rhs); // Drop random-effect terms.
        $combined = [];
        $columns = array_keys($grid[0]);
        foreach ([...$data, ...$grid] as $row) {
            foreach ($columns as $column) {
                $combined[$column][] = $row[$column] ?? null;
            }
        }
        $matrix = stats_model_matrix($rhs, $combined);
        if (($matrix['columns'] ?? null) !== ($fit['columns'] ?? null)) {
            throw new InvalidArgumentException('The grid design has columns '.json_encode($matrix['columns'] ?? null).', not the fit\'s '.json_encode($fit['columns'] ?? null).'.');
        }

        return array_slice($matrix['x'], count($data));
    }

    private static function inverseLink(string $link): \Closure
    {
        return match ($link) {
            'identity', '' => fn (float $eta) => $eta,
            'logit' => fn (float $eta) => 1 / (1 + exp(-$eta)),
            'probit' => fn (float $eta) => Fit::normalCdf($eta),
            'cloglog' => fn (float $eta) => 1 - exp(-exp($eta)),
            'log' => fn (float $eta) => exp($eta),
            'inverse' => fn (float $eta) => 1 / $eta,
            'sqrt' => fn (float $eta) => $eta * $eta,
            default => throw new InvalidArgumentException("Unknown link '{$link}'."),
        };
    }
}

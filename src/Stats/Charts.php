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
 * Fits are the arrays phit returns: formula fits from Phit\fit_ols, fit_generalized_linear, fit_mixed_gaussian, and
 * fit_mixed_logistic name their coefficients in 'columns'; matrix fits from fit_ols_matrix and fit_logistic_matrix
 * need names:. Each needs 'coefficients', and
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
        $link = reset($fits)['link'] ?? 'identity';
        $what = match (true) {
            $exponentiate && $link === 'logit' => 'Odds ratio',
            $exponentiate && $link === 'log' => 'Rate ratio',
            $exponentiate => 'exp(estimate)',
            $link === 'logit' => 'Log odds',
            $link === 'log' => 'Log rate',
            default => 'Estimate',
        };

        $plot = Plot::of($rows)
            ->aes(x: 'estimate', y: \Phiz\field('term', 'nominal', sort: $order), xmin: 'lower', xmax: 'upper', color: $labelled ? 'model' : null)
            ->vline($exponentiate ? 1 : 0, linetype: 'dashed', color: '#888888')
            ->pointrange(position: $labelled && count($fits) > 1 ? 'dodge' : null)
            ->labs(x: "{$what} ({$percent}% CI)", y: null)
            ->height(max(120, count($order) * ($labelled && count($fits) > 1 ? 18 * count($fits) + 12 : 36)))
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
            ->labs(title: 'Scale-location', x: 'Fitted values', y: '√|residual / σ|');
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
     * Formula fits (fit_ols, fit_generalized_linear) build the grid's design with Phit\model_matrix, so pass the data
     * they were fitted on; for matrix fits (fit_ols_matrix, fit_logistic_matrix) pass design: fn (array $row) =>
     * [1, $row['x'], ...].
     * GLM predictions are on the response scale, with the interval computed on the link scale.
     */
    public static function effects(array $fit, mixed $data, string $x, ?string $by = null, int $n = 50, float $level = 0.95, ?callable $design = null, bool $points = true, string $scale = 'response'): Plot
    {
        $rows = Data::from($data)->rows ?? [];
        if ($rows === []) {
            throw new InvalidArgumentException('effect_plot needs the data the model was fitted on.');
        }
        $grid = self::grid($rows, $x, $by, $n, isset($fit['response']) ? [$fit['response']] : []);
        $predicted = self::predictions($fit, $grid, $level, $design, $rows, $scale);
        $response = $fit['response'] ?? 'y';
        $link = $fit['link'] ?? 'identity';
        $onLink = $scale === 'link' && ! in_array($link, ['identity', ''], true);

        $plot = Plot::of($predicted)->aes(x: $x, y: 'estimate', color: $by === null ? null : \Phiz\factor($by));
        if ($points && ! $onLink && isset($rows[0][$response])) {
            $plot = $plot->point(aes(x: $x, y: $response, color: $by === null ? null : \Phiz\factor($by)), data: $rows, inheritAes: false, alpha: 0.35);
        }

        return $plot
            ->geom('ribbon', aes(ymin: 'lower', ymax: 'upper', fill: $by === null ? null : \Phiz\factor($by)), style: ['alpha' => 0.2] + ($by === null ? ['fill' => '#3366FF'] : []))
            ->line(linewidth: 2, color: $by === null ? '#3366FF' : null)
            ->labs(y: $onLink ? "{$response} ({$link} scale)" : $response, fill: $by, color: $by);
    }

    /**
     * Predictions for grid rows: each row plus estimate, lower, upper (and std_error on the link scale).
     *
     * @param list<array<string, mixed>> $grid
     * @param list<array<string, mixed>> $data The fitting data, so factor levels match (formula fits only).
     * @return list<array<string, mixed>>
     */
    public static function predictions(array $fit, array $grid, float $level = 0.95, ?callable $design = null, array $data = [], string $scale = 'response'): array
    {
        $matrix = $design !== null ? array_map(fn ($row) => array_values($design($row)), $grid) : self::design($fit, $grid, $data);

        return self::linear($fit, $grid, $matrix, $level, $scale);
    }

    /**
     * Estimated marginal means of a factor (as emmeans): for each level, the prediction averaged with equal weight
     * over the levels of the model's other categorical variables, with numeric variables at their means. The
     * interval comes from the averaged design row, on the link scale, then transformed to the response scale.
     *
     * @return Plot Points with intervals; ->data->rows holds the means.
     */
    public static function marginalMeans(array $fit, mixed $data, string $factor, ?string $by = null, float $level = 0.95, ?callable $design = null, string $scale = 'response', ?array $variables = null): Plot
    {
        $rows = Data::from($data)->rows ?? [];
        if ($rows === [] || ! array_key_exists($factor, $rows[0])) {
            throw new InvalidArgumentException("marginal_means needs the fitting data with a '{$factor}' column.");
        }
        if (isset($fit['retained_rows']) && count($fit['retained_rows']) < count($rows)) {
            // Means and levels come from the rows the model used, as emmeans takes them from the fitted data.
            $rows = array_values(array_intersect_key($rows, array_flip($fit['retained_rows'])));
        }
        $variables ??= self::modelVariables($fit, $rows);
        $categorical = [];
        $fixed = [];
        foreach ($variables as $variable => $kind) {
            if ($variable === $factor || $variable === $by || $variable === ($fit['response'] ?? null)) {
                continue;
            }
            $values = array_values(array_filter(array_column($rows, $variable), fn ($v) => $v !== null));
            if ($kind === 'categorical') {
                $categorical[$variable] = self::sortedLevels($values);
            } elseif ($values !== []) {
                $fixed[$variable] = array_sum($values) / count($values);
            }
        }

        // The reference grid: every combination of the other categorical variables' levels.
        $combinations = [[]];
        foreach ($categorical as $variable => $levels) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($levels as $level_) {
                    $next[] = $combination + [$variable => $level_];
                }
            }
            $combinations = $next;
        }
        $cells = [];
        foreach (self::sortedLevels(array_column($rows, $factor)) as $value) {
            foreach ($by === null ? [null] : self::sortedLevels(array_column($rows, $by)) as $group) {
                $cells[] = [$factor => $value] + ($by === null ? [] : [$by => $group]);
            }
        }
        $grid = [];
        foreach ($cells as $cell) {
            foreach ($combinations as $combination) {
                $grid[] = $cell + $combination + $fixed;
            }
        }
        $matrix = $design !== null ? array_map(fn ($row) => array_values($design($row)), $grid) : self::design($fit, $grid, $rows);

        $averaged = [];
        $per = count($combinations);
        foreach ($cells as $c => $cell) {
            $sum = array_fill(0, count($matrix[0]), 0.0);
            for ($k = 0; $k < $per; $k++) {
                foreach ($matrix[$c * $per + $k] as $j => $v) {
                    $sum[$j] += $v / $per;
                }
            }
            $averaged[] = $sum;
        }
        $means = self::linear($fit, $cells, $averaged, $level, $scale);
        $response = $fit['response'] ?? 'y';
        $link = $fit['link'] ?? 'identity';
        $percent = rtrim(rtrim(sprintf('%.1f', 100 * $level), '0'), '.');

        return Plot::of($means)
            ->aes(x: \Phiz\factor($factor, self::sortedLevels(array_column($rows, $factor))), y: 'estimate', ymin: 'lower', ymax: 'upper', color: $by === null ? null : \Phiz\factor($by))
            ->pointrange(position: $by === null ? null : 'dodge')
            ->labs(x: $factor, y: ($scale === 'link' && $link !== 'identity' ? "{$response} ({$link} scale)" : $response)." (marginal mean, {$percent}% CI)", color: $by)
            ->tooltip([$factor, ...($by === null ? [] : [$by]), 'estimate', 'lower', 'upper']);
    }

    /**
     * A caterpillar plot of a mixed model's random effects (fit_mixed_gaussian, fit_mixed_logistic): each group's
     * conditional mode with an interval from its conditional variance, one panel per effect, groups sorted by the
     * first effect.
     * The conditional covariance is (Z_j'W_j Z_j / sigma^2 + G^-1)^-1 for group j, treating the fixed effects and
     * variance components as known, as lme4's ranef(condVar = TRUE) does; W holds GLMM working weights.
     */
    public static function randomEffects(array $fit, mixed $data = null, ?string $group = null, float $level = 0.95): Plot
    {
        $terms = $fit['random'] ?? throw new InvalidArgumentException('random_effects_plot needs a mixed-model fit (fit_mixed_gaussian or fit_mixed_logistic).');
        $term = null;
        foreach ($terms as $candidate) {
            if ($group === null || ($candidate['group'] ?? null) === $group) {
                $term = $candidate;
                break;
            }
        }
        if ($term === null) {
            throw new InvalidArgumentException("The fit has no random term for group '{$group}'.");
        }
        $columns = $term['columns'];
        $labels = $term['group_labels'] ?? $fit['group_labels'] ?? throw new InvalidArgumentException('The random term has no group labels.');
        $effects = $term['effects'] ?? array_map(fn ($e) => [$e], $fit['random_effects'] ?? []);
        $G = $term['covariance'] ?? (isset($fit['random_intercept_variance']) ? [[(float) $fit['random_intercept_variance']]] : throw new InvalidArgumentException('The random term has no covariance.'));
        $singular = (bool) ($fit['singular'] ?? $fit['diagnostics']['singular'] ?? false);
        // phit reports conditional variances (lme4's condVar): a k x k matrix per group on each term, or one
        // variance per group for single-term GLMMs. Fits without them fall back to rebuilding them from the data.
        $reported = $term['effects_cond_var'] ?? (count($columns) === 1 && isset($fit['random_effects_cond_var']) ? array_map(fn ($v) => [[(float) $v]], $fit['random_effects_cond_var']) : null);
        $out = $reported !== null
            ? self::effectRows($labels, $columns, $effects, $reported, $level)
            : self::rebuiltEffects($fit, $term, $data, $labels, $columns, $effects, $G, $level);
        $first = array_values(array_filter($out, fn ($r) => $r['effect'] === $columns[0]));
        usort($first, fn ($a, $b) => $a['estimate'] <=> $b['estimate']);
        $order = array_column($first, 'group');
        $percent = rtrim(rtrim(sprintf('%.1f', 100 * $level), '0'), '.');

        $plot = Plot::of($out)
            ->aes(x: 'estimate', y: \Phiz\field('group', 'nominal', sort: array_reverse($order)), xmin: 'lower', xmax: 'upper')
            ->vline(0, linetype: 'dashed', color: '#888888')
            ->pointrange(size: 30)
            ->labs(x: "Conditional mode ({$percent}% interval)", y: $term['group'], subtitle: $singular ? self::singularNote($G) : null)
            ->tooltip(['group', 'effect', 'estimate', 'lower', 'upper']);

        return count($columns) > 1 ? $plot->facetWrap('effect', ncol: count($columns), scales: 'free_x')->size(220, max(120, 16 * count($labels))) : $plot->height(max(120, 16 * count($labels)));
    }

    /** Rows of conditional modes with intervals from per-group conditional covariance matrices. */
    private static function effectRows(array $labels, array $columns, array $effects, array $covariances, float $level): array
    {
        $z = Fit::normalQuantile((1 + $level) / 2);
        $out = [];
        foreach ($labels as $g => $label) {
            foreach ($columns as $a => $column) {
                $estimate = (float) ($effects[$g][$a] ?? 0.0);
                $se = sqrt(max(0.0, (float) ($covariances[$g][$a][$a] ?? 0.0)));
                $out[] = ['group' => (string) $label, 'effect' => $column, 'estimate' => $estimate, 'std_error' => $se, 'lower' => $estimate - $z * $se, 'upper' => $estimate + $z * $se];
            }
        }

        return $out;
    }

    /** Conditional covariances rebuilt from the fitting data, for fits that do not report them. */
    private static function rebuiltEffects(array $fit, array $term, mixed $data, array $labels, array $columns, array $effects, array $G, float $level): array
    {
        $rows = Data::from($data)->rows ?? [];
        if ($rows === []) {
            throw new InvalidArgumentException('This fit does not report conditional variances; pass the data the model was fitted on.');
        }
        $sigma2 = (float) ($fit['residual_variance'] ?? 1.0);
        $retained = array_map(fn ($i) => $rows[$i] ?? throw new InvalidArgumentException('The data does not match the fit\'s retained rows; pass the data the model was fitted on.'), $fit['retained_rows'] ?? array_keys($rows));
        $Z = self::randomDesign($term, $retained);
        $weights = self::workingWeights($fit, count($retained));

        $byGroup = [];
        foreach ($retained as $i => $row) {
            $byGroup[(string) $row[$term['group']]][] = $i;
        }
        $k = count($columns);
        $covariances = [];
        foreach ($labels as $g => $label) {
            // (Z'WZ / sigma^2 + G^-1)^-1 = (I + G Z'WZ / sigma^2)^-1 G, which needs no inverse of G, so it holds for
            // singular fits (a zero variance or a correlation of +-1) too.
            $A = array_fill(0, $k, array_fill(0, $k, 0.0));
            foreach ($byGroup[(string) $label] ?? [] as $i) {
                for ($a = 0; $a < $k; $a++) {
                    for ($b = 0; $b < $k; $b++) {
                        $A[$a][$b] += $weights[$i] * $Z[$i][$a] * $Z[$i][$b] / $sigma2;
                    }
                }
            }
            $M = [];
            for ($a = 0; $a < $k; $a++) {
                for ($b = 0; $b < $k; $b++) {
                    $sum = $a === $b ? 1.0 : 0.0;
                    for ($c = 0; $c < $k; $c++) {
                        $sum += $G[$a][$c] * $A[$c][$b];
                    }
                    $M[$a][$b] = $sum;
                }
            }
            $Minv = self::invert($M);
            $C = [];
            for ($a = 0; $a < $k; $a++) {
                for ($b = 0; $b < $k; $b++) {
                    $C[$a][$b] = 0.0;
                    for ($c = 0; $c < $k; $c++) {
                        $C[$a][$b] += $Minv[$a][$c] * $G[$c][$b];
                    }
                }
            }
            $covariances[$g] = $C;
        }

        return self::effectRows($labels, $columns, $effects, $covariances, $level);
    }

    /** Why a mixed-model fit is singular: a variance at zero, or random effects perfectly correlated. */
    private static function singularNote(array $G): string
    {
        foreach ($G as $a => $row) {
            if ($row[$a] <= 1e-10 * max(1.0, max(array_map('abs', array_merge(...$G))))) {
                return 'Singular fit: a random-effect variance is estimated at zero';
            }
        }

        return 'Singular fit: random effects are perfectly correlated';
    }

    /** Predictions from design rows: estimate, interval, and std_error (on the link scale) per grid row. */
    private static function linear(array $fit, array $grid, array $matrix, float $level, string $scale): array
    {
        if (! in_array($scale, ['response', 'link'], true)) {
            throw new InvalidArgumentException("scale must be 'response' or 'link'.");
        }
        $beta = $fit['coefficients'] ?? throw new InvalidArgumentException('The fit has no coefficients.');
        $covariance = $fit['covariance'] ?? throw new InvalidArgumentException('Predictions need the fit\'s covariance matrix.');
        $critical = isset($fit['t_statistics']) && isset($fit['df_residual'])
            ? Fit::tQuantile((1 + $level) / 2, (float) $fit['df_residual'])
            : Fit::normalQuantile((1 + $level) / 2);
        $inverse = $scale === 'link' ? fn (float $eta) => $eta : self::inverseLink($fit['link'] ?? 'identity');

        $out = [];
        foreach (array_values($grid) as $i => $row) {
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
            // Decreasing links (inverse) swap the transformed bounds.
            [$a, $b] = [$inverse($eta - $critical * $se), $inverse($eta + $critical * $se)];
            $out[] = $row + ['estimate' => $inverse($eta), 'lower' => min($a, $b), 'upper' => max($a, $b), 'std_error' => $se];
        }

        return $out;
    }

    /**
     * The data variables a formula fit uses, as name => 'numeric' or 'categorical': names inside factor() and
     * non-numeric columns are categorical.
     *
     * @return array<string, string>
     */
    private static function modelVariables(array $fit, array $rows): array
    {
        $formula = (string) ($fit['formula'] ?? '');
        if ($formula === '') {
            throw new InvalidArgumentException('Pass variables: [name => numeric|categorical] for a fit without a formula.');
        }
        $rhs = explode('~', $formula, 2)[1] ?? '';
        $rhs = (string) preg_replace('/\(([^()]|\([^()]*\))*\|([^()]|\([^()]*\))*\)/', '', $rhs);
        $variables = [];
        preg_match_all('/(factor|as\.factor|C|ordered)\(\s*([A-Za-z_.][A-Za-z0-9_.]*)/', $rhs, $factors);
        foreach ($factors[2] as $name) {
            $variables[$name] = 'categorical';
        }
        preg_match_all('/\b[A-Za-z_.][A-Za-z0-9_.]*\b(?!\s*\()/', $rhs, $names);
        foreach ($names[0] as $name) {
            if (isset($variables[$name]) || ! array_key_exists($name, $rows[0])) {
                continue;
            }
            $values = array_filter(array_column($rows, $name), fn ($v) => $v !== null);
            $variables[$name] = array_filter($values, fn ($v) => ! is_int($v) && ! is_float($v)) === [] ? 'numeric' : 'categorical';
        }

        return $variables;
    }

    private static function sortedLevels(array $values): array
    {
        $levels = array_values(array_unique(array_filter($values, fn ($v) => $v !== null), SORT_REGULAR));
        sort($levels);

        return $levels;
    }

    /** The random term's design columns for each retained row. */
    private static function randomDesign(array $term, array $rows): array
    {
        $lhs = trim(explode('|', (string) ($term['label'] ?? '1 |'), 2)[0]);
        if (function_exists('Phit\model_matrix') && $lhs !== '' && $lhs !== '1') {
            $columns = [];
            foreach ($rows as $row) {
                foreach ($row as $key => $value) {
                    $columns[$key][] = $value;
                }
            }
            $matrix = \Phit\model_matrix('~ '.$lhs, $columns);
            if (($matrix['columns'] ?? null) === $term['columns']) {
                return $matrix['x'];
            }
        }

        return array_map(function (array $row) use ($term) {
            return array_map(function (string $column) use ($row) {
                if ($column === '(Intercept)') {
                    return 1.0;
                }
                if (! is_numeric($row[$column] ?? null)) {
                    throw new InvalidArgumentException("Cannot build the random-effect column '{$column}' without phit or phit-polyfill.");
                }

                return (float) $row[$column];
            }, $term['columns']);
        }, $rows);
    }

    /** GLMM working weights (dmu/deta)^2 / Var(mu) at the fitted means; ones for Gaussian fits. */
    private static function workingWeights(array $fit, int $n): array
    {
        $link = $fit['link'] ?? 'identity';
        $family = $fit['family'] ?? 'gaussian';
        if ($family === 'gaussian' && in_array($link, ['identity', ''], true)) {
            return array_fill(0, $n, 1.0);
        }
        $mu = array_map('floatval', $fit['fitted'] ?? throw new InvalidArgumentException('GLMM weights need fitted means.'));

        return array_map(fn (float $m) => match ([$family, $link]) {
            ['binomial', 'logit'] => $m * (1 - $m),
            ['poisson', 'log'] => $m,
            default => throw new InvalidArgumentException("Conditional variances are implemented for gaussian, binomial (logit), and poisson (log) fits, not {$family} ({$link})."),
        }, $mu);
    }

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
            if (abs($m[$pivot][$col]) < 1e-300) {
                throw new InvalidArgumentException('The random-effect covariance is singular.');
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
        if (isset($draws['variable'])) {
            $probOuter = $rows[0]['level'] ?? $probOuter; // A summary's outer interval is set by its quantile columns.
        }
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
            ->height(max(120, 30 * count($rows)))
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
            $out[] = ['variable' => (string) $row['variable'], 'll' => $row[$lo], 'm' => $row[$pointEst] ?? $row['mean'] ?? null, 'hh' => $row[$hi], 'level' => ((float) substr($hi, 1) - (float) substr($lo, 1)) / 100];
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
        if (! isset($fit['formula']) || ! function_exists('Phit\model_matrix')) {
            throw new InvalidArgumentException('Predictions for this fit need design: fn (array $row) => [...] (formula fits need phit or phit-polyfill).');
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
        $matrix = \Phit\model_matrix($rhs, $combined);
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

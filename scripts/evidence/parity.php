<?php

declare(strict_types=1);

/*
 * The agreement-with-R checks behind the verification records: phiz's values on the inputs of
 * tests/fixtures/r-references.json (from scripts/evidence/r-references.R), compared case by case. evidence_parity()
 * returns the records without their run environment; scripts/evidence-parity.php writes them, and
 * tests/Spec/EvidenceParityTest.php checks that every case passes.
 */

use Phiz\Datasets;
use Phiz\Palette;
use Phiz\Plot;
use Phiz\Stats\Contour;
use Phiz\Stats\Fit;

use function Phiz\{marginal_means, predictions, random_effects_plot};

/** The comparison rules: tolerance rules pass when |x - r| <= atol + rtol * |r|. */
function evidence_rules(): array
{
    return [
        'tight' => ['id' => 'tight', 'applies_to' => 'Numerical results; fits, quantiles, intervals, and polygon points', 'kind' => 'tolerance', 'atol' => 1e-9, 'rtol' => 1e-9, 'rel_floor' => 1e-12],
        'grid' => ['id' => 'grid', 'applies_to' => 'Density grid; tails reach 1e-30', 'kind' => 'tolerance', 'atol' => 1e-12, 'rtol' => 1e-9, 'rel_floor' => 1e-12],
        'shape' => ['id' => 'shape', 'applies_to' => 'Relative density heights', 'kind' => 'tolerance', 'atol' => 1e-3, 'rel_floor' => 1e-12],
        'mixed' => ['id' => 'mixed', 'applies_to' => 'Mixed-model conditional modes and standard deviations; dependent on the REML optimum', 'kind' => 'tolerance', 'atol' => 1e-6, 'rtol' => 1e-6, 'rel_floor' => 1e-12],
        'exact' => ['id' => 'exact', 'applies_to' => 'Counts, levels, piece sizes, and colors', 'kind' => 'exact'],
    ];
}

/** One case: phiz's values against the reference's under a rule. */
function evidence_case(string $name, string $quantity, mixed $actual, mixed $expected, string $rule): array
{
    $actual = is_array($actual) ? array_values($actual) : [$actual];
    $expected = is_array($expected) ? array_values($expected) : [$expected];
    $case = ['name' => $name, 'quantity' => $quantity, 'rule' => $rule, 'n_compared' => count($expected)];
    if (count($actual) !== count($expected)) {
        return $case + ['status' => 'fail', 'reason' => sprintf('phiz returned %d values, the reference %d.', count($actual), count($expected))];
    }
    $spec = evidence_rules()[$rule];
    if ($spec['kind'] === 'exact') {
        $same = $actual === $expected || array_map(strval(...), $actual) === array_map(strval(...), $expected);
        $case['status'] = $same ? 'pass' : 'fail';
        if (! $same) {
            $case['reason'] = 'The values differ.';
        }
    } else {
        [$abs, $rel, $pass] = [0.0, 0.0, true];
        foreach ($expected as $i => $r) {
            $error = abs((float) $actual[$i] - (float) $r);
            $abs = max($abs, $error);
            $rel = max($rel, $error / max(abs((float) $r), $spec['rel_floor']));
            $pass = $pass && $error <= ($spec['atol'] ?? 0) + ($spec['rtol'] ?? 0) * abs((float) $r);
        }
        $case += ['status' => $pass ? 'pass' : 'fail', 'max_abs_error' => $abs, 'max_rel_error' => $rel];
        if (! $pass) {
            $case['reason'] = 'At least one value is outside the tolerance.';
        }
    }
    if (count($expected) === 1 && ! is_string($expected[0])) {
        $case += ['actual' => $actual[0], 'expected' => $expected[0]];
    }

    return $case;
}

/** A dataset as columns, for phit. */
function evidence_columns(array $rows): array
{
    $columns = [];
    foreach ($rows as $row) {
        foreach ($row as $key => $value) {
            $columns[$key][] = $value;
        }
    }

    return $columns;
}

/** The rules a record's cases use, in the order of evidence_rules(). */
function evidence_used_rules(array $cases): array
{
    $used = array_unique(array_column($cases, 'rule'));

    return array_values(array_filter(evidence_rules(), fn ($rule) => in_array($rule['id'], $used, true)));
}

/** @return array<string, array<string, mixed>> Records by id, without environment. */
function evidence_parity(array $fixture): array
{
    $ref = $fixture['references'];
    $versions = $fixture['versions'];
    $mtcars = Datasets::mtcars();
    $mpg = Datasets::mpg();
    $faithful = Datasets::faithful();
    $records = [];

    // Smoothing.
    $cases = [];
    $fit = Fit::ols(array_map(fn ($r) => [1, $r['wt']], $mtcars), array_column($mtcars, 'mpg'));
    $cases[] = evidence_case('mtcars, mpg ~ wt', 'lm coefficients', $fit['coefficients'], $ref['lm']['coefficients'], 'tight');
    $cases[] = evidence_case('mtcars, mpg ~ wt', 'lm standard errors', $fit['std_errors'], $ref['lm']['std_errors'], 'tight');
    $cases[] = evidence_case('mtcars, mpg ~ wt', 'residual degrees of freedom', $fit['df_residual'], $ref['lm']['df_residual'], 'exact');
    $band = Fit::smooth($mtcars, 'wt', 'mpg', [], degree: 1, level: 0.9, n: 2);
    $cases[] = evidence_case('mtcars, mpg ~ wt', '90% band at the ends of wt: fit, lower, upper', [...array_column($band, 'mpg'), ...array_column($band, '__ymin'), ...array_column($band, '__ymax')], [...$ref['lm']['band']['fit'], ...$ref['lm']['band']['lower'], ...$ref['lm']['band']['upper']], 'tight');
    [$x, $y] = [array_map(floatval(...), array_column($mpg, 'displ')), array_map(floatval(...), array_column($mpg, 'hwy'))];
    foreach ($ref['loess'] as $l) {
        $name = sprintf('mpg, hwy ~ displ, loess span %s degree %d', $l['span'], $l['degree']);
        $fit = Fit::loess($x, $y, $l['at'], span: (float) $l['span'], degree: $l['degree']);
        $cases[] = evidence_case($name, 'fit at 5 points', $fit['fit'], $l['fit'], 'tight');
        $cases[] = evidence_case($name, 'standard errors at 5 points', $fit['se'], $l['se'], 'tight');
        $cases[] = evidence_case($name, 'degrees of freedom of the t interval', $fit['df'], $l['df'], 'tight');
        $cases[] = evidence_case($name, 'residual standard error', $fit['sigma'], $l['sigma'], 'tight');
        if ((float) $l['span'] === 0.75 && $l['degree'] === 2) {
            $band = Fit::smooth($mpg, 'displ', 'hwy', [], n: 2, span: 0.75);
            $cases[] = evidence_case($name, '95% band at the ends of displ: lower, upper', [...array_column($band, '__ymin'), ...array_column($band, '__ymax')], [...$l['band']['lower'], ...$l['band']['upper']], 'tight');
        }
    }
    $records['r-smoothing'] = [
        'title' => 'Smoothing',
        'label' => 'Smoothing',
        'checked' => 'Least-squares coefficients, standard errors, and confidence bands; loess fits, standard errors, degrees of freedom, residual standard errors, and confidence bands. Identical inputs and evaluation points across implementations.',
        'description' => 'scripts/evidence/r-references.R computes the reference values in R and writes tests/fixtures/r-references.json. scripts/evidence/parity.php computes the same quantities with phiz\'s PHP implementations behind smooth() and compares them value by value.',
        'reference' => "R {$versions['R']}: lm(), predict(), loess(surface = \"direct\", statistics = \"exact\")",
        'versions' => ['R' => $versions['R']],
        'inputs' => [['name' => 'mtcars', 'detail' => '32 rows, R\'s datasets package'], ['name' => 'mpg', 'detail' => '234 rows, ggplot2 '.$versions['ggplot2']]],
        'comparison' => evidence_used_rules($cases),
        'cases' => $cases,
        'known_differences' => ['ggplot2\'s geom_smooth() interpolates the loess surface; phiz computes it directly, as loess(surface = "direct") does. ggplot2\'s curve can therefore differ slightly.'],
    ];

    // Distributions and contours.
    $cases = [];
    $q = $ref['quantiles'];
    $cases[] = evidence_case('fixed probabilities', 'qnorm() at 3 probabilities', array_map(Fit::normalQuantile(...), $q['qnorm']['p']), $q['qnorm']['value'], 'tight');
    $cases[] = evidence_case('fixed probabilities', 'qt() at 4 probabilities and degrees of freedom', array_map(fn ($p, $df) => Fit::tQuantile((float) $p, (float) $df), $q['qt']['p'], $q['qt']['df']), $q['qt']['value'], 'tight');
    $cases[] = evidence_case('fixed probabilities', 'pnorm()', Fit::normalCdf((float) $q['pnorm']['q']), $q['pnorm']['value'], 'tight');
    $wt = array_map(floatval(...), array_column($mtcars, 'wt'));
    sort($wt);
    $cases[] = evidence_case('mtcars wt', 'type 7 quantiles at 0.05, 0.25, 0.9', array_map(fn ($p) => Fit::quantile($wt, (float) $p), $ref['sample_quantiles']['p']), $ref['sample_quantiles']['value'], 'tight');
    $cases[] = evidence_case('mtcars wt', 'bw.nrd0 bandwidth', Fit::bandwidth($wt), $ref['sample_quantiles']['bw_nrd0'], 'tight');
    [$ex, $wy] = [array_map(floatval(...), array_column($faithful, 'eruptions')), array_map(floatval(...), array_column($faithful, 'waiting'))];
    $grid = Contour::kde2d($ex, $wy, [Contour::bandwidthNrd($ex), Contour::bandwidthNrd($wy)], [min($ex), max($ex), min($wy), max($wy)]);
    $cases[] = evidence_case('faithful', 'kde2d grid coordinates', [...$grid['x'], ...$grid['y']], [...$ref['kde2d']['x'], ...$ref['kde2d']['y']], 'tight');
    $cases[] = evidence_case('faithful', 'kde2d density on the 100 × 100 grid', array_merge(...$grid['z']), array_merge(...$ref['kde2d']['z']), 'grid');
    $levels = array_values(array_unique(array_column(Plot::of($faithful)->aes(x: 'eruptions', y: 'waiting')->density2d()->toArray()['data']['values'], 'level')));
    sort($levels);
    $cases[] = evidence_case('faithful', 'density contour levels', $levels, $ref['contours']['levels'], 'exact');
    $pieces = array_map(count(...), Contour::lines($grid, 0.002));
    sort($pieces);
    $cases[] = evidence_case('faithful', 'points in each contour piece at level 0.002', $pieces, $ref['contours']['piece_sizes'], 'exact');
    foreach (['t', 'norm'] as $type) {
        $points = Contour::ellipse($ex, $wy, $type);
        $cases[] = evidence_case('faithful', "stat_ellipse(type = \"{$type}\") polygon x and y", [...array_column($points, 0), ...array_column($points, 1)], [...$ref['ellipse'][$type]['x'], ...$ref['ellipse'][$type]['y']], 'tight');
    }
    $summary = Plot::of($mpg)->aes(x: 'class', y: 'hwy')->summary(funData: 'mean_cl_normal')->toArray()['layer'][0]['data']['values'];
    $byClass = array_column($summary, null, 'class');
    $cases[] = evidence_case('mpg hwy by class', 'mean_cl_normal mean, lower, and upper for 7 classes', array_merge(...array_map(fn ($r) => [$byClass[$r['class']]['hwy'], $byClass[$r['class']]['ymin'], $byClass[$r['class']]['ymax']], $ref['summary'])), array_merge(...array_map(fn ($r) => [$r['mean'], $r['lower'], $r['upper']], $ref['summary'])), 'tight');
    $violin = Plot::of(array_map(fn ($r) => $r + ['k' => 'all'], $faithful))->aes(x: 'k', y: 'eruptions')->violin(n: 5)->toArray()['data']['values'];
    $cases[] = evidence_case('faithful eruptions', 'violin evaluation points', array_column($violin, 'eruptions'), $ref['density']['x'], 'tight');
    $cases[] = evidence_case('faithful eruptions', 'violin widths against density() heights, relative to the largest', array_column($violin, '__width'), $ref['density']['relative'], 'shape');
    $records['r-distributions'] = [
        'title' => 'Distributions',
        'label' => 'Distributions',
        'checked' => 'Normal and t quantiles, sample quantiles, bandwidths, two-dimensional density grids, contour levels and pieces, confidence ellipses, mean_cl_normal intervals, and violin densities. Identical data across implementations.',
        'description' => 'scripts/evidence/r-references.R computes the reference values with R, MASS, and ggplot2 and writes tests/fixtures/r-references.json. scripts/evidence/parity.php computes the same quantities with phiz and compares them value by value.',
        'reference' => "R {$versions['R']}, MASS {$versions['MASS']}, ggplot2 {$versions['ggplot2']}",
        'versions' => ['R' => $versions['R'], 'MASS' => $versions['MASS'], 'ggplot2' => $versions['ggplot2']],
        'inputs' => [['name' => 'faithful', 'detail' => '272 rows, R\'s datasets package'], ['name' => 'mtcars', 'detail' => '32 rows, R\'s datasets package'], ['name' => 'mpg', 'detail' => '234 rows, ggplot2 '.$versions['ggplot2']]],
        'comparison' => evidence_used_rules($cases),
        'cases' => $cases,
        'exclusions' => ['Violin widths are compared as relative heights. phiz scales each violin to its band, so absolute densities are not part of the output.'],
        'known_differences' => ['R\'s density() approximates the kernel sum with an FFT over a binned grid; phiz evaluates the kernel sum exactly. Relative heights differ by about 1e-4.'],
    ];

    // Palettes.
    $cases = [];
    foreach ([3, 5, 8] as $n) {
        $cases[] = evidence_case("{$n} levels", 'hue_pal() colors', Palette::hue($n), $ref['palettes']["hue{$n}"], 'exact');
    }
    $cases[] = evidence_case('4 levels', 'grey_pal() colors', Palette::grey(4), $ref['palettes']['grey4'], 'exact');
    $records['scales-palettes'] = [
        'title' => 'Palettes',
        'label' => 'Palettes',
        'checked' => 'Hue and grey palette colors, as used by theme("gray"), scale_color_hue(), and scale_color_grey().',
        'description' => 'scripts/evidence/r-references.R computes the palettes with the scales package. scripts/evidence/parity.php computes them with phiz and compares the hex colors.',
        'reference' => "R {$versions['R']}, scales {$versions['scales']}: hue_pal(), grey_pal()",
        'versions' => ['R' => $versions['R'], 'scales' => $versions['scales']],
        'inputs' => 'Palettes of 3, 4, 5, and 8 levels.',
        'comparison' => evidence_used_rules($cases),
        'cases' => $cases,
    ];

    // Statistics helpers.
    $cases = [];
    $columns = evidence_columns($mtcars);
    $fit = Phit\fit_ols('mpg ~ wt + hp + factor(cyl)', $columns);
    $grid = array_map(fn ($r) => ['wt' => (float) $r['wt'], 'cyl' => (int) $r['cyl'], 'hp' => (float) $r['hp']], $ref['predictions']['grid']);
    $rows = predictions($fit, $grid, data: $mtcars);
    $cases[] = evidence_case('mtcars, mpg ~ wt + hp + factor(cyl)', 'predictions on a 9-row grid: estimate, lower, upper', [...array_column($rows, 'estimate'), ...array_column($rows, 'lower'), ...array_column($rows, 'upper')], [...$ref['predictions']['fit'], ...$ref['predictions']['lower'], ...$ref['predictions']['upper']], 'tight');
    $fit = Phit\fit_ols('mpg ~ factor(cyl) + factor(am) + wt', $columns);
    $means = array_column(marginal_means($fit, $mtcars, 'cyl')->data->rows, null, 'cyl');
    $cases[] = evidence_case('mtcars, mpg ~ factor(cyl) + factor(am) + wt', 'marginal means of cyl: estimate, lower, upper', array_merge(...array_map(fn ($r) => [$means[$r['cyl']]['estimate'], $means[$r['cyl']]['lower'], $means[$r['cyl']]['upper']], $ref['marginal_means'])), array_merge(...array_map(fn ($r) => [$r['estimate'], $r['lower'], $r['upper']], $ref['marginal_means'])), 'tight');
    $fit = Phit\fit_mixed_gaussian('hwy ~ displ + (1 | manufacturer)', evidence_columns($mpg));
    $effects = array_column(random_effects_plot($fit)->data->rows, null, 'group');
    $groups = $ref['random_effects']['group'];
    $cases[] = evidence_case('mpg, hwy ~ displ + (1 | manufacturer)', 'conditional modes of 15 manufacturers', array_map(fn ($g) => $effects[$g]['estimate'] ?? null, $groups), $ref['random_effects']['mode'], 'mixed');
    $cases[] = evidence_case('mpg, hwy ~ displ + (1 | manufacturer)', 'conditional standard deviations of 15 manufacturers', array_map(fn ($g) => $effects[$g]['std_error'] ?? null, $groups), $ref['random_effects']['sd'], 'mixed');
    $records['r-statistics'] = [
        'title' => 'Statistics',
        'label' => 'Statistics',
        'checked' => 'Predictions with confidence intervals, marginal means with confidence intervals, and random-effect conditional modes and standard deviations. Identical model formulas and data across implementations.',
        'description' => 'scripts/evidence/r-references.R fits the models in R and lme4 and writes tests/fixtures/r-references.json. scripts/evidence/parity.php fits the same models with phit, passes the fits to predictions(), marginal_means(), and random_effects_plot(), and compares the results value by value.',
        'reference' => "R {$versions['R']}: lm(), predict(); lme4 {$versions['lme4']}: lmer(), ranef(condVar = TRUE)",
        'versions' => ['R' => $versions['R'], 'lme4' => $versions['lme4'], 'phit-polyfill' => Composer\InstalledVersions::getPrettyVersion('phpstats/phit-polyfill')],
        'inputs' => [['name' => 'mtcars', 'detail' => '32 rows, R\'s datasets package'], ['name' => 'mpg', 'detail' => '234 rows, ggplot2 '.$versions['ggplot2']]],
        'comparison' => evidence_used_rules($cases),
        'cases' => $cases,
        'exclusions' => ['The fits come from phit-polyfill; the cases test phiz\'s helpers together with phit\'s fits. phit\'s fits are verified separately.', 'Marginal means are compared with the emmeans formula computed directly in R; the emmeans package is not used.'],
    ];

    return $records;
}

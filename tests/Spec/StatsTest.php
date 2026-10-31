<?php

declare(strict_types=1);

use Phiz\Datasets;
use Phiz\Stats\Charts;
use Phiz\Stats\Fit;

use function Phiz\coefplot;
use function Phiz\diagnostic_plots;
use function Phiz\effect_plot;
use function Phiz\fitted_plot;
use function Phiz\interval_plot;
use function Phiz\marginal_means;
use function Phiz\random_effects_plot;
use function Phiz\qq_plot;
use function Phiz\residual_plot;
use function Phiz\scale_location_plot;

/** stats_lm, stats_glm results saved from php-stats (tests/fixtures/fits.json). */
function fits(): array
{
    static $fits = null;

    return $fits ??= json_decode((string) file_get_contents(__DIR__.'/../fixtures/fits.json'), true);
}

/** A matrix fit like stats_ols: mpg on wt and hp with an intercept, no names. */
function olsFit(): array
{
    $cars = Datasets::mtcars();
    $fit = Fit::ols(array_map(fn ($r) => [1, $r['wt'], $r['hp']], $cars), array_column($cars, 'mpg'));
    $t = Fit::tQuantile(0.975, $fit['df_residual']);

    return $fit + ['conf_int' => array_map(fn ($b, $s) => [$b - $t * $s, $b + $t * $s], $fit['coefficients'], $fit['std_errors'])];
}

/** Draws as CmdStanPHP's draws_columns() returns them. */
function drawColumns(): array
{
    $draws = ['alpha' => [], 'beta[1]' => [], 'beta[2]' => [], 'lp__' => [], '.chain' => [], '.iteration' => [], '.draw' => []];
    for ($i = 0; $i < 200; $i++) {
        $u = ($i + 0.5) / 200;
        $draws['alpha'][] = 1 + 0.5 * Fit::normalQuantile($u);
        $draws['beta[1]'][] = -0.3 + 0.2 * Fit::normalQuantile($u);
        $draws['beta[2]'][] = 0.8 + 0.4 * Fit::normalQuantile(fmod($u * 7, 1) ?: 0.5);
        $draws['lp__'][] = -10.0;
        $draws['.chain'][] = intdiv($i, 100) + 1;
        $draws['.iteration'][] = $i % 100 + 1;
        $draws['.draw'][] = $i + 1;
    }

    return $draws;
}

$charts = [
    'coefplot lm' => fn () => coefplot(fits()['lm']),
    'coefplot with intercept at 90%' => fn () => coefplot(fits()['lm'], intercept: true, level: 0.9),
    'coefplot compare fits' => fn () => coefplot(['Full' => fits()['lm'], 'Reduced' => fits()['lm_reduced']], sort: true),
    'coefplot odds ratios' => fn () => coefplot(fits()['glm'], exponentiate: true),
    'coefplot matrix fit with names' => fn () => coefplot(olsFit(), names: ['wt', 'hp'], terms: ['hp', 'wt']),
    'residual plot' => fn () => residual_plot(fits()['lm']),
    'qq plot' => fn () => qq_plot(fits()['lm']),
    'qq plot of values standardized' => fn () => qq_plot([1.2, -0.4, 0.3, 2.2, -1.1, 0.0], standardize: true),
    'fitted plot' => fn () => fitted_plot(fits()['lm']),
    'scale location plot' => fn () => scale_location_plot(fits()['lm']),
    'diagnostic plots' => fn () => diagnostic_plots(fits()['lm']),
    'effect plot matrix fit' => fn () => effect_plot(olsFit(), Datasets::mtcars(), 'wt', n: 4, design: fn ($row) => [1, $row['wt'], $row['hp']]),
    'marginal means with design' => fn () => marginal_means(fits()['lm_factors'], Datasets::mtcars(), 'cyl', design: carsDesign(...), variables: ['cyl' => 'categorical', 'am' => 'categorical', 'wt' => 'numeric']),
    'marginal means by group' => fn () => marginal_means(fits()['lm_factors'], Datasets::mtcars(), 'cyl', by: 'am', design: carsDesign(...), variables: ['cyl' => 'categorical', 'am' => 'categorical', 'wt' => 'numeric']),
    'random effects intercepts' => fn () => random_effects_plot(fits()['lmer_intercept'], Datasets::mpg()),
    'glm effects on the link scale' => fn () => \Phiz\effect_plot(fits()['glm'], Datasets::mtcars(), 'wt', n: 3, design: fn ($r) => [1, $r['wt'], $r['hp']], scale: 'link'),
    'interval plot from draws' => fn () => interval_plot(drawColumns()),
    'interval plot of beta' => fn () => interval_plot(drawColumns(), variables: ['beta'], prob: 0.8, probOuter: 0.95, pointEst: 'mean'),
    'interval plot from summary' => fn () => interval_plot(['variable' => ['alpha', 'beta[1]'], 'mean' => [1.0, -0.3], 'median' => [1.01, -0.29], 'q5' => [0.2, -0.6], 'q95' => [1.8, 0.0]]),
];

dataset('charts', array_map(fn ($build) => [$build], $charts));

it('builds valid statistics charts', function (Closure $build) {
    $chart = $build();
    expect($chart)->toBeValidVegaLite();
    expect(specSnapshot($chart))->toMatchSnapshot();
})->with('charts');

it('reads coefficient names from columns and drops the intercept', function () {
    $rows = coefplot(fits()['lm'])->data->rows;

    expect(array_column($rows, 'term'))->toBe(['wt', 'hp', 'factor(cyl)6', 'factor(cyl)8'])
        ->and($rows[0]['lower'])->toBe(fits()['lm']['conf_int'][1][0]);
});

it('exponentiates estimates and intervals', function () {
    $row = coefplot(fits()['glm'], exponentiate: true)->data->rows[0];

    expect($row['estimate'])->toEqualWithDelta(exp(fits()['glm']['coefficients'][1]), 1e-12)
        ->and($row['upper'])->toEqualWithDelta(exp(fits()['glm']['conf_int'][1][1]), 1e-12);
});

it('predicts with confidence intervals for a matrix fit', function () {
    $fit = olsFit();
    $rows = Charts::predictions($fit, [['wt' => 3.0, 'hp' => 150]], design: fn ($r) => [1, $r['wt'], $r['hp']]);
    $expected = $fit['coefficients'][0] + 3.0 * $fit['coefficients'][1] + 150 * $fit['coefficients'][2];

    expect($rows[0]['estimate'])->toEqualWithDelta($expected, 1e-10)
        ->and($rows[0]['upper'] - $rows[0]['estimate'])->toEqualWithDelta($rows[0]['estimate'] - $rows[0]['lower'], 1e-10);
});

it('matches R predict() for a formula fit', function () {
    // R: predict(lm(mpg ~ wt + hp + factor(cyl), mtcars), data.frame(wt = 1.513, hp = mean(hp), cyl = 4), interval = 'confidence')
    $first = fits()['effect_lm_grid_first3'][0];

    expect($first['estimate'])->toEqualWithDelta(27.64114399, 1e-7)
        ->and($first['lower'])->toEqualWithDelta(25.16920925, 1e-7)
        ->and($first['upper'])->toEqualWithDelta(30.11307873, 1e-7);
})->skip(! file_exists(__DIR__.'/../fixtures/fits.json'));

it('builds the effect grid from the formula with Phit\model_matrix', function () {
    $rows = Charts::effects(fits()['lm'], Datasets::mtcars(), 'wt', by: 'cyl', n: 3)->data->rows;

    expect(array_slice($rows, 0, 3))->toEqualWithDelta(fits()['effect_lm_grid_first3'], 1e-9);
});

it('plots fits made by phit itself', function () {
    $cars = Datasets::mtcars();
    $columns = [];
    foreach ($cars as $row) {
        foreach ($row as $key => $value) {
            $columns[$key][] = $value;
        }
    }
    $effects = effect_plot(Phit\fit_lm('mpg ~ wt + hp + factor(cyl)', $columns), $cars, 'wt', by: 'cyl', n: 3)->data->rows;
    $means = marginal_means(Phit\fit_lm('mpg ~ factor(cyl) + factor(am) + wt', $columns), $cars, 'cyl')->data->rows;

    expect(array_slice($effects, 0, 3))->toEqualWithDelta(fits()['effect_lm_grid_first3'], 1e-7)
        ->and($means[1]['estimate'])->toEqualWithDelta(19.4382815593, 1e-8);
});

it('explains what predictions need', function (Closure $build) {
    $build();
})->with([
    fn () => Charts::predictions(['coefficients' => [1.0]], [['x' => 1]]),
    fn () => Charts::predictions(['coefficients' => [1.0, 2.0], 'covariance' => [[1, 0], [0, 1]]], [['x' => 1]], design: fn ($r) => [1]),
    fn () => interval_plot(drawColumns(), prob: 0.95, probOuter: 0.5),
    fn () => coefplot(fits()['lm'], names: null, terms: ['nothing']),
])->throws(InvalidArgumentException::class);

/** The design of mpg ~ factor(cyl) + factor(am) + wt, for the design: argument. */
function carsDesign(array $row): array
{
    return [1, (int) ($row['cyl'] == 6), (int) ($row['cyl'] == 8), (int) ($row['am'] == 1), $row['wt']];
}

it('matches emmeans-style marginal means computed in R', function () {
    // R: lm(mpg ~ factor(cyl) + factor(am) + wt, mtcars), averaging the design over am with wt at its mean.
    $rows = marginal_means(fits()['lm_factors'], Datasets::mtcars(), 'cyl', design: carsDesign(...), variables: ['cyl' => 'categorical', 'am' => 'categorical', 'wt' => 'numeric'])->data->rows;

    expect(array_column($rows, 'cyl'))->toBe([4, 6, 8])
        ->and($rows[0]['estimate'])->toEqualWithDelta(23.6956001033, 1e-8)
        ->and($rows[0]['lower'])->toEqualWithDelta(21.4935921050, 1e-8)
        ->and($rows[2]['upper'])->toEqualWithDelta(19.5098277969, 1e-8);
});

it('builds marginal means from the formula', function () {
    $rows = marginal_means(fits()['lm_factors'], Datasets::mtcars(), 'cyl')->data->rows;

    expect($rows[1]['estimate'])->toEqualWithDelta(19.4382815593, 1e-8);
});

it('matches lme4 conditional standard deviations of random intercepts', function () {
    // R: ranef(lmer(hwy ~ displ + (1 | manufacturer), mpg), condVar = TRUE)
    $rows = random_effects_plot(fits()['lmer_intercept'], Datasets::mpg())->data->rows;
    $sd = array_column($rows, 'std_error', 'group');

    expect($sd['audi'])->toEqualWithDelta(0.7440016440, 1e-6)
        ->and($sd['chevrolet'])->toEqualWithDelta(0.7266359164, 1e-6)
        ->and($sd['dodge'])->toEqualWithDelta(0.5370759285, 1e-6);
});

it('draws random slopes in panels from their formula design', function () {
    $plot = random_effects_plot(fits()['lmer_slope'], Datasets::mpg());

    expect($plot)->toBeValidVegaLite()
        ->and(array_unique(array_column($plot->data->rows, 'effect')))->toBe(['(Intercept)', 'displ']);
});

it('labels GLM coefficients by link', function () {
    expect(coefplot(fits()['glm'])->labels['x'])->toBe('Log odds (95% CI)')
        ->and(coefplot(fits()['glm'], exponentiate: true)->labels['x'])->toBe('Odds ratio (95% CI)');
});

it('predicts on the link scale', function () {
    $fit = fits()['glm'];
    $row = Charts::predictions($fit, [['wt' => 3.0, 'hp' => 120]], design: fn ($r) => [1, $r['wt'], $r['hp']], scale: 'link')[0];

    expect($row['estimate'])->toEqualWithDelta($fit['coefficients'][0] + 3.0 * $fit['coefficients'][1] + 120 * $fit['coefficients'][2], 1e-12);
});

it('notes singular mixed-model fits', function () {
    // stats_glmer('am ~ wt + (1 | cyl)', mtcars): lme4 also finds the random-intercept variance at zero.
    $fit = json_decode((string) file_get_contents(__DIR__.'/../fixtures/glmer-singular.json'), true);
    $plot = random_effects_plot($fit, Datasets::mtcars());

    expect($plot->labels['subtitle'])->toBe('Singular fit: a random-effect variance is estimated at zero')
        ->and(array_unique(array_column($plot->data->rows, 'std_error')))->toBe([0.0])
        ->and($plot)->toBeValidVegaLite();
});

it('uses the conditional variances php-stats reports, without the data', function () {
    // stats_lmer('hwy ~ displ + (1 + displ | manufacturer)', mpg) with effects_cond_var (php-stats 7120e02).
    $fit = json_decode((string) file_get_contents(__DIR__.'/../fixtures/lmer-condvar.json'), true);
    $reported = random_effects_plot($fit)->data->rows;
    $rebuilt = random_effects_plot(['random' => [array_diff_key($fit['random'][0], ['effects_cond_var' => true])]] + $fit, Datasets::mpg())->data->rows;

    expect(array_column($reported, 'std_error'))->toEqualWithDelta(array_column($rebuilt, 'std_error'), 1e-10)
        ->and(fn () => random_effects_plot(['random' => [array_diff_key($fit['random'][0], ['effects_cond_var' => true])]] + $fit))
        ->toThrow(InvalidArgumentException::class, 'pass the data');
});

it('reproduces the documentation fits with phit', function (string $file) {
    $saved = json_decode((string) file_get_contents($file), true);
    $rows = str_starts_with(basename($file), 'mpg') ? Datasets::mpg() : Datasets::mtcars();
    $columns = [];
    foreach ($rows as $row) {
        foreach ($row as $key => $value) {
            $columns[$key][] = $value;
        }
    }
    $fit = match (true) {
        str_contains($saved['formula'], '|') => Phit\fit_lmer($saved['formula'], $columns),
        isset($saved['family']) => Phit\fit_glm($saved['formula'], $columns, $saved['family']),
        default => Phit\fit_lm($saved['formula'], $columns),
    };

    expect($fit['coefficients'])->toEqualWithDelta($saved['coefficients'], 1e-9)
        ->and($fit['covariance'])->toEqualWithDelta($saved['covariance'], 1e-9);
})->with(array_values(array_filter(glob(__DIR__.'/../../docs/examples/fits/*.json'), fn ($f) => ! str_contains($f, 'draws'))));

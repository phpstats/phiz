<?php

declare(strict_types=1);

use Phiz\Datasets;
use Phiz\Stats\Fit;

// Reference values from R 4.x.

it('matches R normal and t quantiles', function () {
    expect(Fit::normalQuantile(0.975))->toEqualWithDelta(1.959963984540054, 1e-13)
        ->and(Fit::normalQuantile(0.001))->toEqualWithDelta(-3.090232306167813, 1e-12)
        ->and(Fit::normalQuantile(0.3))->toEqualWithDelta(-0.5244005127080408, 1e-13)
        ->and(Fit::tQuantile(0.975, 10))->toEqualWithDelta(2.228138851986274, 1e-11)
        ->and(Fit::tQuantile(0.975, 1))->toEqualWithDelta(12.70620473617469, 1e-9)
        ->and(Fit::tQuantile(0.995, 3.5))->toEqualWithDelta(5.085702223091256, 1e-10)
        ->and(Fit::tQuantile(0.4, 5))->toEqualWithDelta(-0.2671808657041452, 1e-12)
        ->and(Fit::normalCdf(1.959963984540054))->toEqualWithDelta(0.975, 1e-14);
});

it('fits least squares as R lm', function () {
    $cars = Datasets::mtcars();
    $fit = Fit::ols(array_map(fn ($r) => [1, $r['wt']], $cars), array_column($cars, 'mpg'));

    expect($fit['coefficients'][0])->toEqualWithDelta(37.28512616734203, 1e-10)
        ->and($fit['coefficients'][1])->toEqualWithDelta(-5.34447157272268, 1e-10)
        ->and($fit['std_errors'][0])->toEqualWithDelta(1.877627337255894, 1e-10)
        ->and($fit['std_errors'][1])->toEqualWithDelta(0.559101045099323, 1e-10)
        ->and($fit['df_residual'])->toBe(30);
});

it('computes smooth bands as R predict(interval = "confidence")', function () {
    $rows = Fit::smooth(Datasets::mtcars(), 'wt', 'mpg', [], degree: 1, level: 0.9, n: 2);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['wt'])->toBe(1.513)
        ->and($rows[0]['mpg'])->toEqualWithDelta(29.19894067781262, 1e-9)
        ->and($rows[0]['__ymin'])->toEqualWithDelta(27.34136023360207, 1e-9)
        ->and($rows[1]['__ymax'])->toEqualWithDelta(10.5815128823529, 1e-9);
});

it('fits smooth bands per group and skips groups too small to fit', function () {
    $rows = [['x' => 1, 'y' => 1, 'g' => 'a'], ['x' => 2, 'y' => 2.5, 'g' => 'a'], ['x' => 3, 'y' => 2.9, 'g' => 'a'], ['x' => 1, 'y' => 0, 'g' => 'b']];
    $out = Fit::smooth($rows, 'x', 'y', ['g'], n: 4);

    expect($out)->toHaveCount(4)->and(array_unique(array_column($out, 'g')))->toBe(['a']);
});

it('matches R type 7 quantiles and bw.nrd0', function () {
    $wt = array_column(Datasets::mtcars(), 'wt');
    sort($wt);

    expect(Fit::quantile($wt, 0.05))->toEqualWithDelta(1.736, 1e-12)
        ->and(Fit::quantile($wt, 0.25))->toEqualWithDelta(2.58125, 1e-12)
        ->and(Fit::quantile($wt, 0.9))->toEqualWithDelta(4.0475, 1e-12)
        ->and(Fit::bandwidth($wt))->toEqualWithDelta(0.345475746268657, 1e-12);
});

it('rejects singular designs', function () {
    Fit::ols([[1, 2], [1, 2], [1, 2]], [1, 2, 3]);
})->throws(InvalidArgumentException::class, 'singular');

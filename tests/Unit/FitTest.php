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

it('fits loess as R loess(surface = "direct", statistics = "exact")', function () {
    $mpg = Datasets::mpg();
    [$x, $y] = [array_map(floatval(...), array_column($mpg, 'displ')), array_map(floatval(...), array_column($mpg, 'hwy'))];
    $at = [1.6, 2.5, 3.3, 4.7, 7];
    $fit = Fit::loess($x, $y, $at, span: 0.75, degree: 2);
    $wide = Fit::loess($x, $y, $at, span: 1.5, degree: 1);

    expect($fit['fit'])->toEqualWithDelta([33.0916646435, 26.6307318135, 23.2243602603, 17.5405006902, 24.4676955449], 1e-9)
        ->and($fit['se'])->toEqualWithDelta([0.782548384777, 0.363773621604, 0.504139864794, 0.417777555543, 1.99298922257], 1e-10)
        ->and($fit['df'])->toEqualWithDelta(228.888062719, 1e-8)
        ->and($fit['sigma'])->toEqualWithDelta(3.37211103763, 1e-10)
        ->and($wide['fit'])->toEqualWithDelta([30.6855104975, 27.0417839632, 23.8549756019, 19.002988929, 12.7005817212], 1e-9)
        ->and($wide['se'])->toEqualWithDelta([0.434110201313, 0.303269219259, 0.244872574048, 0.336274356443, 0.752411117001], 1e-10)
        ->and($wide['df'])->toEqualWithDelta(231.860726844, 1e-8);

    $band = Fit::smooth($mpg, 'displ', 'hwy', [], n: 2, span: 0.75);
    expect(array_column($band, '__ymin'))->toEqualWithDelta([31.549745077, 20.5407446524], 1e-8)
        ->and(array_column($band, '__ymax'))->toEqualWithDelta([34.6335842099, 28.3946464373], 1e-8);
});

it('rejects loess spans too small for the data', function () {
    Fit::loess([1.0, 2.0, 3.0, 4.0], [1.0, 3.0, 2.0, 5.0], [2.5], span: 0.5);
})->throws(InvalidArgumentException::class, 'too small');

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

it('matches ggplot2 hue palettes and R hcl()', function () {
    expect(Phiz\Palette::hue(3))->toBe(['#F8766D', '#00BA38', '#619CFF'])
        ->and(Phiz\Palette::hue(5))->toBe(['#F8766D', '#A3A500', '#00BF7D', '#00B0F6', '#E76BF3'])
        ->and(Phiz\Palette::hue(8))->toBe(['#F8766D', '#CD9600', '#7CAE00', '#00BE67', '#00BFC4', '#00A9FF', '#C77CFF', '#FF61CC']);
});

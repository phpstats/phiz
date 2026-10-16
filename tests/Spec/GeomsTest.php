<?php

declare(strict_types=1);

use Phiz\Plot;

use function Phiz\aes;
use function Phiz\expr;

$geoms = [
    'point' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: 'g', size: 'n')->point(alpha: 0.7, shape: 17),
    'point jitter discrete' => fn () => Plot::of(sample())->aes(x: 'g', y: 'y')->point(jitter: true),
    'jitter continuous' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->jitter(width: 0.2),
    'line' => fn () => Plot::of(sample())->aes(x: 'date', y: 'y', color: 'h')->line(linewidth: 2, linetype: 'dashed'),
    'path' => fn () => Plot::of(sample())->aes(x: 'y', y: 'x')->path(),
    'step' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->step(direction: 'vh'),
    'area stacked' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', fill: 'h')->area(alpha: 0.8),
    'area fill' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', fill: 'h')->area(position: 'fill'),
    'ribbon' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->ribbon(aes(ymin: 'lo', ymax: 'hi'), fill: 'steelblue')->line(),
    'bar counts' => fn () => Plot::of(sample())->aes(x: 'g')->bar(),
    'bar counts horizontal' => fn () => Plot::of(sample())->aes(y: 'g')->bar(fill: '#555555'),
    'bar stacked by fill' => fn () => Plot::of(sample())->aes(x: 'g', fill: 'h')->bar(),
    'bar dodge' => fn () => Plot::of(sample())->aes(x: 'g', fill: 'h')->bar(position: 'dodge'),
    'bar fill' => fn () => Plot::of(sample())->aes(x: 'g', fill: 'h')->bar(position: 'fill'),
    'col' => fn () => Plot::of(sample())->aes(x: 'g', y: 'y')->col(width: 0.6, color: 'black'),
    'histogram' => fn () => Plot::of(sample())->aes(x: 'y')->histogram(bins: 10),
    'histogram binwidth' => fn () => Plot::of(sample())->aes(x: 'y', fill: 'h')->histogram(binwidth: 0.5, boundary: 0, position: 'identity', alpha: 0.5),
    'histogram density' => fn () => Plot::of(sample())->aes(x: 'y')->histogram(bins: 5, density: true)->density(),
    'freqpoly' => fn () => Plot::of(sample())->aes(x: 'y', color: 'h')->freqpoly(binwidth: 1),
    'density' => fn () => Plot::of(sample())->aes(x: 'y')->density(bw: 0.5),
    'density filled groups' => fn () => Plot::of(sample())->aes(x: 'y', fill: 'h')->density(alpha: 0.4, adjust: 1.5),
    'boxplot' => fn () => Plot::of(sample())->aes(x: 'h', y: 'y', fill: 'h')->boxplot(coef: 2, color: 'black'),
    'errorbar' => fn () => Plot::of(sample())->aes(x: 'g', y: 'y', ymin: 'lo', ymax: 'hi')->errorbar(width: 6)->point(),
    'errorbar horizontal' => fn () => Plot::of(sample())->aes(y: 'g', xmin: 'lo', xmax: 'hi')->errorbar(),
    'linerange' => fn () => Plot::of(sample())->aes(x: 'x', ymin: 'lo', ymax: 'hi')->linerange(linewidth: 3),
    'pointrange' => fn () => Plot::of(sample())->aes(x: 'g', y: 'y', ymin: 'lo', ymax: 'hi', color: 'h')->pointrange(),
    'pointrange horizontal' => fn () => Plot::of(sample())->aes(y: 'g', x: 'y', xmin: 'lo', xmax: 'hi')->pointrange(size: 80),
    'hline and vline' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->hline(4, linetype: 'dashed', color: 'red')->vline([2, 5]),
    'hline mapped' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->hline(aes: aes(yintercept: 'lo'), data: [['lo' => 3.5]]),
    'hline from a slider' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->slider('cutoff', 0, 7, 0.1, 4)->hline(expr('cutoff')),
    'abline' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->abline(intercept: 1, slope: 0.8),
    'segment' => fn () => Plot::of(sample())->aes(x: 'x', y: 'lo', xend: 'x', yend: 'hi')->segment(color: 'gray'),
    'text' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', label: 'g')->text(size: 14, angle: 45, nudgeY: 5),
    'label' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', label: 'g')->label(),
    'tile' => fn () => Plot::of(sample())->aes(x: 'g', y: 'h', fill: 'y')->tile(color: 'white'),
    'tile numeric' => fn () => Plot::of(sample())->aes(x: 'x', y: 'n', fill: 'y')->raster(),
    'rect' => fn () => Plot::of([['a' => 1, 'b' => 2, 'c' => 1, 'd' => 3, 'k' => 'u'], ['a' => 3, 'b' => 4, 'c' => 2, 'd' => 5, 'k' => 'v']])->rect(aes(xmin: 'a', xmax: 'b', ymin: 'c', ymax: 'd', fill: 'k'), alpha: 0.5),
    'bin2d' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->bin2d(bins: 5),
    'smooth loess' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->smooth(),
    'smooth lm with band' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->smooth(method: 'lm'),
    'smooth lm by group without band' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: 'h')->point()->smooth(method: 'lm', se: false),
    'smooth poly' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->smooth(method: 'poly', order: 2, level: 0.9, n: 5),
    'curve' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->curve(fn ($x) => 1 + 0.9 * $x, 1, 6, n: 3, color: 'red'),
    'curve alone' => fn () => Plot::of()->curve(fn ($x) => exp(-$x * $x / 2) / sqrt(2 * M_PI), -3, 3, n: 7),
    'rug' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->rug(),
    'ecdf' => fn () => Plot::of(sample())->aes(x: 'y', color: 'h')->ecdf(),
    'qq' => fn () => Plot::of(sample())->aes(sample: 'y')->qq()->qqLine(color: 'red'),
    'arc' => fn () => Plot::of(sample())->aes(theta: 'n', fill: 'g')->arc(),
    'pie of counts' => fn () => Plot::of(sample())->aes(fill: 'g')->pie(innerRadius: 40),
    'raw layer' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->layer(['mark' => 'tick', 'encoding' => ['x' => ['field' => 'x', 'type' => 'quantitative']]]),
];

dataset('geoms', array_map(fn ($build) => [$build], $geoms));

it('compiles each geom to a valid Vega-Lite spec', function (Closure $build) {
    $plot = $build();
    expect($plot)->toBeValidVegaLite();
    expect(specSnapshot($plot))->toMatchSnapshot();
})->with('geoms');

it('requires the aesthetics a geom needs', function () {
    Plot::of(sample())->aes(x: 'x')->text()->toArray();
})->throws(InvalidArgumentException::class, 'geom text needs the label aesthetic');

it('rejects unknown positions and methods', function (Closure $build) {
    $build()->toArray();
})->with([
    fn () => Plot::of(sample())->aes(x: 'g')->bar(position: 'sideways'),
    fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->smooth(method: 'gam'),
    fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->smooth(method: 'loess', se: true),
])->throws(InvalidArgumentException::class);

it('needs a layer', function () {
    Plot::of(sample())->toArray();
})->throws(InvalidArgumentException::class, 'no layers');

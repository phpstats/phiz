<?php

declare(strict_types=1);

use Phit\Datasets;
use Phit\Palette;
use Phit\Plot;
use Phit\Stats\Contour;

use function Phit\factor;
use function Phit\ggplot;
use function Phit\aes;
use function Phit\geom_point;
use function Phit\guide_axis;
use function Phit\guide_legend;
use function Phit\guides;
use function Phit\label_comma;
use function Phit\label_date;
use function Phit\label_dollar;
use function Phit\label_number;
use function Phit\label_percent;
use function Phit\label_scientific;
use function Phit\scale_color_distiller;
use function Phit\scale_color_grey;
use function Phit\scale_color_hue;
use function Phit\scale_color_identity;
use function Phit\scale_color_viridis_c;
use function Phit\scale_color_viridis_d;
use function Phit\scale_fill_fermenter;
use function Phit\scale_fill_gradientn;
use function Phit\scale_x_discrete;
use function Phit\scale_y_continuous;
use function Phit\theme_linedraw;

/** A smooth 2D field on a regular grid, for contour(). */
function surface(): array
{
    $rows = [];
    foreach (range(0, 20) as $i) {
        foreach (range(0, 20) as $j) {
            [$x, $y] = [$i / 20 * 6 - 3, $j / 20 * 6 - 3];
            $rows[] = ['x' => $x, 'y' => $y, 'z' => sin($x) * cos($y)];
        }
    }

    return $rows;
}

$faithful = fn () => Plot::of(Datasets::faithful())->aes(x: 'eruptions', y: 'waiting');
$cars = fn () => Plot::of(Datasets::mpg());

$parity = [
    'smooth loess band' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->point()->smooth(span: 0.5),
    'smooth loess band by group' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'drv')->smooth(level: 0.9),
    'density2d' => fn () => $faithful()->point()->density2d(),
    'density2d level color' => fn () => $faithful()->density2d(aes: ['color' => 'level'], bins: 6),
    'density2d filled' => fn () => $faithful()->density2dFilled(),
    'density2d filled faceted' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->density2dFilled(contourVar: 'ndensity', bins: 5)->facetWrap('year'),
    'contour' => fn () => Plot::of(surface())->aes(x: 'x', y: 'y', z: 'z')->contour(binwidth: 0.25),
    'contour filled' => fn () => Plot::of(surface())->aes(x: 'x', y: 'y', z: 'z')->contourFilled(bins: 6),
    'polygon' => fn () => Plot::of([['x' => 0, 'y' => 0, 'g' => 'a'], ['x' => 2, 'y' => 0, 'g' => 'a'], ['x' => 1, 'y' => 2, 'g' => 'a'], ['x' => 3, 'y' => 0, 'g' => 'b'], ['x' => 4, 'y' => 1, 'g' => 'b'], ['x' => 3, 'y' => 2, 'g' => 'b']])->aes(x: 'x', y: 'y', fill: 'g')->polygon(alpha: 0.6),
    'summary mean_se' => fn () => $cars()->aes(x: 'class', y: 'hwy')->jitter(alpha: 0.3)->summary(color: 'red'),
    'summary mean_cl_normal crossbar' => fn () => $cars()->aes(x: 'class', y: 'hwy', fill: 'drv')->summary(funData: 'mean_cl_normal', geom: 'crossbar', position: 'dodge'),
    'summary fun line' => fn () => $cars()->aes(x: 'year:O', y: 'hwy', color: 'drv')->summary(fun: 'median', geom: 'line'),
    'summary horizontal col' => fn () => $cars()->aes(x: 'hwy', y: 'class')->summary(fun: 'mean', geom: 'col'),
    'crossbar' => fn () => Plot::of(sample())->aes(x: 'g', y: 'y', ymin: 'lo', ymax: 'hi')->crossbar(width: 0.5),
    'count' => fn () => $cars()->aes(x: 'cty', y: 'hwy')->count(),
    'ellipse' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->ellipse()->ellipse(type: 'norm', geom: 'polygon', aes: ['fill' => 'drv']),
    'annotate' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->point()
        ->annotate('rect', xmin: 2, xmax: 3, ymin: -INF, ymax: INF, fill: 'orange', alpha: 0.2)
        ->annotate('text', x: 6, y: 40, label: 'Note')
        ->annotate('segment', x: 5, y: 35, xend: 6, yend: 38),
    'annotate faceted' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->point()->annotate('text', x: [3, 5], y: 40, label: ['a', 'b'])->facetWrap('drv'),
    'coord polar pie' => fn () => $cars()->aes(fill: 'class')->bar()->coordPolar(theta: 'y'),
    'coord polar donut' => fn () => Plot::of(sample())->aes(y: 'n', fill: 'g')->col()->coordPolar(theta: 'y', innerRadius: 60, start: M_PI / 2),
    'coord polar rose' => fn () => $cars()->aes(x: factor('class', ['suv', 'compact', 'midsize', 'subcompact', 'pickup', 'minivan', '2seater']), fill: 'class')->bar()->coordPolar(),
    'coord fixed' => fn () => $faithful()->point()->coordFixed(0.05),
    'coord fixed flipped' => fn () => $faithful()->point()->coordFixed(0.05)->coordFlip(),
    'viridis option' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'cty')->point()->apply(scale_color_viridis_c(option: 'A', direction: -1)),
    'viridis discrete range' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->apply(scale_color_viridis_d(begin: 0.2, end: 0.8)),
    'distiller' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'cty')->point()->apply(scale_color_distiller(palette: 'Spectral')),
    'fermenter' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->bin2d()->apply(scale_fill_fermenter()),
    'gradientn' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->bin2d()->apply(scale_fill_gradientn(['#000004', '#B63679', '#FCFDBF'])),
    'grey' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->apply(scale_color_grey()),
    'hue options' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->apply(scale_color_hue(l: 45, direction: -1)),
    'identity colors' => fn () => Plot::of([['x' => 1, 'y' => 1, 'c' => 'red'], ['x' => 2, 'y' => 2, 'c' => '#00F']])->aes(x: 'x', y: 'y', color: 'c')->point()->apply(scale_color_identity()),
    'discrete limits and labels' => fn () => $cars()->aes(x: 'drv', y: 'hwy')->boxplot()->apply(scale_x_discrete(limits: ['r', 'f', '4'], labels: ['f' => 'Front', 'r' => 'Rear', '4' => '4WD'])),
    'labels from breaks' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->point()->scaleX(breaks: [2, 4, 6], labels: ['two', 'four', 'six']),
    'label percent' => fn () => $cars()->aes(x: 'class', fill: 'drv')->bar(position: 'fill')->apply(scale_y_continuous(labels: label_percent())),
    'label dollar' => fn () => Plot::of(Datasets::economics())->aes(x: 'date', y: 'pce')->line()->apply(scale_y_continuous(labels: label_dollar(scale: 0.001, suffix: 'k'))),
    'label comma, number, scientific, date' => fn () => Plot::of(Datasets::economics())->aes(x: 'date', y: 'pop')->line()
        ->scaleY(labels: label_comma())->scaleX(labels: label_date('%Y'))->scaleColor(labels: label_scientific())->scaleSize(labels: label_number(accuracy: 0.1, bigMark: ' ')),
    'guides' => fn () => ggplot(Datasets::mpg(), aes(x: 'class', y: 'hwy', color: 'drv')) |> geom_point() |> guides(x: guide_axis(angle: 45), color: guide_legend(title: 'Drive', ncol: 2)),
    'guides none' => fn () => $cars()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->guides(color: 'none'),
    'theme linedraw' => fn () => $cars()->aes(x: 'displ', y: 'hwy')->point()->apply(theme_linedraw()),
    'theme element options' => fn () => $cars()->aes(x: 'class', y: 'hwy', color: 'drv')->point()
        ->theme(xLabelAngle: 45, legendPosition: 'bottom', legendDirection: 'horizontal', legendTitle: false, axisLine: true, ticks: false, gridColor: '#EEEEEE', textColor: '#333333'),
];

dataset('parity', array_map(fn ($build) => [$build], $parity));

it('compiles each ggplot2 feature to a valid Vega-Lite spec', function (Closure $build) {
    $spec = $build()->toArray();

    expect($spec)->toBeValidVegaLite()
        ->and(specSnapshot($spec))->toMatchSnapshot();
})->with('parity');

it('computes loess bands in PHP by default and leaves large data to Vega-Lite', function () {
    $mpg = Datasets::mpg();
    $small = Plot::of($mpg)->aes(x: 'displ', y: 'hwy')->smooth()->toArray();
    $large = Plot::of(array_merge(...array_fill(0, 13, $mpg)))->aes(x: 'displ', y: 'hwy')->smooth()->toArray();
    $tiny = Plot::of(sample())->aes(x: 'x', y: 'y', color: 'g')->smooth()->toArray();

    expect($small['layer'][0]['mark']['type'])->toBe('area')
        ->and($small['layer'][0]['data']['values'])->toHaveCount(80)
        ->and($large['transform'][0])->toHaveKey('loess')
        ->and($tiny['transform'][0])->toHaveKey('loess');
});

it('draws density contours at ggplot2\'s pretty breaks', function () {
    $spec = Plot::of(Datasets::faithful())->aes(x: 'eruptions', y: 'waiting')->density2d()->toArray();
    $levels = array_values(array_unique(array_column($spec['data']['values'], 'level')));
    sort($levels);
    $filled = Plot::of(Datasets::faithful())->aes(x: 'eruptions', y: 'waiting')->density2dFilled()->toArray();

    expect($levels)->toBe([0.002, 0.004, 0.006, 0.008, 0.01, 0.012, 0.014, 0.016, 0.018, 0.02, 0.022, 0.024])
        ->and($filled['encoding']['fill']['sort'][0])->toBe('(0.000, 0.002]')
        ->and($filled['encoding']['fill']['sort'])->toHaveCount(13);
});

it('traces contours as R and ggplot2 do', function () {
    $f = Datasets::faithful();
    [$x, $y] = [array_column($f, 'eruptions'), array_column($f, 'waiting')];
    $grid = Contour::kde2d($x, $y, [Contour::bandwidthNrd($x), Contour::bandwidthNrd($y)], [min($x), max($x), min($y), max($y)]);
    $pieces = array_map(count(...), Contour::lines($grid, 0.002));
    sort($pieces);

    // MASS::kde2d(faithful$eruptions, faithful$waiting, n = 100) and layer_data(geom_density_2d(breaks = 0.002)).
    expect($grid['z'][9][19])->toEqualWithDelta(0.015920971585, 1e-12)
        ->and($grid['z'][49][49])->toEqualWithDelta(0.00360922096115, 1e-12)
        ->and($pieces)->toBe([6, 120, 129])
        ->and(Contour::pretty(0.000123, 0.0371))->toBe([0.0, 0.005, 0.01, 0.015, 0.02, 0.025, 0.03, 0.035, 0.04])
        ->and(Contour::pretty(-3.2, 7.9))->toBe([-4.0, -3.0, -2.0, -1.0, 0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0])
        ->and(Contour::breaks(-1.7196150257, 1.7196150257, bins: 8))->toEqualWithDelta([-2.057142857, -1.542857143, -1.028571429, -0.5142857143, 0, 0.5142857143, 1.028571429, 1.542857143, 2.057142857], 1e-8)
        ->and(Contour::breaks(3, 197, bins: 5))->toBe([0.0, 40.0, 80.0, 120.0, 160.0, 200.0])
        ->and(Contour::format([-2.0571428, -0.5142857, 0.0, 1.0285714]))->toBe(['-2.057', '-0.514', '0.000', '1.029']);
});

it('closes filled regions with holes running the other way', function () {
    // A ring: high values around a low center.
    $z = [];
    foreach (range(0, 6) as $i) {
        foreach (range(0, 6) as $j) {
            $d = max(abs($i - 3), abs($j - 3));
            $z[$i][$j] = $d === 2 ? 1.0 : 0.0;
        }
    }
    $rings = Contour::region(['x' => range(0, 6), 'y' => range(0, 6), 'z' => $z], 0.5);
    $area = function (array $ring): float {
        $sum = 0.0;
        for ($k = 0; $k < count($ring) - 1; $k++) {
            $sum += $ring[$k][0] * $ring[$k + 1][1] - $ring[$k + 1][0] * $ring[$k][1];
        }

        return $sum / 2;
    };
    $areas = array_map($area, $rings);
    sort($areas);

    expect($rings)->toHaveCount(2)
        ->and($areas[0])->toBeLessThan(0)
        ->and($areas[1])->toBeGreaterThan(0)
        ->and(abs($areas[1]))->toBeGreaterThan(abs($areas[0]));
});

it('summarizes as ggplot2\'s stat_summary', function () {
    $spec = Plot::of(Datasets::mpg())->aes(x: 'class', y: 'hwy')->summary(funData: 'mean_cl_normal')->toArray();
    $compact = array_values(array_filter($spec['layer'][0]['data']['values'], fn ($r) => $r['class'] === 'compact'))[0];

    // t.test(mpg$hwy[mpg$class == "compact"])$conf.int, as Hmisc's smean.cl.normal behind mean_cl_normal.
    expect($compact['hwy'])->toEqualWithDelta(28.29787234, 1e-8)
        ->and($compact['ymin'])->toEqualWithDelta(27.1875472011, 1e-9)
        ->and($compact['ymax'])->toEqualWithDelta(29.4081974797, 1e-9);
});

it('places annotations at the panel edges for infinite positions', function () {
    $spec = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->annotate('rect', xmin: 2, xmax: 3, ymin: -INF, ymax: INF)->toArray();
    $rect = $spec['layer'][1];

    expect($rect['encoding']['y'])->toBe(['value' => 'height'])
        ->and($rect['encoding']['y2'])->toBe(['value' => 0])
        ->and($rect['encoding']['x']['field'])->toBe('x')
        ->and($rect['encoding']['x2']['title'])->toBe('x')
        ->and(fn () => Plot::of(sample())->annotate('violin', x: 1))->toThrow(InvalidArgumentException::class);
});

it('sizes fixed-ratio panels from the data ranges', function () {
    $spec = Plot::of(Datasets::faithful())->aes(x: 'eruptions', y: 'waiting')->point()->coordFixed(0.05)->toArray();

    expect($spec['width'])->toBe(300)
        ->and($spec['height'])->toBe((int) round(300 * 0.05 * (96 - 43) / (5.1 - 1.6)))
        ->and($spec['encoding']['x']['scale'])->toMatchArray(['domain' => [1.6, 5.1], 'nice' => false]);
});

it('rejects geoms without a polar form', function () {
    Plot::of(sample())->aes(x: 'x', y: 'y')->point()->coordPolar()->toArray();
})->throws(InvalidArgumentException::class, 'no polar form');

it('computes grey and hue palettes as the scales package', function () {
    expect(Palette::grey(4))->toBe(['#333333', '#818181', '#ABABAB', '#CCCCCC'])
        ->and(Palette::hue(3, 15, 100, 45))->toHaveCount(3)
        ->and(Plot::of(Datasets::mpg())->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->apply(scale_color_grey())->toArray()['encoding']['color']['scale']['range'])
        ->toBe(Palette::grey(3));
});

it('draws ellipses as ggplot2\'s stat_ellipse', function () {
    $f = Datasets::faithful();
    [$x, $y] = [array_column($f, 'eruptions'), array_column($f, 'waiting')];

    // layer_data(ggplot(faithful, aes(eruptions, waiting)) + stat_ellipse(type = ...)), points 1 and 14.
    foreach (['t' => [6.2714584089, 100.6544930083, 3.5017382477, 83.9699989987], 'norm' => [6.2970840838, 101.0398510774, 3.4012704996, 84.4914161762]] as $type => $expected) {
        $e = Contour::ellipse($x, $y, $type);
        expect([$e[0][0], $e[0][1], $e[13][0], $e[13][1]])->toEqualWithDelta($expected, 1e-9);
    }
    expect(Contour::ellipse([1.0, 2.0, 3.0], [1.0, 3.0, 2.0]))->toBe([]);
});

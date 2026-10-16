<?php

declare(strict_types=1);

use Phit\Chart;
use Phit\Datasets;
use Phit\Export;
use Phit\Plot;

use function Phit\factor;
use function Phit\guide_axis;
use function Phit\label_percent;

/*
 * Visual regression tests: each plot is rendered to PNG with vl-convert and compared with its baseline in
 * tests/Visual/baselines. The plots use Liberation Sans, which vl-convert bundles, so text renders the same on every
 * platform. Plots must be deterministic, so none uses jitter. A failure writes the new rendering and a difference
 * image to tests/Visual/output.
 *
 * Create or update baselines with PHIT_UPDATE_VISUAL=1 vendor/bin/pest tests/Visual, then look at the images.
 */

const VISUAL_BASELINES = __DIR__.'/baselines';
const VISUAL_OUTPUT = __DIR__.'/output';

/** The share of pixels whose largest channel difference exceeds 40 (of 255), or 1.0 when the sizes differ. */
function visualDifference(string $expected, string $actual, ?string $diffPath = null): float
{
    $a = imagecreatefromstring($expected);
    $b = imagecreatefromstring($actual);
    [$w, $h] = [imagesx($a), imagesy($a)];
    if ($w !== imagesx($b) || $h !== imagesy($b)) {
        return 1.0;
    }
    $diff = $diffPath === null ? null : imagecreatetruecolor($w, $h);
    $changed = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            [$p, $q] = [imagecolorsforindex($a, imagecolorat($a, $x, $y)), imagecolorsforindex($b, imagecolorat($b, $x, $y))];
            $delta = max(abs($p['red'] - $q['red']), abs($p['green'] - $q['green']), abs($p['blue'] - $q['blue']), 2 * abs($p['alpha'] - $q['alpha']));
            if ($delta > 40) {
                $changed++;
            }
            if ($diff !== null) {
                imagesetpixel($diff, $x, $y, $delta > 40 ? imagecolorallocate($diff, 255, 0, 0) : imagecolorallocate($diff, 235, 235, 235));
            }
        }
    }
    if ($diff !== null) {
        imagepng($diff, $diffPath);
    }

    return $changed / ($w * $h);
}

$mpg = fn () => Plot::of(Datasets::mpg());
$faithful = fn () => Plot::of(Datasets::faithful())->aes(x: 'eruptions', y: 'waiting');

$visual = [
    'point-color' => fn () => $mpg()->aes(x: 'displ', y: 'hwy', color: 'class')->point(),
    'histogram' => fn () => $mpg()->aes(x: 'hwy')->histogram(binwidth: 2, boundary: 0, fill: '#4C78A8', color: 'white'),
    'boxplot-violin' => fn () => $mpg()->aes(x: 'drv', y: 'hwy')->violin(adjust: 1, fill: '#9ECAE1')->boxplot(width: 0.15, outliers: false),
    'bar-dodge' => fn () => $mpg()->aes(x: 'class', fill: 'drv')->bar(position: 'dodge'),
    'facet-grid-free' => fn () => $mpg()->aes(x: 'displ', y: 'hwy')->point()->facetGrid(rows: 'drv', cols: 'year', scales: 'free_y')->size(220, 120),
    'theme-gray' => fn () => $mpg()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->theme('gray'),
    'smooth-loess' => fn () => $mpg()->aes(x: 'displ', y: 'hwy')->point(alpha: 0.4)->smooth(),
    'smooth-lm-groups' => fn () => $mpg()->aes(x: 'displ', y: 'hwy', color: 'drv')->point()->smooth(method: 'lm'),
    'density2d' => fn () => $faithful()->point(size: 10)->density2d(),
    'density2d-filled' => fn () => $faithful()->density2dFilled(),
    'contour-filled' => fn () => Plot::of(visualSurface())->aes(x: 'x', y: 'y', z: 'z')->contourFilled(bins: 7)->contour(bins: 7, color: 'white', linewidth: 0.5),
    'polygon' => fn () => Plot::of([
        ['x' => 0, 'y' => 0, 'g' => 'a'], ['x' => 4, 'y' => 0, 'g' => 'a'], ['x' => 2, 'y' => 3, 'g' => 'a'],
        ['x' => 3, 'y' => 1, 'g' => 'b'], ['x' => 6, 'y' => 1, 'g' => 'b'], ['x' => 6, 'y' => 4, 'g' => 'b'], ['x' => 3, 'y' => 4, 'g' => 'b'],
    ])->aes(x: 'x', y: 'y', fill: 'g')->polygon(alpha: 0.7, color: 'black'),
    'summary' => fn () => $mpg()->aes(x: 'class', y: 'hwy')->point(alpha: 0.3)->summary(funData: 'mean_cl_normal', color: '#D62728'),
    'crossbar-dodge' => fn () => $mpg()->aes(x: 'class', y: 'hwy', fill: 'drv')->summary(funData: 'mean_se', geom: 'crossbar', position: 'dodge'),
    'count-annotate' => fn () => $mpg()->aes(x: 'cty', y: 'hwy')
        ->annotate('rect', xmin: 15, xmax: 20, ymin: -INF, ymax: INF, fill: 'orange', alpha: 0.2)
        ->count(alpha: 0.6)
        ->annotate('text', x: 30, y: 15, label: 'Efficient', fontSize: 14)
        ->annotate('segment', x: 28, y: 17, xend: 32, yend: 40, color: 'red'),
    'ellipse' => fn () => $faithful()->point(alpha: 0.5)->ellipse()->ellipse(type: 'norm', linetype: 'dashed'),
    'pie' => fn () => $mpg()->aes(fill: 'class')->bar()->coordPolar(theta: 'y'),
    'rose' => fn () => $mpg()->aes(x: factor('class', ['suv', 'compact', 'midsize', 'subcompact', 'pickup', 'minivan', '2seater']), fill: 'class')->bar()->coordPolar(),
    'coord-fixed' => fn () => $faithful()->point()->coordFixed(0.05),
    'scales-labels' => fn () => $mpg()->aes(x: 'class', fill: 'drv')->bar(position: 'fill')
        ->scaleY(labels: label_percent(), name: 'share')->scaleFill(scheme: ['name' => 'viridis', 'extent' => [0.2, 0.8]])->guides(x: guide_axis(angle: 45)),
    'theme-linedraw' => fn () => $mpg()->aes(x: 'displ', y: 'hwy')->point()->facetWrap('drv')->theme('linedraw')->size(200, 200),
];

/** A smooth 2D field on a regular grid. */
function visualSurface(): array
{
    $rows = [];
    foreach (range(0, 30) as $i) {
        foreach (range(0, 30) as $j) {
            [$x, $y] = [$i / 30 * 6 - 3, $j / 30 * 6 - 3];
            $rows[] = ['x' => $x, 'y' => $y, 'z' => sin($x) * cos($y) + 0.1 * $x * $y];
        }
    }

    return $rows;
}

dataset('visual', array_map(fn ($build) => [$build], $visual));

it('renders as its baseline', function (Closure $build) {
    $name = $this->dataName();
    preg_match('/"([^"]+)"/', $name, $m);
    $name = $m[1] ?? $name;
    /** @var Chart $plot */
    $plot = $build()->theme(font: 'Liberation Sans');
    $png = $plot->png(scale: 1);
    $baseline = VISUAL_BASELINES."/{$name}.png";

    if (getenv('PHIT_UPDATE_VISUAL') === '1' || ! is_file($baseline)) {
        if (getenv('PHIT_UPDATE_VISUAL') !== '1') {
            $this->fail("No baseline for '{$name}'. Run PHIT_UPDATE_VISUAL=1 vendor/bin/pest tests/Visual and check the new image.");
        }
        file_put_contents($baseline, $png);
        expect(is_file($baseline))->toBeTrue();

        return;
    }
    $difference = visualDifference((string) file_get_contents($baseline), $png);
    if ($difference > 0.002) {
        is_dir(VISUAL_OUTPUT) || mkdir(VISUAL_OUTPUT, recursive: true);
        file_put_contents(VISUAL_OUTPUT."/{$name}.png", $png);
        visualDifference((string) file_get_contents($baseline), $png, VISUAL_OUTPUT."/{$name}.diff.png");
    }

    expect($difference)->toBeLessThanOrEqual(0.002, sprintf("'%s' differs from its baseline in %.2f%% of pixels; see tests/Visual/output.", $name, 100 * $difference));
})->with('visual')->skip(fn () => ! Export::available() || ! extension_loaded('gd'), 'Visual tests need vl-convert and the GD extension.');

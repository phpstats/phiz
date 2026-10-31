<?php

declare(strict_types=1);

use Phiz\Compose;
use Phiz\Data;
use Phiz\Plot;

use function Phiz\aes;
use function Phiz\calc;
use function Phiz\factor;
use function Phiz\field;

$base = fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: 'g')->point();

$features = [
    'scale log and breaks' => fn () => $base()->scaleX(type: 'log', breaks: [1, 2, 5], name: 'x (log)')->scaleY(type: 'sqrt', format: '.1f'),
    'scale reverse and domain' => fn () => $base()->scaleY(reverse: true, domain: [0, 10], zero: true),
    'scale color scheme' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: 'n')->point()->scaleColor(scheme: 'viridis'),
    'scale color manual' => fn () => $base()->scaleColor(values: ['a' => '#E41A1C', 'b' => '#377EB8', 'c' => '#4DAF4A']),
    'scale gradient2' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: 'y')->point()->scaleColor(range: ['#832424', '#FFFFFF', '#3A3A98'], domainMid: 4),
    'scale hides legend' => fn () => $base()->scaleColor(guide: false),
    'scale fill on bars' => fn () => Plot::of(sample())->aes(x: 'g', fill: 'h')->bar()->scaleFill(scheme: 'set2', name: 'Kind'),
    'scale time' => fn () => Plot::of(sample())->aes(x: 'date', y: 'y')->line()->scaleX(type: 'time', format: '%b %Y'),
    'labs' => fn () => $base()->labs(title: 'Title', subtitle: 'Subtitle', x: 'X axis', y: 'Y axis', color: 'Group'),
    'labs on facets' => fn () => $base()->facetWrap('h')->labs(h: 'Panel'),
    'title only' => fn () => $base()->title('Just a title'),
    'caption' => fn () => $base()->labs(x: 'X axis', caption: 'Source: sample data')->theme('minimal', captionColor: '#475569', captionAnchor: 'middle'),
    'caption with title' => fn () => $base()->labs(title: 'Title', caption: 'Source: sample data')->facetWrap('h'),
    'capped axis' => fn () => $base()->scaleX(domain: [0, 5], breaks: [1, 2, 3, 4])->guides(x: Phiz\guide_axis(cap: true))->theme('classic'),
    'facet wrap' => fn () => $base()->facetWrap('h', ncol: 2),
    'facet wrap free scales' => fn () => $base()->facetWrap('h', scales: 'free_y'),
    'facet wrap two fields' => fn () => $base()->facetWrap(['g', 'h']),
    'facet grid' => fn () => $base()->facetGrid(rows: 'h', cols: 'g'),
    'facet labeller both' => fn () => $base()->facetGrid(rows: 'h', labeller: 'both'),
    'facet labeller map' => fn () => $base()->facetGrid(rows: 'h', cols: 'g', labeller: ['h' => ['p' => 'Panel P', 'q' => 'Panel Q']]),
    'facet wrap labeller map' => fn () => $base()->facetWrap('h', labeller: ['p' => 'P "quoted"']),
    'facet grid columns only' => fn () => $base()->facetGrid(cols: 'h', scales: 'free'),
    'facet with smooth band' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->smooth(method: 'lm')->facetWrap('h'),
    'facet with reference line' => fn () => $base()->hline(3)->facetWrap('h'),
    'coord flip' => fn () => Plot::of(sample())->aes(x: 'g', y: 'y')->boxplot()->coordFlip(),
    'coord flip bars dodge' => fn () => Plot::of(sample())->aes(x: 'g', fill: 'h')->bar(position: 'dodge')->coordFlip(),
    'coord cartesian' => fn () => $base()->coordCartesian(xlim: [2, 5], ylim: [2, 6]),
    'xlim' => fn () => $base()->xlim(0, 10),
    'theme gray' => fn () => $base()->theme('gray'),
    'theme minimal base size' => fn () => $base()->theme('minimal', baseSize: 14),
    'theme options' => fn () => $base()->theme(legendPosition: 'bottom', font: 'Georgia', grid: false, background: '#fafafa'),
    'theme legend none' => fn () => $base()->theme('bw', legendPosition: 'none'),
    'theme raw config' => fn () => $base()->theme(config: ['axis' => ['labelAngle' => -45]]),
    'theme dark' => fn () => $base()->theme('dark'),
    'theme void' => fn () => $base()->theme('void'),
    'theme classic' => fn () => $base()->theme('classic'),
    'theme light' => fn () => $base()->theme('light'),
    'size' => fn () => $base()->size(400, 250),
    'size container' => fn () => $base()->width('container')->height(200),
    'facet size' => fn () => $base()->facetWrap('h')->size(200, 150),
    'tooltip all' => fn () => $base()->tooltip(),
    'tooltip fields' => fn () => $base()->tooltip(['x', 'y', 'date']),
    'tooltip aesthetic' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', tooltip: ['g', 'n'])->point(),
    'interactive zoom' => fn () => $base()->interactive(),
    'brush' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: 'g')->point()->brush(),
    'brush without color' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->brush(encodings: ['x', 'y']),
    'highlight legend' => fn () => $base()->line()->highlight(),
    'slider and dropdown' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point(filter: 'datum.y > cutoff')->slider('cutoff', 0, 6, 0.5, 2, 'Minimum y')->dropdown('kind', ['a', 'b']),
    'layer filter by brush' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->brush()->smooth(method: 'lm', se: false, filter: ['param' => 'brush']),
    'factor and typed fields' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y', color: factor('n'), shape: 'h:N')->point(),
    'field properties' => fn () => Plot::of(sample())->aes(x: field('date', timeUnit: 'month', title: 'Month'), y: 'mean(y)')->line(),
    'aggregate shorthand' => fn () => Plot::of(sample())->aes(x: 'g', y: 'sum(n)')->col(),
    'calculated field' => fn () => Plot::of(sample())->aes(x: 'x', y: calc('datum.hi - datum.lo', 'width'))->point(),
    'escaped field names' => fn () => Plot::of([['Sepal.Length' => 5.1, 'a[b]' => 1], ['Sepal.Length' => 4.9, 'a[b]' => 2]])->aes(x: 'Sepal.Length', y: 'a[b]')->point()->smooth(method: 'lm', se: false),
    'layer data and inherit' => fn () => $base()->point(aes(x: 'a', y: 'b'), data: [['a' => 1, 'b' => 1]], inheritAes: false, color: 'black', size: 80),
    'layer aes override' => fn () => $base()->line(aes(color: 'h')),
    'url data' => fn () => Plot::of(Data::url('https://vega.github.io/vega-datasets/data/cars.json'))->aes(x: 'Horsepower:Q', y: 'Miles_per_Gallon:Q', color: 'Origin:N')->point(),
    'named data' => fn () => Plot::of(Data::named('live'))->aes(x: 'x:Q', y: 'y:Q')->line(),
    'top-level transform' => fn () => $base()->transform(['filter' => 'datum.y > 2']),
    'raw merge' => fn () => $base()->raw(['autosize' => ['type' => 'fit'], 'config' => ['view' => ['stroke' => null]]]),
    'mark passthrough' => fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point(strokeWidth: 2, stroke: 'black', cursor: 'pointer'),
    'description' => fn () => $base()->description('Accessible description'),
    'compose beside' => fn () => Compose::beside($base(), Plot::of(sample())->aes(x: 'y')->histogram())->title('Two', 'views'),
    'compose stack linked' => fn () => Compose::stack(
        Plot::of(sample())->aes(x: 'x', y: 'y')->point()->brush(),
        Plot::of(sample())->aes(x: 'g')->bar(filter: ['param' => 'brush']),
    ),
    'compose grid themed' => fn () => Compose::grid([$base(), $base(), $base()], columns: 2)->theme('minimal'),
];

dataset('features', array_map(fn ($build) => [$build], $features));

it('compiles each feature to a valid Vega-Lite spec', function (Closure $build) {
    $chart = $build();
    expect($chart)->toBeValidVegaLite();
    expect(specSnapshot($chart))->toMatchSnapshot();
})->with('features');

it('places a caption below the plot and styles it from the theme', function () {
    $plot = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->labs(caption: 'Source')->theme('gray', baseSize: 20, captionColor: '#475569');
    $spec = $plot->toArray();

    expect($spec['title'])->toBe(['text' => 'Source', 'orient' => 'bottom', 'anchor' => 'end', 'fontWeight' => 'normal', 'fontSize' => 16.0, 'color' => '#475569'])
        ->and($spec['config'])->not->toHaveKey('caption')
        ->and($spec)->toHaveKey('encoding');
});

it('keeps the title on top when a plot also has a caption', function () {
    $spec = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->labs(title: 'Title', caption: 'Source')->size(300, 200)->toArray();

    expect($spec['title'])->toBe('Title')
        ->and($spec['vconcat'])->toHaveCount(1)
        ->and($spec['vconcat'][0]['title']['text'])->toBe('Source')
        ->and($spec['vconcat'][0]['width'])->toBe(300);
});

it('caps the axis line at the outer breaks', function () {
    $spec = Plot::of(sample())->aes(x: 'x', y: 'y')->line()->scaleX(domain: [-4, 104], breaks: [0, 50, 100])->guides(x: Phiz\guide_axis(cap: true))->toArray();
    $rule = end($spec['layer']);

    expect($spec['layer'][0]['encoding']['x']['axis'])->toBe(['values' => [0, 50, 100], 'domain' => false])
        ->and($rule['mark']['type'])->toBe('rule')
        ->and($rule['encoding']['x']['datum'])->toBe(0)
        ->and($rule['encoding']['x2']['datum'])->toBe(100)
        ->and($rule['encoding']['y']['value'])->toBe('height');
});

it('refuses a capped axis on a faceted plot', function () {
    Plot::of(sample())->aes(x: 'x', y: 'y')->point()->facetWrap('h')->guides(x: Phiz\guide_axis(cap: true))->scaleX(breaks: [1, 2])->toArray();
})->throws(InvalidArgumentException::class, 'faceted');

it('is immutable', function () {
    $base = Plot::of(sample())->aes(x: 'x', y: 'y');
    $points = $base->point();
    $lines = $base->line();

    expect($base->layers)->toHaveCount(0)
        ->and($points->layers)->toHaveCount(1)
        ->and($lines->layers[0]->geom)->toBe('line');
});

it('composes ggplot2-style functions with apply()', function () {
    $applied = Plot::of(sample())->apply(
        Phiz\geom_point(alpha: 0.5),
        Phiz\geom_smooth(method: 'lm', se: false),
        Phiz\scale_y_log10(),
        Phiz\labs(title: 'Applied', x: 'X'),
        Phiz\theme_minimal(),
    );
    $fluent = Plot::of(sample())
        ->point(alpha: 0.5)
        ->smooth(method: 'lm', se: false)
        ->scaleY(type: 'log')
        ->labs(title: 'Applied', x: 'X')
        ->theme('minimal');

    expect($applied->aes(x: 'x', y: 'y')->toArray())->toBe($fluent->aes(x: 'x', y: 'y')->toArray());
});

it('starts a plot with ggplot() and aes()', function () {
    $plot = Phiz\ggplot(sample(), aes(x: 'x', y: 'y'))->apply(Phiz\geom_line());

    expect($plot->toArray()['encoding']['x']['field'])->toBe('x');
});

it('applies reusable plot fragments', function () {
    $house = fn (Plot $p) => $p->theme('minimal')->size(300, 200);
    $plot = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->apply($house);

    expect($plot->width)->toBe(300)->and($plot->config['view']['stroke'])->toBeNull();
});

it('merges scale settings from successive calls', function () {
    $spec = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->scaleX(type: 'log')->scaleX(name: 'Log x')->toArray();

    expect($spec['encoding']['x']['scale']['type'])->toBe('log')
        ->and($spec['encoding']['x']['title'])->toBe('Log x');
});

it('keeps zero for bar charts but not for scatterplots', function () {
    $scatter = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->toArray();
    $bars = Plot::of(sample())->aes(x: 'g', y: 'y')->col()->hline(1)->toArray();

    expect($scatter['encoding']['y']['scale']['zero'])->toBeFalse()
        ->and($bars['layer'][0]['encoding']['y'])->not->toHaveKey('scale');
});

it('rejects unknown aesthetics, themes, and facet scales', function (Closure $build) {
    $build();
})->with([
    fn () => aes(colr: 'g'),
    fn () => Plot::of(sample())->theme('solarized'),
    fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->facetWrap('g', scales: 'loose')->toArray(),
    fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->point()->scaleX(type: 'logarithmic'),
    fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->line(linetype: 'wavy')->toArray(),
    fn () => Plot::of(sample())->highlight(),
])->throws(InvalidArgumentException::class);

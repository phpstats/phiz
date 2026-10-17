<?php

declare(strict_types=1);

use Phiz\Compose;
use Phiz\Data;
use Phiz\Export;
use Phiz\Plot;
use Phiz\Stats\Charts;

use function Phiz\aes;
use function Phiz\calc;
use function Phiz\expr;

function dotted(): array
{
    return [['x' => 1, 'y' => 2, 'g.h' => 'u'], ['x' => 2, 'y' => 3, 'g.h' => 'v'], ['x' => 3, 'y' => 1, 'g.h' => 'u']];
}

function grid(): array
{
    $rows = [];
    foreach (['p', 'q'] as $a) {
        foreach (['r', 's'] as $b) {
            foreach (range(1, 6) as $x) {
                $rows[] = ['a' => $a, 'b' => $b, 'x' => $x, 'y' => $x * ($a === 'p' ? 1 : 2) + ($b === 'r' ? 0.3 : -0.2) * ($x % 3)];
            }
        }
    }

    return $rows;
}

it('escapes dotted fields in legend selections', function () {
    $spec = Plot::of(dotted())->aes(x: 'x', y: 'y', color: 'g.h')->point()->highlight()->toArray();

    expect($spec['params'][0]['select']['fields'])->toBe(['g\\.h']);
});

it('titles escaped fields with their raw names', function () {
    $spec = Plot::of(dotted())->aes(x: 'x', y: 'y', color: 'g.h')->point()->toArray();

    expect($spec['encoding']['color']['title'])->toBe('g.h');
});

it('adds tooltips to raw layers with string marks', function () {
    $plot = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->layer(['mark' => 'tick', 'encoding' => ['x' => ['field' => 'x', 'type' => 'quantitative']]])->tooltip();

    expect($plot)->toBeValidVegaLite()
        ->and($plot->toArray()['layer'][1]['mark'])->toBe(['type' => 'tick', 'tooltip' => true]);
});

it('lays out multi-field facets by their combinations', function () {
    $spec = Plot::of(grid())->aes(x: 'x', y: 'y')->point()->facetWrap(['a', 'b'], nrow: 2)->toArray();

    expect($spec['columns'])->toBe(2);
});

it('fits PHP-computed smooths per multi-field facet panel', function () {
    $plot = Plot::of(grid())->aes(x: 'x', y: 'y')->point()->smooth(method: 'lm', n: 2)->facetWrap(['a', 'b']);
    $spec = $plot->toArray();
    $computed = array_values(array_filter($spec['data']['values'], fn ($row) => isset($row['__layer'])));

    expect($plot)->toBeValidVegaLite()
        ->and($computed)->toHaveCount(8)
        ->and(array_unique(array_map(fn ($r) => $r['a'].$r['b'], $computed)))->toHaveCount(4);
});

it('refuses to facet a layer with its own URL data', function () {
    Plot::of(grid())->aes(x: 'x', y: 'y')->point()->line(data: Data::url('other.csv'))->facetWrap('a')->toArray();
})->throws(InvalidArgumentException::class, 'URL or named data');

it('lifts sliders to the top of a composition', function () {
    $chart = Compose::beside(
        Plot::of(sample())->aes(x: 'x', y: 'y')->slider('c', 0, 5)->point()->hline(expr('c')),
        Plot::of(sample())->aes(x: 'x', y: 'y')->point()->brush(),
    );
    $spec = $chart->toArray();

    expect($chart)->toBeValidVegaLite()
        ->and($spec['params'][0]['name'])->toBe('c')
        ->and($spec['hconcat'][0])->not->toHaveKey('params')
        ->and($spec['hconcat'][1]['params'][0]['name'])->toBe('brush');
});

it('places rugs along the flipped axes', function () {
    $spec = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->rug()->coordFlip()->toArray();
    [$xRug, $yRug] = [$spec['layer'][1]['encoding'], $spec['layer'][2]['encoding']];

    expect($xRug['y']['field'])->toBe('x')->and($xRug['x'])->toBe(['value' => 0])
        ->and($yRug['x']['field'])->toBe('y')->and($yRug['y']['value'])->toEqual(expr('height'));
});

it('keeps bar widths under coordFlip', function () {
    $mark = Plot::of(sample())->aes(x: 'g', y: 'y')->col(width: 0.3)->coordFlip()->toArray()['mark'];

    expect($mark)->toBe(['type' => 'bar', 'height' => ['band' => 0.3]]);
});

it('formats exponents in expressions', function () {
    $transform = Plot::of(sample())->aes(x: 'x', y: 'y')->point()->abline(0, 1e-10)->toArray()['layer'][1]['transform'];

    expect($transform[1]['calculate'])->toBe('0 + 1.0e-10 * datum.__x1');
});

it('computes QQ probabilities per group from ranks', function () {
    $transforms = Plot::of(sample())->aes(sample: 'y', color: 'g')->qq()->toArray()['transform'];

    expect($transforms[1]['window'][0]['op'])->toBe('row_number')
        ->and($transforms[1]['groupby'])->toBe(['g'])
        ->and($transforms[2]['joinaggregate'][0]['op'])->toBe('count')
        ->and($transforms[2]['groupby'])->toBe(['g']);
});

it('smooths dates and computed fields in Vega-Lite, and says why bands need numbers', function () {
    $dates = Plot::of(sample())->aes(x: 'date', y: 'y')->smooth(method: 'lm')->toArray();
    $calc = Plot::of(sample())->aes(x: 'x', y: calc('datum.y * 2', 'y2'))->smooth(method: 'lm')->toArray();

    expect($dates['transform'][0]['regression'])->toBe('y')
        ->and($dates['encoding']['x']['type'])->toBe('temporal')
        ->and($calc['transform'][1]['regression'])->toBe('y2')
        ->and(fn () => Plot::of(sample())->aes(x: 'date', y: 'y')->smooth(method: 'lm', se: true)->toArray())
        ->toThrow(InvalidArgumentException::class, 'numeric x and y');
});

it('skips groups that cannot be fitted', function () {
    $rows = [...grid(), ...array_map(fn ($i) => ['a' => 'z', 'b' => 'r', 'x' => $i % 2, 'y' => $i], range(1, 5))];
    $computed = Plot::of($rows)->aes(x: 'x', y: 'y', color: 'a')->smooth(method: 'poly', order: 2, n: 3)->toArray()['layer'][0]['data']['values'];

    expect(array_values(array_unique(array_column($computed, 'a'))))->toBe(['p', 'q']);
});

it('rejects smooths that cannot be drawn', function (Closure $build) {
    $build();
})->with([
    fn () => Plot::of(sample())->aes(x: 'x', y: 'y')->smooth(method: 'lm', n: 1)->toArray(),
    fn () => Plot::of([['x' => 1, 'y' => 1], ['x' => 1, 'y' => 2]])->aes(x: 'x', y: 'y')->smooth(method: 'lm')->toArray(),
    fn () => Plot::of(Data::url('a.csv'))->aes(x: 'x:Q')->density(adjust: 2)->toArray(),
])->throws(InvalidArgumentException::class);

it('orders prediction bounds under decreasing links', function () {
    $fit = ['coefficients' => [0.5, 0.1], 'covariance' => [[0.01, 0], [0, 0.001]], 'link' => 'inverse'];
    $row = Charts::predictions($fit, [['x' => 1]], design: fn ($r) => [1, $r['x']])[0];

    expect($row['lower'])->toBeLessThan($row['estimate'])
        ->and($row['upper'])->toBeGreaterThan($row['estimate']);
});

it('labels summary intervals by their quantile columns', function () {
    $plot = Charts::intervals(['variable' => ['a', 'b'], 'median' => [1.0, 2.0], 'q2.5' => [0.0, 1.0], 'q97.5' => [2.0, 3.0]]);

    expect($plot->labels['subtitle'])->toBe('95% intervals, median');
});

it('sizes discrete axes from the layer\'s own data', function () {
    $many = array_map(fn ($i) => ['k' => "level {$i}", 'v' => $i], range(1, 33));
    $spec = Plot::of(sample())->aes(x: 'x', y: 'y')->col(aes(x: 'k', y: 'v'), data: $many, inheritAes: false)->toArray();

    expect($spec['width'])->toBe(660);
});

it('renders the fixed specs with vl-convert', function () {
    foreach ([
        Plot::of(dotted())->aes(x: 'x', y: 'y', color: 'g.h')->point()->highlight(),
        Plot::of(grid())->aes(x: 'x', y: 'y')->point()->smooth(method: 'lm')->facetWrap(['a', 'b']),
        Compose::beside(Plot::of(sample())->aes(x: 'x', y: 'y')->slider('c', 0, 5)->point()->hline(expr('c')), Plot::of(sample())->aes(x: 'x', y: 'y')->point()),
        Plot::of(sample())->aes(sample: 'y', color: 'h')->qq()->qqLine(),
    ] as $chart) {
        expect($chart->svg())->toStartWith('<svg');
    }
})->skip(! Export::available(), 'vl-convert is not installed');

it('turns hexagons flat-topped under coordFlip', function () {
    $shape = Plot::of(grid())->aes(x: 'x', y: 'y')->hex(bins: 5)->coordFlip()->toArray()['mark']['shape'];

    expect($shape)->toStartWith('M-1,0');
});

it('leaves binning to Vega-Lite when a filter or transform needs the browser', function () {
    $previous = Phiz\Config::$serverRows;
    Phiz\Config::$serverRows = 5;
    try {
        $filtered = Plot::of(grid())->aes(x: 'x')->histogram(filter: "datum.a === 'p'")->toArray();
        $transformed = Plot::of(grid())->transform(['calculate' => 'datum.x * 2', 'as' => 'w'])->aes(x: 'w:Q')->histogram()->toArray();
    } finally {
        Phiz\Config::$serverRows = $previous;
    }

    expect($filtered['encoding']['x']['bin'])->toBe(['maxbins' => 30])
        ->and($transformed['encoding']['x']['bin'])->toBe(['maxbins' => 30])
        ->and(fn () => Plot::of(grid())->aes(x: 'x')->histogram(compute: 'server', filter: 'datum.x > 1')->toArray())->toThrow(InvalidArgumentException::class, 'no layer filter')
        ->and(fn () => Plot::of(sample())->aes(x: 'date', y: 'y')->hex()->toArray())->toThrow(InvalidArgumentException::class, 'a numeric x');
});

it('bins multi-field facet panels in PHP from columns', function () {
    $columns = ['a' => array_column(grid(), 'a'), 'b' => array_column(grid(), 'b'), 'x' => array_column(grid(), 'x'), 'y' => array_column(grid(), 'y')];
    $plot = Plot::of($columns)->aes(x: 'x', y: 'y')->hex(bins: 4)->facetWrap(['a', 'b']);

    expect($plot)->toBeValidVegaLite()
        ->and(array_unique(array_map(fn ($r) => $r['a'].$r['b'], $plot->toArray()['data']['values'])))->toHaveCount(4);
});

it('reads phibble table column names', function () {
    $data = Data::from(Phibble\DataFrame::fromColumns(['a' => [1, 2], 'b' => ['u', 'v']]));

    expect($data->fields())->toBe(['a', 'b'])->and($data->has('a'))->toBeTrue()->and($data->has('z'))->toBeFalse();
});

it('narrows dodged violins to share their band', function () {
    $single = Plot::of(sample())->aes(x: 'h', y: 'y')->violin()->toArray()['encoding']['size']['scale']['range'][1];
    $dodged = Plot::of(sample())->aes(x: 'h', y: 'y', fill: 'g')->violin(position: 'dodge')->toArray()['encoding']['size']['scale']['range'][1];

    expect($dodged)->toEqualWithDelta($single / 3, 0.1);
});

it('estimates violin densities per group with R bandwidths', function () {
    $rows = Plot::of(Phiz\Datasets::faithful())->aes(x: calc("'all'", 'k', 'nominal'), y: 'eruptions')->violin(n: 5)->toArray();
    $computed = Plot::of(array_map(fn ($r) => $r + ['k' => 'all'], Phiz\Datasets::faithful()))->aes(x: 'k', y: 'eruptions')->violin(n: 5)->toArray()['data']['values'];

    // R: density(faithful$eruptions, n = 5, from = 1.6, to = 5.1); relative peak heights.
    expect(array_column($computed, 'eruptions'))->toEqualWithDelta([1.6, 2.475, 3.35, 4.225, 5.1], 1e-12)
        ->and($computed[1]['__width'])->toEqualWithDelta(0.17899231 / 0.46597958, 1e-3)
        ->and($rows['transform'][0]['density'] ?? null)->toBe('eruptions');
});

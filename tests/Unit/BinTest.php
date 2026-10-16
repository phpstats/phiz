<?php

declare(strict_types=1);

use Phibble\Frame;
use Phit\Bin;
use Phit\Config;
use Phit\Data;
use Phit\Plot;

function cloud(int $n = 400): array
{
    $x = [];
    $y = [];
    for ($i = 0; $i < $n; $i++) {
        $x[] = sin($i) * 3 + $i / $n;
        $y[] = cos($i * 1.3) * 2 + $x[$i] * 0.5;
    }

    return ['x' => $x, 'y' => $y, 'g' => array_map(fn ($i) => $i % 2 ? 'a' : 'b', range(0, $n - 1))];
}

it('counts every value in histograms, grids, and hexagons', function () {
    $data = cloud();

    expect(array_sum(array_column(Bin::histogram($data, 'x', bins: 10), 'count')))->toBe(400)
        ->and(array_sum(array_column(Bin::grid($data, 'x', 'y', bins: 8), 'count')))->toBe(400)
        ->and(array_sum(array_column(Bin::hex($data, 'x', 'y', bins: 10), 'count')))->toBe(400);
});

it('bins with exact widths and boundaries', function () {
    $rows = Bin::histogram(['v' => [0.1, 0.4, 0.6, 1.2, 1.9]], 'v', binwidth: 0.5, boundary: 0);

    expect(array_column($rows, 'v_start'))->toBe([0.0, 0.5, 1.0, 1.5])
        ->and(array_column($rows, 'count'))->toBe([2, 1, 1, 1])
        ->and(array_sum(array_map(fn ($r) => $r['density'] * 0.5, $rows)))->toEqualWithDelta(1.0, 1e-12);
});

it('bins per group', function () {
    $rows = Bin::histogram(cloud(), 'x', bins: 5, groups: ['g']);

    expect(array_sum(array_column(array_filter($rows, fn ($r) => $r['g'] === 'a'), 'count')))->toBe(200);
});

it('places points in their nearest hexagon', function () {
    // Points at hexagon centers stay in their own cells.
    $rows = Bin::hex(['x' => [0.0, 1.0, 0.5, 10.0], 'y' => [0.0, 0.0, 0.866, 10.0]], 'x', 'y', bins: 10, xlim: [0, 10], ylim: [0, 10]);

    expect($rows)->toHaveCount(4);
});

it('reads phibble tables column by column and streams generators with limits', function () {
    $frame = Frame::fromColumns(cloud());
    $stream = (function () {
        foreach (range(1, 100) as $i) {
            yield ['v' => $i];
        }
    })();

    expect(array_sum(array_column(Bin::grid($frame, 'x', 'y', 6), 'count')))->toBe(400)
        ->and(array_sum(array_column(Bin::histogram($stream, 'v', bins: 4, limits: [1, 100]), 'count')))->toBe(100)
        ->and(fn () => Bin::histogram((fn () => yield ['v' => 1])(), 'v'))->toThrow(InvalidArgumentException::class, 'pass the limits');
});

it('keeps line shape when downsampling with LTTB', function () {
    $rows = array_map(fn ($i) => ['t' => $i, 'v' => $i === 500 ? 100.0 : sin($i / 50)], range(0, 999));
    $kept = Bin::lttb($rows, 't', 'v', 100);

    expect($kept)->toHaveCount(100)
        ->and(in_array(100.0, array_column($kept, 'v'), true))->toBeTrue()
        ->and($kept[0]['t'])->toBe(0)->and($kept[99]['t'])->toBe(999);
});

it('samples reproducibly', function () {
    $rows = array_map(fn ($i) => ['i' => $i], range(1, 1000));

    expect(Bin::sample($rows, 10, seed: 3))->toBe(Bin::sample($rows, 10, seed: 3))
        ->and(Bin::sample($rows, 10, seed: 3))->toHaveCount(10);
});

it('bins in PHP above the server threshold and embeds only the bins', function () {
    $previous = Config::$serverRows;
    Config::$serverRows = 100;
    try {
        $spec = Plot::of(cloud())->aes(x: 'x', y: 'y')->bin2d(bins: 5)->toArray();
        $faceted = Plot::of(cloud())->aes(x: 'x', y: 'y')->bin2d(bins: 5)->facetWrap('g')->toArray();
        $histogram = Plot::of(cloud())->aes(x: 'x', fill: 'g')->histogram(bins: 5)->toArray();
    } finally {
        Config::$serverRows = $previous;
    }

    expect($spec['data']['values'])->not->toHaveCount(400)
        ->and($spec['encoding']['x']['field'])->toBe('x_start')
        ->and(array_filter($faceted['data']['values'], fn ($r) => ! isset($r['__layer'])))->toBe([])
        ->and($histogram['encoding']['x']['bin'])->toBe(['binned' => true])
        ->and(Plot::of(cloud())->aes(x: 'x', y: 'y')->bin2d(compute: 'server'))->toBeValidVegaLite()
        ->and(Plot::of(cloud())->aes(x: 'x')->histogram(compute: 'server', fill: '#888888'))->toBeValidVegaLite();
});

it('draws hexagons sized to tile the panel', function () {
    $plot = Plot::of(cloud())->aes(x: 'x', y: 'y')->hex(bins: 20)->size(400, 200);
    $spec = $plot->toArray();

    expect($plot)->toBeValidVegaLite()
        ->and($spec['mark']['size'])->toEqualWithDelta((2 * 20 / sqrt(3)) ** 2, 0.01)
        ->and($spec['encoding']['x']['scale']['nice'])->toBeFalse();
});

it('downsamples lines per group', function () {
    $rows = [];
    foreach (['a', 'b'] as $g) {
        foreach (range(1, 500) as $t) {
            $rows[] = ['t' => $t, 'v' => sin($t / 20), 'g' => $g];
        }
    }
    $values = Plot::of($rows)->aes(x: 't', y: 'v', color: 'g')->line(downsample: 50)->toArray()['data']['values'];

    expect($values)->toHaveCount(100);
});

it('keeps tables lazy until a spec embeds them', function () {
    $data = Data::from(Frame::fromColumns(cloud()));

    expect($data->count())->toBe(400)
        ->and($data->type('x'))->toBe('quantitative')
        ->and($data->type('g'))->toBe('nominal')
        ->and($data->levels('g'))->toBe(['b', 'a'])
        ->and($data->rows[1])->toBe(['x' => cloud()['x'][1], 'y' => cloud()['y'][1], 'g' => 'a']);
});

it('warns when a spec embeds too many rows', function () {
    [$previous, $handler] = [Config::$warnRows, Config::$warn];
    [Config::$warnRows, Config::$warn] = [100, null];
    $warnings = [];
    set_error_handler(function (int $level, string $message) use (&$warnings) {
        $warnings[] = $message;

        return true;
    }, E_USER_WARNING);
    try {
        Plot::of(cloud())->aes(x: 'x', y: 'y')->point()->toArray();
        Plot::of(cloud())->aes(x: 'x', y: 'y')->hex(bins: 6)->toArray();
    } finally {
        restore_error_handler();
        [Config::$warnRows, Config::$warn] = [$previous, $handler];
    }

    expect($warnings)->toHaveCount(1)
        ->and($warnings[0])->toContain('embeds 400 data rows');
});

it('sends the size warning to a handler when one is set', function () {
    [$previous, $handler] = [Config::$warnRows, Config::$warn];
    $messages = [];
    [Config::$warnRows, Config::$warn] = [10, function (string $message) use (&$messages) {
        $messages[] = $message;
    }];
    try {
        Plot::of(cloud(50))->aes(x: 'x', y: 'y')->point()->toArray();
    } finally {
        [Config::$warnRows, Config::$warn] = [$previous, $handler];
    }

    expect($messages)->toHaveCount(1);
});

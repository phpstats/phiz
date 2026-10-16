<?php

declare(strict_types=1);

use Phit\Aes;
use Phit\Data;
use Phit\Field;
use Phit\Style;

enum Suit: string
{
    case Hearts = 'H';
}

it('reads rows, columns, iterables, and objects', function () {
    $rows = [['a' => 1, 'b' => 'x'], ['a' => 2, 'b' => 'y']];

    expect(Data::from($rows)->rows)->toBe($rows)
        ->and(Data::from(['a' => [1, 2], 'b' => ['x', 'y']])->rows)->toBe($rows)
        ->and(Data::from(new ArrayIterator($rows))->rows)->toBe($rows)
        ->and(Data::from([(object) ['a' => 1, 'b' => 'x'], (object) ['a' => 2, 'b' => 'y']])->rows)->toBe($rows);
});

it('reads phibble-style tables and collections', function () {
    $table = new class
    {
        public function toColumns(): array
        {
            return ['a' => [1, 2]];
        }
    };
    $frame = new class
    {
        public function toRows(): array
        {
            return [['a' => 1]];
        }

        public function toColumns(): array
        {
            return ['a' => [9]];
        }
    };
    $collection = new class
    {
        public function toArray(): array
        {
            return [['a' => 3]];
        }
    };

    expect(Data::from($table)->rows)->toBe([['a' => 1], ['a' => 2]])
        ->and(Data::from($frame)->rows)->toBe([['a' => 1]])
        ->and(Data::from($collection)->rows)->toBe([['a' => 3]]);
});

it('makes values JSON-safe', function () {
    $rows = Data::from([['d' => new DateTimeImmutable('2024-05-06T07:08:09+00:00'), 'e' => Suit::Hearts, 'n' => NAN, 'i' => INF]])->rows;

    expect($rows[0])->toBe(['d' => '2024-05-06T07:08:09+00:00', 'e' => 'H', 'n' => null, 'i' => null]);
});

it('infers measurement types', function () {
    $data = Data::from([
        ['q' => 1, 'f' => 1.5, 't' => '2024-01-01', 'ts' => '2024-01-01T10:00:00Z', 'n' => 'a', 'mixed' => 1, 'nul' => null],
        ['q' => 2, 'f' => null, 't' => '2024-02-01', 'ts' => '2024-01-02 11:30', 'n' => 'b', 'mixed' => 'b', 'nul' => null],
    ]);

    expect($data->type('q'))->toBe('quantitative')
        ->and($data->type('f'))->toBe('quantitative')
        ->and($data->type('t'))->toBe('temporal')
        ->and($data->type('ts'))->toBe('temporal')
        ->and($data->type('n'))->toBe('nominal')
        ->and($data->type('mixed'))->toBe('nominal')
        ->and($data->type('nul'))->toBeNull()
        ->and(Data::url('x.csv')->type('q'))->toBeNull()
        ->and($data->levels('n'))->toBe(['a', 'b']);
});

it('rejects data it cannot read', function (mixed $data) {
    Data::from($data);
})->with([
    'scalar' => 5,
    'ragged columns' => [['a' => [1, 2], 'b' => [1]]],
    'object' => new stdClass,
    'scalar rows' => [[[1, 2], 3]],
])->throws(InvalidArgumentException::class);

it('describes URL and named data', function () {
    expect(Data::url('cars.csv', type: 'csv', parse: ['hp' => 'number'])->toSpec())->toBe(['url' => 'cars.csv', 'format' => ['type' => 'csv', 'parse' => ['hp' => 'number']]])
        ->and(Data::named('live')->toSpec())->toBe(['name' => 'live']);
});

it('parses field shorthand', function () {
    expect(Field::of('hwy')->toEncoding('quantitative'))->toBe(['field' => 'hwy', 'type' => 'quantitative'])
        ->and(Field::of('year:O')->toEncoding('quantitative'))->toBe(['field' => 'year', 'type' => 'ordinal'])
        ->and(Field::of('mean(hwy)')->toEncoding())->toBe(['field' => 'hwy', 'type' => 'quantitative', 'aggregate' => 'mean'])
        ->and(Field::of('count()')->toEncoding())->toBe(['type' => 'quantitative', 'aggregate' => 'count'])
        ->and(Field::of('month(date):O')->toEncoding())->toBe(['field' => 'date', 'type' => 'ordinal', 'timeUnit' => 'month'])
        ->and(Field::of('a.b[1]')->toEncoding('nominal')['field'])->toBe('a\\.b\\[1\\]')
        ->and(Field::of('x')->with(sort: false, axis: ['grid' => false])->toEncoding('nominal'))->toBe(['field' => 'x', 'type' => 'nominal', 'sort' => null, 'axis' => ['grid' => false]]);
});

it('maps aesthetics with aliases and merges them', function () {
    $aes = Aes::of(x: 'a', colour: 'b', alpha: 'c')->merge(Aes::of(x: 'z', text: 'l'));

    expect($aes->all())->toBe(['x' => 'z', 'color' => 'b', 'opacity' => 'c', 'label' => 'l'])
        ->and($aes->field('color'))->toBe('b')
        ->and($aes->without('x')->has('x'))->toBeFalse();
});

it('translates ggplot2 style names', function () {
    expect(Style::mark(['alpha' => 0.5, 'color' => 'red', 'size' => 3, 'linetype' => 'dashed'], 'line'))->toBe(['opacity' => 0.5, 'color' => 'red', 'strokeWidth' => 3, 'strokeDash' => [4, 4]])
        ->and(Style::mark(['color' => 'black', 'fill' => 'white'], 'filled'))->toBe(['stroke' => 'black', 'fill' => 'white'])
        ->and(Style::mark(['shape' => 1, 'size' => 40], 'point'))->toBe(['shape' => 'circle', 'filled' => false, 'size' => 40])
        ->and(Style::mark(['angle' => 90, 'hjust' => 0, 'vjust' => 1, 'nudgeY' => 4, 'fontface' => 'bold.italic'], 'text'))->toBe(['angle' => 270.0, 'align' => 'left', 'baseline' => 'top', 'dy' => -4, 'fontWeight' => 'bold', 'fontStyle' => 'italic'])
        ->and(Style::dash('4c'))->toBe([4, 12])
        ->and(Style::mark(['strokeCap' => 'round'], 'line'))->toBe(['strokeCap' => 'round']);
});

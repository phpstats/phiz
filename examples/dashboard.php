<?php

declare(strict_types=1);

/*
 * The fuel economy dashboard from the "Dashboards with Livewire" documentation page: three charts built from phplyr
 * summaries of one filtered phibble table. A Livewire component calls fuel_dashboard() in render() with its public
 * properties, so every chart follows the same filters.
 */

use Phibble\Contracts\Table;
use Phiz\Plot;

use function Phiz\{aes, calc, field};
use function Phplyr\{
    arrange, desc, filter, group_by, mean, n, summarize,
};

/**
 * @param string|null $drive 'f', 'r', '4', or null for all
 * @param int|null $year 1999, 2008, or null for both
 * @return array{summary: Table, bars: Plot,
 *     scatter: Plot, trend: Plot}
 */
function fuel_dashboard(
    Table $cars,
    ?string $drive = null,
    ?int $year = null,
    ?string $selected = null,
): array {
    $byDrive = $cars |> filter(
        fn ($r) => $drive === null || $r->drv === $drive,
    );
    $subset = $byDrive |> filter(
        fn ($r) => $year === null || $r->year === $year,
    );

    $byClass = $subset |>
        group_by('class') |>
        summarize(
            cars: n(),
            hwy: mean('hwy'),
            cty: mean('cty'),
        ) |>
        arrange(desc('hwy'));

    // The trend compares the years, so it ignores $year.
    $byManufacturer = $byDrive |>
        group_by('manufacturer', 'year') |>
        summarize(hwy: mean('hwy'));

    $test = 'datum.class === '.json_encode($selected ?? '');

    $bars = Plot::of($byClass)
        ->aes(
            x: 'hwy',
            y: field('class', sort: '-x'),
            fill: calc($test, 'selected', 'nominal'),
        )
        ->col()
        ->scaleFill(
            domain: [false, true],
            range: ['#9ECAE1', '#08519C'],
            guide: false,
        )
        ->tooltip(['class', 'cars', 'hwy', 'cty'])
        ->labs(
            title: 'Mean highway mpg by class',
            x: 'Highway mpg',
            y: null,
        )
        ->size('container', 220);

    $scatter = Plot::of($subset)
        ->aes(x: 'displ', y: 'hwy', color: 'class')
        ->point(alpha: 0.7, size: 40)
        ->smooth(
            aes(x: 'displ', y: 'hwy'),
            method: 'lm',
            color: '#333333',
            inheritAes: false,
        )
        ->tooltip([
            'manufacturer', 'model', 'year', 'displ', 'hwy',
        ])
        ->theme(legendPosition: 'bottom')
        ->labs(
            title: 'Displacement and highway mpg',
            x: 'Displacement (L)',
            y: 'Highway mpg',
            color: null,
        )
        ->size('container', 220);

    $trend = Plot::of($byManufacturer)
        ->aes(x: 'year:O', y: 'hwy', group: 'manufacturer')
        ->line(color: '#BBBBBB')
        ->point(color: '#666666', size: 20)
        ->tooltip(['manufacturer', 'year', 'hwy'])
        ->labs(
            title: 'Manufacturers, 1999 to 2008',
            x: null,
            y: 'Mean highway mpg',
        )
        ->size('container', 220);

    return [
        'summary' => $byClass,
        'bars' => $bars,
        'scatter' => $scatter,
        'trend' => $trend,
    ];
}

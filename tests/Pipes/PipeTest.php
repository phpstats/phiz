<?php

declare(strict_types=1);

use Phiz\Plot;

use function Phiz\aes;

/*
 * Tests in this directory use the pipe operator, so phpunit.xml runs them only on PHP 8.5 and later.
 */

it('composes with the pipe operator', function () {
    $piped = Plot::of(sample())
        |> Phiz\geom_point(alpha: 0.5)
        |> Phiz\geom_smooth(method: 'lm', se: false)
        |> Phiz\scale_y_log10()
        |> Phiz\labs(title: 'Piped', x: 'X')
        |> Phiz\theme_minimal();
    $fluent = Plot::of(sample())
        ->point(alpha: 0.5)
        ->smooth(method: 'lm', se: false)
        ->scaleY(type: 'log')
        ->labs(title: 'Piped', x: 'X')
        ->theme('minimal');

    expect($piped->aes(x: 'x', y: 'y')->toArray())->toBe($fluent->aes(x: 'x', y: 'y')->toArray());
});

it('starts a piped plot with ggplot() and aes()', function () {
    $plot = Phiz\ggplot(sample(), aes(x: 'x', y: 'y')) |> Phiz\geom_line();

    expect($plot->toArray()['encoding']['x']['field'])->toBe('x');
});

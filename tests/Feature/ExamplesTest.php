<?php

declare(strict_types=1);

use Phit\Chart;
use Phit\Export;

$catalog = require __DIR__.'/../../examples/catalog.php';

dataset('examples', array_map(fn ($entry) => [$entry[3]], $catalog));

it('builds a valid spec for every gallery example', function (Closure $build) {
    $chart = $build();
    expect($chart)->toBeInstanceOf(Chart::class)->toBeValidVegaLite();
    expect(specSnapshot($chart))->toMatchSnapshot();
})->with('examples');

it('renders every gallery example to SVG with vl-convert', function (Closure $build) {
    $svg = $build()->svg();

    expect($svg)->toStartWith('<svg')->toContain('role-mark');
})->with('examples')->skip(! Export::available(), 'vl-convert is not installed');

it('writes the gallery page', function () {
    $output = sys_get_temp_dir().'/phit-gallery-'.bin2hex(random_bytes(4)).'.html';
    $php = escapeshellarg(PHP_BINARY);
    exec("{$php} ".escapeshellarg(__DIR__.'/../../examples/gallery.php').' '.escapeshellarg($output).' 2>&1', $lines, $status);

    expect($status)->toBe(0);
    $html = (string) file_get_contents($output);
    unlink($output);
    expect(substr_count($html, 'data-phit="'))->toBe(count(require __DIR__.'/../../examples/catalog.php'))
        ->and($html)->toContain('<details><summary>Code</summary>');
});

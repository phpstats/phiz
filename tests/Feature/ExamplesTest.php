<?php

declare(strict_types=1);

use Phiz\Chart;
use Phiz\Export;

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
    $output = sys_get_temp_dir().'/phiz-gallery-'.bin2hex(random_bytes(4)).'.html';
    $php = escapeshellarg(PHP_BINARY);
    exec("{$php} ".escapeshellarg(__DIR__.'/../../examples/gallery.php').' '.escapeshellarg($output).' 2>&1', $lines, $status);

    expect($status)->toBe(0);
    $html = (string) file_get_contents($output);
    unlink($output);
    expect(substr_count($html, 'data-phiz="'))->toBe(count(require __DIR__.'/../../examples/catalog.php'))
        ->and($html)->toContain('<details><summary>Code</summary>');
});

it('writes the gallery fragment for the documentation', function () {
    $output = sys_get_temp_dir().'/phiz-gallery-'.bin2hex(random_bytes(4)).'.md';
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../../examples/gallery.php').' '.escapeshellarg($output).' 2>&1', $lines, $status);

    expect($status)->toBe(0);
    $markdown = (string) file_get_contents($output);
    unlink($output);
    expect(substr_count($markdown, 'data-phiz="'))->toBe(count(require __DIR__.'/../../examples/catalog.php'))
        ->and($markdown)->toContain("## Statistics\n");
});

it('writes a gallery fragment whose charts load their specs, sized to the page, from files', function () {
    require_once __DIR__.'/../../scripts/docs-fit.php';
    $dir = sys_get_temp_dir().'/phiz-gallery-'.bin2hex(random_bytes(4));
    mkdir($dir);
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/../../examples/gallery.php').' '.escapeshellarg("{$dir}/gallery.md").' '.escapeshellarg("--specs={$dir}/specs").' 2>&1', $lines, $status);

    expect($status)->toBe(0);
    $markdown = (string) file_get_contents("{$dir}/gallery.md");
    $catalog = require __DIR__.'/../../examples/catalog.php';
    $specs = glob("{$dir}/specs/*.json");
    expect($markdown)->not->toContain('data-phiz="')
        ->and(substr_count($markdown, 'data-phiz-deferred'))->toBe(count($catalog))
        ->and($markdown)->toContain('href="specs/coefplot.json"')
        ->and($specs)->toHaveCount(count($catalog))
        ->and(file_get_contents("{$dir}/specs/coefplot.json"))->toBe(json_encode(docs_fit($catalog['coefplot'][3]()), Phiz\Chart::JSON_FLAGS))
        ->and(json_decode(file_get_contents("{$dir}/specs/coefplot.json"), true)['width'])->toBe('container');
    array_map(unlink(...), [...$specs, "{$dir}/gallery.md"]);
    rmdir("{$dir}/specs");
    rmdir($dir);
});

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Phiz\Assets;
use Phiz\Laravel\ScriptState;
use Phiz\Plot;

function chart(): Plot
{
    return Plot::of(sample())->aes(x: 'x', y: 'y')->point()->labs(title: 'Blade {{ $not_blade }}');
}

it('renders a plot with <x-phiz>', function () {
    $html = Blade::render('<x-phiz :plot="$plot" class="h-64" wire:key="rent" />', ['plot' => chart()]);

    expect($html)
        ->toContain('<div data-phiz="')
        ->toContain('class="h-64"')
        ->toContain('wire:key="rent"')
        ->toContain('<div data-phiz-view wire:ignore></div>')
        ->toContain('data-phiz-options="{&quot;actions&quot;:false}"')
        ->toContain('vega-embed@'.Assets::VEGA_EMBED)
        ->toContain('window.Phiz');

    preg_match('/data-phiz="([^"]*)"/', $html, $m);
    $spec = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);
    expect($spec)->toBe(chart()->toArray());
});

it('adds the scripts once per request', function () {
    $html = Blade::render('<x-phiz :plot="$a" /><x-phiz :plot="$b" />', ['a' => chart(), 'b' => chart()]);

    expect(substr_count($html, 'vega-embed.min.js'))->toBe(1)
        ->and(substr_count($html, 'data-phiz="'))->toBe(2);
});

it('leaves scripts to @phizScripts when it was used', function () {
    $html = Blade::render("@phizScripts\n<x-phiz :plot=\"\$plot\" />", ['plot' => chart()]);

    expect(substr_count($html, 'vega.min.js'))->toBe(1);
    expect(strpos($html, 'vega.min.js'))->toBeLessThan(strpos($html, 'data-phiz="'));
});

it('skips scripts on Livewire update requests', function () {
    request()->headers->set('X-Livewire', 'true');

    expect(Blade::render('<x-phiz :plot="$plot" />', ['plot' => chart()]))->not->toContain('<script');
});

it('accepts spec arrays and JSON, and merges embed options', function () {
    $spec = chart()->toArray();
    app(ScriptState::class)->included = true;

    $fromArray = Blade::render('<x-phiz :plot="$spec" :options="[\'theme\' => \'auto\']" />', ['spec' => $spec]);
    $fromJson = Blade::render('<x-phiz :plot="$json" />', ['json' => json_encode($spec)]);

    expect($fromArray)->toContain('{&quot;actions&quot;:false,&quot;theme&quot;:&quot;auto&quot;}')
        ->and($fromJson)->toContain('data-phiz="');
});

it('serves local assets from a configured prefix', function () {
    config(['phiz.assets' => '/vendor/phiz']);

    expect(Blade::render('@phizScripts'))
        ->toContain('src="/vendor/phiz/vega-lite.min.js"')
        ->not->toContain('integrity=');
});

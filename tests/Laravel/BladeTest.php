<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Phit\Assets;
use Phit\Laravel\ScriptState;
use Phit\Plot;

function chart(): Plot
{
    return Plot::of(sample())->aes(x: 'x', y: 'y')->point()->labs(title: 'Blade {{ $not_blade }}');
}

it('renders a plot with <x-phit>', function () {
    $html = Blade::render('<x-phit :plot="$plot" class="h-64" wire:key="rent" />', ['plot' => chart()]);

    expect($html)
        ->toContain('<div data-phit="')
        ->toContain('class="h-64"')
        ->toContain('wire:key="rent"')
        ->toContain('<div data-phit-view wire:ignore></div>')
        ->toContain('data-phit-options="{&quot;actions&quot;:false}"')
        ->toContain('vega-embed@'.Assets::VEGA_EMBED)
        ->toContain('window.Phit');

    preg_match('/data-phit="([^"]*)"/', $html, $m);
    $spec = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);
    expect($spec)->toBe(chart()->toArray());
});

it('adds the scripts once per request', function () {
    $html = Blade::render('<x-phit :plot="$a" /><x-phit :plot="$b" />', ['a' => chart(), 'b' => chart()]);

    expect(substr_count($html, 'vega-embed.min.js'))->toBe(1)
        ->and(substr_count($html, 'data-phit="'))->toBe(2);
});

it('leaves scripts to @phitScripts when it was used', function () {
    $html = Blade::render("@phitScripts\n<x-phit :plot=\"\$plot\" />", ['plot' => chart()]);

    expect(substr_count($html, 'vega.min.js'))->toBe(1);
    expect(strpos($html, 'vega.min.js'))->toBeLessThan(strpos($html, 'data-phit="'));
});

it('skips scripts on Livewire update requests', function () {
    request()->headers->set('X-Livewire', 'true');

    expect(Blade::render('<x-phit :plot="$plot" />', ['plot' => chart()]))->not->toContain('<script');
});

it('accepts spec arrays and JSON, and merges embed options', function () {
    $spec = chart()->toArray();
    app(ScriptState::class)->included = true;

    $fromArray = Blade::render('<x-phit :plot="$spec" :options="[\'theme\' => \'auto\']" />', ['spec' => $spec]);
    $fromJson = Blade::render('<x-phit :plot="$json" />', ['json' => json_encode($spec)]);

    expect($fromArray)->toContain('{&quot;actions&quot;:false,&quot;theme&quot;:&quot;auto&quot;}')
        ->and($fromJson)->toContain('data-phit="');
});

it('serves local assets from a configured prefix', function () {
    config(['phit.assets' => '/vendor/phit']);

    expect(Blade::render('@phitScripts'))
        ->toContain('src="/vendor/phit/vega-lite.min.js"')
        ->not->toContain('integrity=');
});

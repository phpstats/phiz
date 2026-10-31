<?php

declare(strict_types=1);

use Phiz\Assets;
use Phiz\Exceptions\VlConvertNotFound;
use Phiz\Export;
use Phiz\Html;
use Phiz\Plot;

function tiny(): Plot
{
    return Plot::of(sample())->aes(x: 'x', y: 'y')->point()->labs(title: '</script><b>');
}

it('embeds the spec in an attribute the loader reads', function () {
    $html = tiny()->embed(['actions' => false], attributes: ['id' => 'chart', 'class' => 'w-full']);
    preg_match('/data-phiz="([^"]*)"/', $html, $m);

    expect(json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true))->toBe(tiny()->toArray())
        ->and($html)->toContain('id="chart"')->toContain('class="w-full"')
        ->toContain('integrity="sha384-')->toContain('window.Phiz')
        ->not->toContain('</script><b>');
});

it('defers a spec to a link the loader fetches', function () {
    $html = Html::deferred('specs/a "b".json', ['actions' => false], ['class' => 'w-full']);

    expect($html)->toContain('data-phiz-deferred')->not->toContain('data-phiz="')
        ->toContain('<a data-phiz-spec href="specs/a &quot;b&quot;.json">Vega-Lite specification</a>')
        ->toContain('data-phiz-options="{&quot;actions&quot;:false}"')->toContain('class="w-full"')
        ->and(Assets::loader())->toContain('data-phiz-deferred')->toContain('IntersectionObserver');
});

it('can leave out the script tags', function () {
    expect(tiny()->embed(assets: false))->not->toContain('<script');
});

it('writes a standalone page titled by the plot', function () {
    $page = tiny()->html();

    expect($page)->toStartWith('<!DOCTYPE html>')
        ->toContain('<title>&lt;/script&gt;&lt;b&gt;</title>')
        ->toContain('vega@'.Assets::VEGA.'/build/vega.min.js')
        ->toContain('vega-lite@'.Assets::VEGA_LITE)
        ->toContain('data-phiz=');
});

it('supports local, explicit, inline, and no assets', function () {
    $dir = sys_get_temp_dir().'/phiz-assets-'.bin2hex(random_bytes(4));
    mkdir($dir);
    foreach (['vega', 'vega-lite', 'vega-embed'] as $lib) {
        file_put_contents("{$dir}/{$lib}.min.js", "/* {$lib} */ var x = '</script>';");
    }

    expect(Assets::local('/js/')->scripts())->toContain('src="/js/vega-embed.min.js"')
        ->and(Assets::urls('a.js', 'b.js', 'c.js')->scripts(loader: false))->toBe("<script src=\"a.js\"></script>\n<script src=\"b.js\"></script>\n<script src=\"c.js\"></script>\n")
        ->and(Assets::inline($dir)->scripts())->toContain('/* vega-lite */')->toContain("'<\\/script>'")
        ->and(Assets::none()->scripts())->toStartWith('<script>');

    array_map(unlink(...), glob("{$dir}/*"));
    rmdir($dir);
});

it('explains how to install vl-convert when it is missing', function () {
    $path = getenv('PATH');
    $env = [getenv('PHIZ_VL_CONVERT'), getenv('VL_CONVERT')];
    putenv('PATH=/nonexistent');
    putenv('PHIZ_VL_CONVERT');
    putenv('VL_CONVERT');
    try {
        expect(Export::available())->toBeFalse();
        expect(fn () => tiny()->svg())->toThrow(VlConvertNotFound::class, 'github.com/vega/vl-convert/releases');
    } finally {
        putenv("PATH={$path}");
        $env[0] === false ?: putenv("PHIZ_VL_CONVERT={$env[0]}");
        $env[1] === false ?: putenv("VL_CONVERT={$env[1]}");
    }
});

it('saves JSON and HTML without vl-convert', function () {
    $base = sys_get_temp_dir().'/phiz-save-'.bin2hex(random_bytes(4));
    tiny()->save("{$base}.json");
    tiny()->save("{$base}.html");

    expect(json_decode((string) file_get_contents("{$base}.json"), true))->toBe(tiny()->toArray())
        ->and((string) file_get_contents("{$base}.html"))->toContain('<!DOCTYPE html>');
    unlink("{$base}.json");
    unlink("{$base}.html");
});

it('rejects unknown export formats', function () {
    tiny()->save('/tmp/plot.gif');
})->throws(InvalidArgumentException::class, 'Cannot tell the format');

describe('with vl-convert', function () {
    it('renders SVG, PNG, PDF, and Vega', function () {
        $plot = tiny();

        expect($plot->svg())->toStartWith('<svg')
            ->and(substr($plot->png(scale: 1), 0, 8))->toBe("\x89PNG\r\n\x1a\n")
            ->and(substr($plot->pdf(), 0, 5))->toBe('%PDF-')
            ->and((new Export)->vega($plot))->toHaveKey('marks');
    });

    it('reports conversion errors', function () {
        (new Export)->svg(['mark' => 'point', 'encoding' => ['x' => ['field' => 'a', 'type' => 'wrong']]]);
    })->throws(Phiz\Exceptions\ExportFailed::class, 'vl-convert failed');
})->skip(! Export::available(), 'vl-convert is not installed');

it('validates the HTML container markup', function () {
    expect(Html::container(['mark' => 'point'], [], ['hidden' => true, 'skip' => null]))
        ->toBe('<div data-phiz="{&quot;mark&quot;:&quot;point&quot;}" hidden><div data-phiz-view wire:ignore></div></div>');
});

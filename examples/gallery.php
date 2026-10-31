<?php

declare(strict_types=1);

/*
 * Writes an HTML page of every example in catalog.php: the chart, a description, and the code that built it.
 * An output path ending in .md writes a Markdown fragment instead, which the documentation's gallery page includes.
 *
 *     php examples/gallery.php [output.html|output.md] [--svg=dir]
 *
 * --svg also renders each example to dir/{id}.svg through vl-convert.
 */

use Phiz\Assets;
use Phiz\Html;

require __DIR__.'/../vendor/autoload.php';

$output = __DIR__.'/gallery.html';
$svgDir = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--svg=')) {
        $svgDir = substr($arg, 6);
    } else {
        $output = $arg;
    }
}

$catalog = require __DIR__.'/catalog.php';
$source = file(__DIR__.'/catalog.php');

/** The body of an example's build closure, dedented. */
$code = function (Closure $build) use ($source): string {
    $reflection = new ReflectionFunction($build);
    $lines = array_slice($source, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1);
    $text = implode('', $lines);
    $text = preg_replace('/^.*?fn \(\) => /s', '', $text);
    $text = preg_replace('/\],\s*$/', '', rtrim($text));
    $lines = explode("\n", $text);
    $indent = PHP_INT_MAX;
    foreach (array_slice($lines, 1) as $line) {
        if (trim($line) !== '') {
            $indent = min($indent, strlen($line) - strlen(ltrim($line)));
        }
    }
    $indent = $indent === PHP_INT_MAX ? 0 : max(0, $indent - 4);

    return implode("\n", array_map(fn ($line, $i) => $i === 0 ? $line : substr($line, min($indent, strlen($line) - strlen(ltrim($line)))), $lines, array_keys($lines))).';';
};

if (str_ends_with($output, '.md')) {
    $markdown = [];
    foreach ($catalog as $id => [$section, $title, $description, $build]) {
        $chart = $build();
        $markdown[$section] ??= "## {$section}\n\n";
        $markdown[$section] .= "### {$title} {#{$id}}\n\n{$description}\n\n```{=html}\n<div class=\"phiz-figure\">"
            .Html::container($chart, ['actions' => false], ['aria-label' => $title])
            ."</div>\n```\n\n```php\n".$code($build)."\n```\n\n";
    }
    file_put_contents($output, implode('', $markdown));
    fwrite(STDERR, 'Wrote '.count($catalog)." examples to {$output}\n");
    exit(0);
}

$sections = [];
$count = 0;
foreach ($catalog as $id => [$section, $title, $description, $build]) {
    $chart = $build();
    $sections[$section] ??= '';
    $sections[$section] .= '<article id="'.htmlspecialchars($id).'"><h3>'.htmlspecialchars($title).'</h3><p>'.htmlspecialchars($description).'</p>'
        .Html::container($chart, ['actions' => ['source' => true, 'editor' => true, 'export' => true, 'compiled' => false]], ['class' => 'chart'])
        .'<details><summary>Code</summary><pre><code>'.htmlspecialchars($code($build)).'</code></pre></details></article>'."\n";
    if ($svgDir !== null) {
        is_dir($svgDir) || mkdir($svgDir, 0777, true);
        $chart->save(rtrim($svgDir, '/')."/{$id}.svg");
    }
    $count++;
}

$nav = implode(' · ', array_map(fn ($s) => '<a href="#'.strtolower(preg_replace('/\W+/', '-', $s)).'">'.htmlspecialchars($s).'</a>', array_keys($sections)));
$body = "<header><h1>phiz gallery</h1><p>{$count} examples of phiz, a ggplot2-flavored grammar of graphics for PHP that emits Vega-Lite "
    .'(vega '.Assets::VEGA.', vega-lite '.Assets::VEGA_LITE.', vega-embed '.Assets::VEGA_EMBED.").</p><nav>{$nav}</nav></header>\n";
foreach ($sections as $section => $html) {
    $body .= '<section id="'.strtolower(preg_replace('/\W+/', '-', $section)).'"><h2>'.htmlspecialchars($section)."</h2><div class=\"grid\">\n{$html}</div></section>\n";
}
$style = '<style>body{max-width:1400px;margin:0 auto;padding:24px}h2{margin-top:2.5rem;border-bottom:1px solid #ddd}'
    .'.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(520px,1fr));gap:24px}'
    .'article{border:1px solid #e5e5e5;border-radius:8px;padding:16px;overflow:auto}article h3{margin:0 0 4px}article p{margin:0 0 12px;color:#555}'
    .'pre{background:#f6f6f6;padding:12px;border-radius:6px;overflow:auto;font-size:12px}nav a{margin:0 4px}'
    .'@media (max-width:600px){body{padding:16px}.grid{grid-template-columns:1fr}}</style>';

$page = Html::page(null, 'phiz gallery', body: $style.$body);
file_put_contents($output, $page);
fwrite(STDERR, "Wrote {$count} examples to {$output}\n");

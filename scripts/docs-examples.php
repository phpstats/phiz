<?php

declare(strict_types=1);

/*
 * Runs every docs/examples/*.php file and writes what the documentation pages include:
 *
 * - docs/_generated/{name}.md: the example's code and its live chart (the spec it actually produced)
 * - docs/figures/{name}.svg: a static rendering through vl-convert, when it is installed
 * - docs/_phiz-scripts.html: the pinned vega, vega-lite, vega-embed and phiz loader script tags
 * - docs/gallery.html: the examples/ gallery page
 *
 * An example file returns a Chart. Its code is shown as written, with `return` shown as `$plot =`.
 *
 *     php scripts/docs-examples.php [name ...]
 */

use Phiz\Assets;
use Phiz\Chart;
use Phiz\Export;
use Phiz\Html;

require __DIR__.'/../vendor/autoload.php';

$docs = dirname(__DIR__).'/docs';
@mkdir("{$docs}/_generated", 0777, true);
@mkdir("{$docs}/figures", 0777, true);
$only = array_slice($argv, 1);
$svg = Export::available();

file_put_contents("{$docs}/_phiz-scripts.html", "<style>.phiz-figure{margin:0.5rem 0 1.5rem;overflow-x:auto}</style>\n".Assets::cdn()->scripts());

$count = 0;
foreach (glob("{$docs}/examples/*.php") as $file) {
    $name = basename($file, '.php');
    if ($only !== [] && ! in_array($name, $only, true)) {
        continue;
    }
    $chart = (static fn () => require $file)();
    if (! $chart instanceof Chart) {
        fwrite(STDERR, "{$name}: expected a Chart, got ".get_debug_type($chart)."\n");
        exit(1);
    }
    $code = (string) file_get_contents($file);
    $code = preg_replace('/^<\?php\s*(declare\(strict_types=1\);\s*)?/', '', $code);
    $code = preg_replace('/^return /m', '$plot = ', trim($code));

    $markdown = "```php\n{$code}\n```\n\n```{=html}\n<div class=\"phiz-figure\">"
        .Html::container($chart, ['actions' => false], ['aria-label' => $chart->toArray()['description'] ?? $name])
        ."</div>\n```\n";
    file_put_contents("{$docs}/_generated/{$name}.md", $markdown);
    if ($svg) {
        $chart->save("{$docs}/figures/{$name}.svg");
    }
    $count++;
}

// Source listings of shared example code, for pages that show it whole.
foreach (glob(dirname(__DIR__).'/examples/dashboard.php') as $file) {
    $code = preg_replace('/^<\?php\s*(declare\(strict_types=1\);\s*)?/', '', (string) file_get_contents($file));
    file_put_contents("{$docs}/_generated/source-".basename($file, '.php').'.md', "```php\n".trim($code)."\n```\n");
}

if ($only === []) {
    passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/examples/gallery.php').' '.escapeshellarg("{$docs}/gallery.html"), $status);
    if ($status !== 0) {
        exit($status);
    }
}
fwrite(STDERR, "Ran {$count} documentation examples".($svg ? ' and rendered SVGs' : ' (vl-convert not found; no SVGs)').".\n");

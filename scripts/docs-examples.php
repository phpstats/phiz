<?php

declare(strict_types=1);

/*
 * Runs every docs/examples/*.php file and writes what the documentation pages include:
 *
 * - docs/_generated/{name}.md: the example's code and its live chart (the spec it actually produced)
 * - docs/figures/{name}.svg: a static rendering through vl-convert, when it is installed
 * - docs/_phiz-scripts.html: the pinned vega, vega-lite, vega-embed and phiz loader script tags
 * - docs/_generated/gallery.md: the examples/catalog.php gallery, for docs/gallery.qmd, with each chart's spec in
 *   docs/gallery/{id}.json, loaded as the chart scrolls into view
 * - docs/_generated/example-{name}-code.md and -output.md: the scripts in docs/examples/scripts, shown without their
 *   bootstrap, and the chart each one produced when run in an empty directory, in docs/examples/charts/{name}.json
 *   (sized to the page by docs_fit()) or .svg
 *
 * An example file returns a Chart, shown sized to the page by docs_fit(). Its code is shown as written, with `return`
 * shown as `$plot =`; a file named {name}-preview.php shows only its chart, and {name}-hero.php shows the chart first.
 * An example that uses the pipe operator has a method-chain twin, {name}.chain.php; the two are shown in tabs labeled
 * "PHP 8.5+" and "PHP 8.3–8.4", and must build the same specification. Pipe examples need PHP 8.5 to run.
 *
 *     php scripts/docs-examples.php [name ...]
 */

use Phiz\Assets;
use Phiz\Chart;
use Phiz\Export;
use Phiz\Html;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/docs-fit.php';

$docs = dirname(__DIR__).'/docs';
@mkdir("{$docs}/_generated", 0777, true);
@mkdir("{$docs}/figures", 0777, true);
$only = array_slice($argv, 1);
$svg = Export::available();

file_put_contents("{$docs}/_phiz-scripts.html", "<style>.phiz-figure{margin:0.5rem 0 1.5rem;overflow-x:auto}</style>\n".Assets::cdn()->scripts());

$count = 0;
foreach (glob("{$docs}/examples/*.php") as $file) {
    $name = basename($file, '.php');
    if (str_starts_with($name, '_') || str_ends_with($name, '.chain') || ($only !== [] && ! in_array($name, $only, true))) {
        continue;
    }
    $chart = (static fn () => require $file)();
    if (! $chart instanceof Chart) {
        fwrite(STDERR, "{$name}: expected a Chart, got ".get_debug_type($chart)."\n");
        exit(1);
    }
    $chainFile = "{$docs}/examples/{$name}.chain.php";
    if (str_ends_with($name, '-preview')) {
        $markdown = '';
    } elseif (is_file($chainFile)) {
        $chain = (static fn () => require $chainFile)();
        if (! $chain instanceof Chart || $chain->toArray() !== $chart->toArray()) {
            fwrite(STDERR, "{$name}: {$name}.chain.php does not build the same specification\n");
            exit(1);
        }
        $markdown = syntax_tabs(display_code($file), display_code($chainFile));
    } else {
        $markdown = "```php\n".display_code($file)."\n```\n";
    }

    $figure = "```{=html}\n<div class=\"phiz-figure\">"
        .Html::container(docs_fit($chart), ['actions' => false], ['aria-label' => $chart->toArray()['description'] ?? $name])
        ."</div>\n```\n";
    $markdown = str_ends_with($name, '-hero') ? "{$figure}\n{$markdown}" : "{$markdown}\n{$figure}";
    file_put_contents("{$docs}/_generated/{$name}.md", $markdown);
    if ($svg) {
        $chart->save("{$docs}/figures/{$name}.svg");
    }
    $count++;
}

// Source listings of shared example code, for pages that show it whole, without the file's opening comment.
foreach (glob(dirname(__DIR__).'/examples/dashboard.php') as $file) {
    $chainFile = substr($file, 0, -4).'.chain.php';
    $listing = fn (string $path) => trim(preg_replace('/^<\?php\s*(declare\(strict_types=1\);\s*)?(\/\*.*?\*\/\s*)?/s', '', (string) file_get_contents($path)));
    $markdown = is_file($chainFile) ? syntax_tabs($listing($file), $listing($chainFile)) : "```php\n".$listing($file)."\n```\n";
    file_put_contents("{$docs}/_generated/source-".basename($file, '.php').'.md', $markdown);
}

// The example pages: complete scripts, each run in an empty directory, shown without the opening tag and autoloader.
@mkdir("{$docs}/examples/charts", 0777, true);
foreach (glob("{$docs}/examples/scripts/*.php") as $file) {
    $name = basename($file, '.php');
    if (str_ends_with($name, '.chain') || ($only !== [] && ! in_array($name, $only, true))) {
        continue;
    }
    try {
        [$plot, $files] = run_script($file);
        $chainFile = "{$docs}/examples/scripts/{$name}.chain.php";
        [$chainPlot, $chainFiles] = is_file($chainFile) ? run_script($chainFile) : [$plot, $files];
        if ($chainPlot->toArray() !== $plot->toArray() || $chainFiles !== $files) {
            fwrite(STDERR, "examples/scripts/{$name}: the method chain does not write the same files\n");
            exit(1);
        }
    } catch (Phiz\Exceptions\VlConvertNotFound) {
        fwrite(STDERR, "examples/scripts/{$name}: needs vl-convert, so its committed output is kept\n");
        continue;
    }
    $listing = fn (string $path) => trim(preg_replace('/^<\?php\s*(require \'vendor\/autoload\.php\';\s*)?/', '', (string) file_get_contents($path)));
    file_put_contents("{$docs}/_generated/example-{$name}-code.md", is_file($chainFile)
        ? syntax_tabs($listing($file), $listing($chainFile))
        : "```php\n".$listing($file)."\n```\n");

    file_put_contents("{$docs}/examples/charts/{$name}.json", json_encode(docs_fit($plot), Chart::JSON_FLAGS));
    preg_match('/^title: "(.+)"$/m', (string) @file_get_contents("{$docs}/examples/{$name}.qmd"), $title);
    $label = htmlspecialchars($title[1] ?? $name);
    $svgFile = array_key_first(array_filter($files, fn ($bytes, $path) => str_ends_with($path, '.svg'), ARRAY_FILTER_USE_BOTH));
    if ($svgFile !== null) {
        file_put_contents("{$docs}/examples/charts/{$name}.svg", $files[$svgFile]);
        $figure = "<img src=\"charts/{$name}.svg\" alt=\"{$label}\" loading=\"lazy\">";
    } else {
        $figure = Html::deferred("charts/{$name}.json", ['actions' => false], ['aria-label' => $label]);
    }
    file_put_contents("{$docs}/_generated/example-{$name}-output.md", "```{=html}\n<div class=\"phiz-figure\">{$figure}</div>\n```\n");
    $count++;
}

if ($only === []) {
    passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(dirname(__DIR__).'/examples/gallery.php').' '.escapeshellarg("{$docs}/_generated/gallery.md").' '.escapeshellarg("--specs={$docs}/gallery"), $status);
    if ($status !== 0) {
        exit($status);
    }
}
/** An example's code as the page shows it: without the opening tag, with `return` shown as `$plot =`. */
function display_code(string $file): string
{
    $code = preg_replace('/^<\?php\s*(declare\(strict_types=1\);\s*)?/', '', (string) file_get_contents($file));

    return preg_replace('/^return /m', '$plot = ', trim($code));
}

/**
 * Runs a complete example script in an empty directory, as a reader would beside their vendor directory.
 *
 * @return array{0: Chart, 1: array<string, string>} The script's $plot, and the files it wrote by name.
 */
function run_script(string $file): array
{
    $dir = sys_get_temp_dir().'/phiz-example-'.bin2hex(random_bytes(6));
    mkdir($dir);
    symlink(dirname(__DIR__).'/vendor', "{$dir}/vendor");
    $cwd = getcwd();
    chdir($dir);
    try {
        $plot = (static function (string $exampleScript) {
            require $exampleScript;

            return $plot;
        })($file);
    } finally {
        chdir($cwd);
        $files = [];
        foreach (array_diff(scandir($dir), ['.', '..', 'vendor']) as $path) {
            $files[$path] = (string) file_get_contents("{$dir}/{$path}");
            unlink("{$dir}/{$path}");
        }
        unlink("{$dir}/vendor");
        rmdir($dir);
    }
    ksort($files);

    return [$plot, $files];
}

/** Quarto tabs holding the pipe and method-chain versions of the same code. */
function syntax_tabs(string $pipe, string $chain): string
{
    return "::: {.panel-tabset}\n\n#### PHP 8.5+\n\n```php\n{$pipe}\n```\n\n#### PHP 8.3–8.4\n\n```php\n{$chain}\n```\n\n:::\n";
}

fwrite(STDERR, "Ran {$count} documentation examples".($svg ? ' and rendered SVGs' : ' (vl-convert not found; no SVGs)').".\n");

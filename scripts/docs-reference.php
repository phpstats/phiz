<?php

declare(strict_types=1);

/*
 * Writes the API reference fragments that the docs/reference-*.qmd pages include, from the public signatures and
 * docblocks of the classes and functions, so the reference cannot drift from the code. Each page gets
 * docs/_generated/reference-{page}.md: an index of its methods and functions, then one anchored entry for each.
 *
 * Plot's methods are assigned to pages by the section comments in src/Plot.php, and a function that wraps a Plot
 * method goes to that method's page.
 *
 *     php scripts/docs-reference.php
 */

require __DIR__.'/../vendor/autoload.php';

$docs = dirname(__DIR__).'/docs/_generated';
@mkdir($docs, 0777, true);

// Each page: the Plot sections it documents, the other classes, and the functions that wrap no Plot method.
$pages = [
    'plots' => [
        'sections' => ['Construction', 'Mappings and data'],
        'classes' => [Phiz\Data::class, Phiz\Datasets::class, Phiz\Bin::class],
        'functions' => ['aes', 'ggplot', 'field', 'factor', 'calc', 'expr'],
    ],
    'geoms' => ['sections' => ['Geoms'], 'classes' => [], 'functions' => []],
    'scales' => [
        'sections' => ['Scales'],
        'classes' => [],
        'functions' => ['viridis_scheme', 'brewer_scheme', 'guide_*', 'label_*'],
    ],
    'facets' => ['sections' => ['Coordinates and facets'], 'classes' => [Phiz\Compose::class], 'functions' => []],
    'themes' => ['sections' => ['Labels, theme, size'], 'classes' => [], 'functions' => []],
    'interaction' => ['sections' => ['Interaction'], 'classes' => [], 'functions' => []],
    'output' => [
        'sections' => ['Output', 'Escape hatches'],
        'classes' => [Phiz\Html::class, Phiz\Assets::class, Phiz\Export::class],
        'functions' => [],
    ],
    'statistics' => ['sections' => [], 'classes' => [], 'functions' => ['*']],
];

/** A parameter as written in PHP: type, variadic marker, name, and default. */
function reference_parameter(ReflectionParameter $p): string
{
    $out = ($p->hasType() ? $p->getType().' ' : '').($p->isVariadic() ? '...' : '').'$'.$p->getName();
    if ($p->isDefaultValueAvailable()) {
        $default = $p->getDefaultValue();
        $out .= ' = '.match (true) {
            $default === null => 'null',
            $default === [] => '[]',
            is_bool($default) => $default ? 'true' : 'false',
            is_array($default) => str_replace(["\n", '  ', 'array (', ',)', '( '], ['', '', '[', ']', '['], var_export($default, true)),
            default => var_export($default, true),
        };
    }

    return $out;
}

/** A function or method's signature, with one parameter per line when it is too long for one line. */
function reference_signature(ReflectionFunctionAbstract $f, string $prefix = ''): string
{
    $parameters = array_map(reference_parameter(...), $f->getParameters());
    $return = $f->hasReturnType() ? ': '.$f->getReturnType() : '';

    $signature = $prefix.$f->getShortName().'('.implode(', ', $parameters).')'.$return;
    if (strlen($signature) > 88) {
        $signature = $prefix.$f->getShortName()."(\n    ".implode(",\n    ", $parameters).",\n)".$return;
    }

    return preg_replace('/(?<![\w\\\\])Phiz\\\\(?=[A-Z])/', '', $signature);
}

/** The docblock's description as one paragraph, without tags. */
function reference_summary(ReflectionFunctionAbstract $f): string
{
    $doc = (string) $f->getDocComment();
    $lines = array_map(fn ($line) => trim(preg_replace('#^\s*(/\*\*|\*/|\*)\s?#', '', $line)), explode("\n", $doc));
    $text = [];
    foreach ($lines as $line) {
        if (str_starts_with($line, '@')) {
            break;
        }
        $text[] = str_replace('*/', '', $line);
    }

    return reference_escape(trim(preg_replace('/\s+/', ' ', implode(' ', $text))));
}

/** Escapes dollar signs outside code spans, which Pandoc would otherwise read as math. */
function reference_escape(string $text): string
{
    $parts = preg_split('/(`[^`]*`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    return implode('', array_map(fn ($part) => str_starts_with($part, '`') ? $part : preg_replace('/(?<!\\\\)\$/', '\\\\$', $part), $parts));
}

/**
 * A function's description when it has no docblock of its own: a fill or y twin points to its color or x original, an
 * alias to the function it calls, and a pipe wrapper to the method it wraps.
 *
 * @param array<string, string> $wraps Function names => the Plot methods they wrap.
 */
function reference_fallback(ReflectionFunction $f, array $wraps): string
{
    $name = $f->getShortName();
    foreach (['scale_fill_' => ['scale_color_', 'the fill aesthetic'], 'scale_y_' => ['scale_x_', 'the y axis']] as $prefix => [$original, $for]) {
        if (str_starts_with($name, $prefix) && function_exists('Phiz\\'.($twin = $original.substr($name, strlen($prefix))))) {
            return "The [`{$twin}()`](#".reference_function_anchor($twin).") scale for {$for}.";
        }
    }
    $body = implode('', array_slice(file($f->getFileName()), $f->getStartLine() - 1, $f->getEndLine() - $f->getStartLine() + 1));
    if (preg_match('/return (\w+)\(\.\.\.\$args\);/', $body, $m) && function_exists('Phiz\\'.$m[1])) {
        return "An alias of [`{$m[1]}()`](#".reference_function_anchor($m[1]).').';
    }
    if (isset($wraps[$name])) {
        return "The pipe form of [`->{$wraps[$name]}()`](#".reference_anchor($wraps[$name]).').';
    }

    return '';
}

/** An anchor from a method name: scaleX becomes scale-x. */
function reference_anchor(string $name): string
{
    return strtolower(preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', '-', $name));
}

/** A function's anchor. A name without an underscore, such as labs, would collide with the method's anchor. */
function reference_function_anchor(string $name): string
{
    return str_contains($name, '_') ? $name : "{$name}-function";
}

/** One entry: a heading with its anchor, the signature, the description, and any functions that wrap it. */
function reference_entry(string $heading, string $anchor, ReflectionFunctionAbstract $f, string $prefix, array $functions = [], string $fallback = ''): string
{
    $summary = reference_summary($f) ?: $fallback;
    $out = "### `{$heading}` {#{$anchor}}\n\n```php\n".reference_signature($f, $prefix)."\n```\n\n";
    $out .= $summary === '' ? '' : "{$summary}\n\n";
    if ($functions !== []) {
        $links = array_map(fn ($function) => $function['entry'] === null
            ? "[`{$function['name']}()`]{#".reference_function_anchor($function['name']).'}'
            : "[`{$function['name']}()`](#".reference_function_anchor($function['name']).')', $functions);
        $out .= (count($functions) === 1 ? 'Function: ' : 'Functions: ').implode(', ', $links).".\n\n";
    }

    return $out;
}

/** The first sentence of a description, without links, for a table cell. */
function reference_purpose(string $summary): string
{
    $summary = preg_replace('/\[(`[^`]*`)\]\([^)]*\)/', '$1', $summary);
    $first = preg_split('/(?<=[.!?])\s+(?=[A-Z`(])/', $summary, 2)[0] ?? '';

    return str_replace('|', '\\|', trim($first));
}

/** The index line for a group of entries. */
function reference_index(string $label, array $links): string
{
    return $links === [] ? '' : "**{$label}:** ".implode(' · ', $links)."\n\n";
}

/** The public methods a class declares itself, in source order, without those marked @internal. */
function reference_methods(string $class): array
{
    $reflection = new ReflectionClass($class);
    $methods = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() === $class && ! str_starts_with($method->getName(), '__') && ! str_contains((string) $method->getDocComment(), '@internal')) {
            $methods[$method->getStartLine()] = $method;
        }
    }
    ksort($methods);

    return $methods;
}

// Plot's methods by the section comments in its source. The output methods Plot inherits from Chart form a section
// of their own.
$plot = new ReflectionClass(Phiz\Plot::class);
$sections = [];
foreach (file($plot->getFileName()) as $i => $line) {
    if (preg_match('#^    // (.+)\.$#', $line, $m)) {
        $sections[$i + 1] = $m[1];
    }
}
$methods = [];
foreach (reference_methods(Phiz\Plot::class) as $method) {
    $section = 'Construction';
    foreach ($sections as $start => $name) {
        if ($method->getStartLine() > $start) {
            $section = $name;
        }
    }
    $methods[$section][$method->getName()] = $method;
}
foreach (reference_methods(Phiz\Chart::class) as $method) {
    if (! $method->isAbstract() && $method->getName() !== 'jsonSerialize') {
        $methods['Output'][$method->getName()] = $method;
    }
}
$methods['Output'] = ['toArray' => $methods['Escape hatches']['toArray'], 'toSpec' => $methods['Escape hatches']['toSpec']] + $methods['Output'];
unset($methods['Escape hatches']['toArray'], $methods['Escape hatches']['toSpec']);

// The functions, each with the Plot method it wraps, if any.
$wrappers = [];
$wraps = [];
$standalone = [];
foreach (get_defined_functions()['user'] as $name) {
    if (! str_starts_with($name, 'phiz\\')) {
        continue;
    }
    $function = new ReflectionFunction($name);
    if ($function->getShortName() === 'chain') {
        continue; // The helper behind the closures.
    }
    $body = implode('', array_slice(file($function->getFileName()), $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
    $short = $function->getShortName();
    if (preg_match("/chain\\('(\\w+)'/", $body, $m)) {
        // A function that only forwards its arguments needs no entry of its own.
        $plain = reference_summary($function) === '' && $function->getNumberOfParameters() === 1 && $function->isVariadic();
        $wrappers[$m[1]][$function->getStartLine()] = ['name' => $short, 'entry' => $plain ? null : $function];
        $wraps[$short] = $m[1];
    } else {
        $standalone[$function->getStartLine()] = $function;
    }
}
ksort($standalone);

// The rows of the functions-and-methods index, by page: the functions, the method, and the purpose.
$indexRows = [];
$counts = ['functions' => 0, 'plot methods' => 0, 'class methods' => 0];

foreach ($pages as $page => $spec) {
    $index = '';
    $body = '';
    $multiple = count($spec['sections']) + count($spec['classes']) > 1;
    $functions = [];

    foreach ($spec['sections'] as $section) {
        $links = [];
        $entries = '';
        foreach ($methods[$section] ?? [] as $name => $method) {
            $static = $method->isStatic();
            $heading = $static ? "Plot::{$name}()" : "{$name}()";
            $anchor = reference_anchor($name);
            $wrapping = $wrappers[$name] ?? [];
            ksort($wrapping);
            $links[] = "[`{$heading}`](#{$anchor})";
            $entries .= reference_entry($heading, $anchor, $method, $static ? 'Plot::' : '->', $wrapping);
            foreach ($wrapping as $line => $function) {
                $functions[$line] = $function['entry'] ?? $function['name'];
            }
            $indexRows[$page][] = [
                'functions' => array_map(fn ($function) => "[`{$function['name']}()`](reference-{$page}.qmd#".reference_function_anchor($function['name']).')', array_values($wrapping)),
                'method' => "[`".($static ? "Plot::{$name}()" : "->{$name}()")."`](reference-{$page}.qmd#{$anchor})",
                'purpose' => reference_purpose(reference_summary($method)),
            ];
            $counts['plot methods']++;
            $counts['functions'] += count($wrapping);
        }
        $index .= reference_index($multiple ? $section : 'Methods', $links);
        $body .= ($multiple ? "## {$section}\n\n" : '').$entries;
    }

    foreach ($spec['classes'] as $class) {
        $short = (new ReflectionClass($class))->getShortName();
        $links = [];
        $entries = '';
        foreach (reference_methods($class) as $method) {
            $name = $method->getName();
            $heading = $method->isStatic() ? "{$short}::{$name}()" : '$'.lcfirst($short)."->{$name}()";
            $anchor = strtolower($short).'-'.reference_anchor($name);
            $links[] = "[`{$heading}`](#{$anchor})";
            $entries .= reference_entry($heading, $anchor, $method, $method->isStatic() ? "{$short}::" : '->');
            $indexRows[$page][] = ['functions' => [], 'method' => "[`{$heading}`](reference-{$page}.qmd#{$anchor})", 'purpose' => reference_purpose(reference_summary($method))];
            $counts['class methods']++;
        }
        $index .= reference_index($short, $links);
        $body .= "## {$short} {#class-".strtolower($short)."}\n\n".$entries;
    }

    foreach ($standalone as $line => $function) {
        foreach ($spec['functions'] as $pattern) {
            if (fnmatch($pattern, $function->getShortName())) {
                $functions[$line] = $function;
                unset($standalone[$line]);
                break;
            }
        }
    }
    if ($functions !== []) {
        ksort($functions);
        $links = [];
        $entries = '';
        foreach ($functions as $function) {
            $name = is_string($function) ? $function : $function->getShortName();
            $anchor = reference_function_anchor($name);
            $links[] = "[`{$name}()`](#{$anchor})";
            if (! is_string($function)) {
                $entries .= reference_entry("{$name}()", $anchor, $function, '', fallback: reference_fallback($function, $wraps));
            }
            if (! is_string($function) && ! isset($wraps[$name])) {
                $link = "[`{$name}()`](reference-{$page}.qmd#{$anchor})";
                $counts['functions']++;
                // A function that starts a plot, as ggplot() does, pairs with Plot::of().
                $source = implode('', array_slice(file($function->getFileName()), $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
                $of = array_key_first(array_filter($indexRows[$page] ?? [], fn ($row) => str_contains($row['method'] ?? '', '`Plot::of()`')));
                if ($of !== null && str_contains($source, 'Plot::of(')) {
                    $indexRows[$page][$of]['functions'][] = $link;
                } else {
                    $indexRows[$page][] = ['functions' => [$link], 'method' => null, 'purpose' => reference_purpose(reference_summary($function) ?: reference_fallback($function, $wraps))];
                }
            }
        }
        $index .= reference_index('Functions', $links);
        if ($entries !== '') {
            $body .= ($spec['sections'] === [] && $spec['classes'] === [] ? '' : "## Functions\n\n").$entries;
        }
    }

    file_put_contents("{$docs}/reference-{$page}.md", $index.$body);
}

if ($standalone !== []) {
    fwrite(STDERR, 'Functions on no reference page: '.implode(', ', array_map(fn ($f) => $f->getShortName(), $standalone))."\n");
    exit(1);
}
// The index: one table per reference page, titled as the page is.
$markdown = '';
foreach ($indexRows as $page => $rows) {
    preg_match('/^title: "(.+?)(?: API)?"$/m', (string) file_get_contents(dirname($docs)."/reference-{$page}.qmd"), $title);
    $markdown .= '## ['.($title[1] ?? $page)."](reference-{$page}.qmd)\n\n| Function | Method | Purpose |\n| --- | --- | --- |\n";
    foreach ($rows as $row) {
        $markdown .= '| '.(implode(', ', $row['functions']) ?: '')." | {$row['method']} | {$row['purpose']} |\n";
    }
    $markdown .= "\n";
}
file_put_contents("{$docs}/reference-index.md", $markdown);
fwrite(STDERR, sprintf("Indexed %d functions, %d Plot methods, and %d class methods.\n", $counts['functions'], $counts['plot methods'], $counts['class methods']));

foreach (['plot', 'classes', 'functions'] as $old) {
    @unlink("{$docs}/reference-{$old}.md");
}

fwrite(STDERR, "Wrote the API reference fragments.\n");

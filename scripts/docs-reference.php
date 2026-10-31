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

    return trim(preg_replace('/\s+/', ' ', implode(' ', $text)));
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
function reference_entry(string $heading, string $anchor, ReflectionFunctionAbstract $f, string $prefix, array $functions = []): string
{
    $summary = reference_summary($f);
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

/** The index line for a group of entries. */
function reference_index(string $label, array $links): string
{
    return $links === [] ? '' : "**{$label}:** ".implode(' · ', $links)."\n\n";
}

/** The public methods a class declares itself, in source order. */
function reference_methods(string $class): array
{
    $reflection = new ReflectionClass($class);
    $methods = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() === $class && ! str_starts_with($method->getName(), '__')) {
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
    } else {
        $standalone[$function->getStartLine()] = $function;
    }
}
ksort($standalone);

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
                $entries .= reference_entry("{$name}()", $anchor, $function, '');
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
foreach (['plot', 'classes', 'functions'] as $old) {
    @unlink("{$docs}/reference-{$old}.md");
}

fwrite(STDERR, "Wrote the API reference fragments.\n");

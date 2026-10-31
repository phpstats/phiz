<?php

declare(strict_types=1);

/*
 * Writes the API reference fragments that docs/reference.qmd includes, from the public signatures and docblocks of
 * the classes and functions, so the reference cannot drift from the code:
 *
 * - docs/_generated/reference-plot.md: Plot's methods, grouped by the section comments in src/Plot.php
 * - docs/_generated/reference-classes.md: Compose, Data, Export, Html, and Bin
 * - docs/_generated/reference-functions.md: the functions in the Phiz namespace
 *
 *     php scripts/docs-reference.php
 */

require __DIR__.'/../vendor/autoload.php';

$docs = dirname(__DIR__).'/docs/_generated';
@mkdir($docs, 0777, true);

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

/** A function or method's signature on one line. */
function reference_signature(ReflectionFunctionAbstract $f, string $prefix = ''): string
{
    $parameters = implode(', ', array_map(reference_parameter(...), $f->getParameters()));

    $signature = $prefix.$f->getShortName()."({$parameters})".($f->hasReturnType() ? ': '.$f->getReturnType() : '');

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

/** One entry: the signature as code, then its description. */
function reference_entry(ReflectionFunctionAbstract $f, string $prefix = ''): string
{
    $summary = reference_summary($f);

    return "```php\n".reference_signature($f, $prefix)."\n```\n\n".($summary === '' ? '' : "{$summary}\n\n");
}

// Plot, by the section comments in its source.
$plot = new ReflectionClass(Phiz\Plot::class);
$source = file($plot->getFileName());
$sections = [];
foreach ($source as $i => $line) {
    if (preg_match('#^    // (.+)\.$#', $line, $m)) {
        $sections[$i + 1] = $m[1];
    }
}
$grouped = [];
foreach ($plot->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
    if ($method->getDeclaringClass()->getName() !== Phiz\Plot::class || str_starts_with($method->getName(), '__')) {
        continue;
    }
    $section = 'Construction';
    foreach ($sections as $start => $name) {
        if ($method->getStartLine() > $start) {
            $section = $name;
        }
    }
    $grouped[$section][$method->getStartLine()] = reference_entry($method, $method->isStatic() ? 'Plot::' : '->');
}
$markdown = '';
foreach ($grouped as $section => $entries) {
    ksort($entries);
    $markdown .= "### {$section}\n\n".implode('', $entries);
}
file_put_contents("{$docs}/reference-plot.md", $markdown);

// Other classes.
$markdown = '';
foreach ([Phiz\Compose::class, Phiz\Data::class, Phiz\Export::class, Phiz\Html::class, Phiz\Bin::class] as $class) {
    $reflection = new ReflectionClass($class);
    $short = $reflection->getShortName();
    $markdown .= "### {$short}\n\n";
    $entries = [];
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() !== $class || str_starts_with($method->getName(), '__')) {
            continue;
        }
        $entries[$method->getStartLine()] = reference_entry($method, $method->isStatic() ? "{$short}::" : '->');
    }
    ksort($entries);
    $markdown .= implode('', $entries);
}
file_put_contents("{$docs}/reference-classes.md", $markdown);

// Functions.
$functions = array_filter(get_defined_functions()['user'], fn ($name) => str_starts_with($name, 'phiz\\'));
$entries = [];
foreach ($functions as $name) {
    $function = new ReflectionFunction($name);
    if ($function->getShortName() === 'chain') {
        continue; // The helper behind the closures below.
    }
    $summary = reference_summary($function);
    $body = implode('', array_slice(file($function->getFileName()), $function->getStartLine() - 1, $function->getEndLine() - $function->getStartLine() + 1));
    if ($summary === '' && preg_match("/chain\\('(\\w+)'/", $body, $m)) {
        $summary = "`->{$m[1]}()` as a closure, for the pipe operator and `apply()`.";
    }
    $entries[$function->getStartLine()] = '- `'.reference_signature($function).'`'.($summary === '' ? '' : ": {$summary}")."\n";
}
ksort($entries);
file_put_contents("{$docs}/reference-functions.md", implode('', $entries)."\n");

fwrite(STDERR, "Wrote the API reference fragments.\n");

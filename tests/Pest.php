<?php

declare(strict_types=1);

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Phiz\Chart;

/*
 * Specs are validated against the Vega-Lite 6.1.0 JSON schema (tests/fixtures), the version vl-convert renders
 * with, so every spec works both in the browser and in static export.
 */
function vegaLiteValidator(): array
{
    static $state = null;
    if ($state === null) {
        $validator = new Validator();
        $validator->setMaxErrors(5);
        $schema = json_decode((string) file_get_contents(__DIR__.'/fixtures/vega-lite-v6.1.0.schema.json'));
        $validator->resolver()->registerRaw($schema, 'https://phiz.test/vega-lite.json');
        $state = [$validator, 'https://phiz.test/vega-lite.json'];
    }

    return $state;
}

/** @return list<string> Schema errors, empty when valid. */
function vegaLiteErrors(array $spec): array
{
    [$validator, $id] = vegaLiteValidator();
    $data = json_decode(json_encode($spec, Chart::JSON_FLAGS));
    $result = $validator->validate($data, $id);
    if ($result->isValid()) {
        return [];
    }
    $errors = [];
    foreach ((new ErrorFormatter())->formatFlat($result->error()) as $message) {
        $errors[] = $message;
    }

    return $errors;
}

expect()->extend('toBeValidVegaLite', function () {
    $spec = $this->value instanceof Chart ? $this->value->toArray() : $this->value;
    $errors = vegaLiteErrors($spec);
    expect($errors)->toBe([], 'Invalid Vega-Lite: '.implode('; ', array_slice($errors, 0, 5))."\n".json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $this;
});

/** The spec as stable pretty JSON, with inline data cut to its first rows to keep snapshots readable. */
function specSnapshot(Chart|array $chart, int $rows = 3): string
{
    $spec = $chart instanceof Chart ? $chart->toArray() : $chart;
    array_walk_recursive($spec, function (&$value) {
        if ($value instanceof stdClass) {
            $value = (array) $value;
        }
    });
    $trim = function (array $node) use (&$trim, $rows): array {
        foreach ($node as $key => $value) {
            if ($key === 'values' && is_array($value) && array_is_list($value) && count($value) > $rows && is_array($value[0] ?? null)) {
                $node[$key] = [...array_slice($value, 0, $rows), '... '.(count($value) - $rows).' more rows'];
            } elseif (is_array($value)) {
                $node[$key] = $trim($value);
            }
        }

        return $node;
    };

    return json_encode($trim($spec), Chart::JSON_FLAGS | JSON_PRETTY_PRINT)."\n";
}

/** A small deterministic dataset for spec tests. */
function sample(): array
{
    return [
        ['x' => 1, 'y' => 2.5, 'g' => 'a', 'h' => 'p', 'n' => 3, 'date' => '2024-01-01', 'lo' => 2.0, 'hi' => 3.0],
        ['x' => 2, 'y' => 3.1, 'g' => 'b', 'h' => 'q', 'n' => 5, 'date' => '2024-02-01', 'lo' => 2.6, 'hi' => 3.7],
        ['x' => 3, 'y' => 2.2, 'g' => 'a', 'h' => 'q', 'n' => 2, 'date' => '2024-03-01', 'lo' => 1.5, 'hi' => 2.9],
        ['x' => 4, 'y' => 4.8, 'g' => 'b', 'h' => 'p', 'n' => 7, 'date' => '2024-04-01', 'lo' => 4.0, 'hi' => 5.5],
        ['x' => 5, 'y' => 5.0, 'g' => 'c', 'h' => 'p', 'n' => 4, 'date' => '2024-05-01', 'lo' => 4.1, 'hi' => 5.9],
        ['x' => 6, 'y' => 6.3, 'g' => 'c', 'h' => 'q', 'n' => 6, 'date' => '2024-06-01', 'lo' => 5.5, 'hi' => 7.0],
    ];
}

pest()->extend(Phiz\Tests\Laravel\TestCase::class)->in('Laravel');

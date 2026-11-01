<?php

declare(strict_types=1);

/*
 * Runs the Pest suite and writes the verification records built from it to site/verifications/: Vega-Lite schema
 * validity, spec snapshots, pipe and method-chain equivalence, and the vl-convert export checks. Each case is one
 * test, with every data set counted; a case passes only when all of its runs pass, and skipped runs are listed as
 * skipped. Export checks need vl-convert (PHIZ_VL_CONVERT or PATH) and the GD extension, or they are recorded as
 * skipped.
 *
 *     php scripts/evidence-tests.php
 */

use Composer\InstalledVersions;
use Phiz\Assets;
use Phiz\Export;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/evidence/environment.php';

$root = dirname(__DIR__);
$junit = tempnam(sys_get_temp_dir(), 'phiz-junit');
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg("{$root}/vendor/bin/pest").' --log-junit '.escapeshellarg($junit).' > /dev/null', $status);

// Each test's description, file, and body, from the test sources, so a record can select tests by what they assert.
$tests = [];
foreach (glob("{$root}/tests/*/*Test.php") as $file) {
    $source = (string) file_get_contents($file);
    preg_match_all("/^\\s*it\\('((?:\\\\'|[^'])*)'/m", $source, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[1] as $i => [$description, $offset]) {
        $end = $matches[0][$i + 1][1] ?? strlen($source);
        $tests[str_replace("\\'", "'", $description)] = ['file' => substr($file, strlen($root) + 1), 'body' => substr($source, $offset, $end - $offset)];
    }
}

// The runs of each test from the JUnit log.
$runs = [];
foreach (simplexml_load_file($junit)->xpath('//testcase') as $case) {
    $name = html_entity_decode((string) $case['name'], ENT_QUOTES);
    $name = preg_replace('/^`[^`]*` → /u', '', $name);
    $name = preg_replace('/ with data set .*$/s', '', preg_replace('/^it /', '', $name));
    $state = isset($case->failure) || isset($case->error) ? 'fail' : (isset($case->skipped) ? 'skipped' : 'pass');
    $runs[$name][] = ['state' => $state, 'message' => trim((string) ($case->failure ?? $case->error ?? $case->skipped ?? ''))];
}
unlink($junit);

/** The cases for the tests a filter selects: one per test, plus one for any of its runs that were skipped or failed. */
function evidence_test_cases(array $tests, array $runs, callable $select, string $rule): array
{
    $cases = [];
    foreach ($tests as $description => $test) {
        if (! $select($description, $test) || ! isset($runs[$description])) {
            continue;
        }
        $name = basename($test['file'], '.php');
        $by = [];
        foreach ($runs[$description] as $run) {
            $by[$run['state']][] = $run['message'];
        }
        foreach (['pass', 'fail', 'skipped'] as $state) {
            if (! isset($by[$state])) {
                continue;
            }
            $case = ['name' => $name, 'quantity' => $description, 'status' => $state, 'rule' => $rule, 'n_compared' => count($by[$state])];
            if ($state !== 'pass') {
                $message = trim(strtok($by[$state][0] ?: ($state === 'fail' ? 'The test failed.' : 'The test was skipped.'), "\n"));
                $case['reason'] = rtrim($message, '.').'.';
            }
            $cases[] = $case;
        }
    }

    return $cases;
}

$environment = evidence_environment();
$common = [
    'reproduce' => ['composer install', 'php scripts/evidence-tests.php'],
    'source' => 'scripts/evidence-tests.php',
    'environment' => $environment,
];
$records = [];

$records['vega-lite-schema'] = [
    'title' => 'Vega-Lite schema validity',
    'group' => 'Specifications',
    'label' => 'Vega-Lite schema',
    'kind' => 'contract',
    'checked' => 'Every specification the tests build, for each geom, feature, statistics helper, documentation example, and gallery example, validates against the Vega-Lite JSON schema.',
    'reference' => 'Vega-Lite 6.1.0 JSON schema, validated with opis/json-schema',
    'versions' => [
        'Vega-Lite schema' => '6.1.0',
        'vega-lite (browser)' => Assets::VEGA_LITE,
        'vega (browser)' => Assets::VEGA,
        'vega-embed (browser)' => Assets::VEGA_EMBED,
        'vl-convert (tested)' => '1.9.0',
        'opis/json-schema' => InstalledVersions::getPrettyVersion('opis/json-schema'),
    ],
    'inputs' => [['name' => 'schema', 'detail' => 'tests/fixtures/vega-lite-v6.1.0.schema.json', 'url' => 'tests/fixtures/vega-lite-v6.1.0.schema.json']],
    'comparison' => [['id' => 'schema', 'applies_to' => 'each specification against the schema', 'kind' => 'exact']],
    'cases' => evidence_test_cases($tests, $runs, fn ($d, $t) => str_contains($t['body'], 'toBeValidVegaLite'), 'schema'),
    'known_differences' => ['Browsers load vega-lite '.Assets::VEGA_LITE.', but specifications are validated against the 6.1.0 schema, the version vl-convert 1.9.0 renders with. No check validates against a 6.4 schema.'],
] + $common;

$records['spec-snapshots'] = [
    'title' => 'Specification snapshots',
    'group' => 'Specifications',
    'label' => 'Spec snapshots',
    'kind' => 'contract',
    'checked' => 'Each compiled specification matches its committed snapshot exactly. A change to the output fails until the snapshot is reviewed and updated.',
    'reference' => 'The committed snapshots in tests/.pest/snapshots ('.count(glob("{$root}/tests/.pest/snapshots/*/*/*.snap")).' files)',
    'inputs' => 'The bundled mpg, mtcars, economics, and faithful datasets, and the gallery catalog in examples/catalog.php.',
    'comparison' => [['id' => 'snapshot', 'applies_to' => 'the specification JSON, with inline data cut to its first three rows and a count of the rest', 'kind' => 'exact']],
    'cases' => evidence_test_cases($tests, $runs, fn ($d, $t) => str_contains($t['body'], 'toMatchSnapshot'), 'snapshot'),
] + $common;

$records['pipe-chain-equivalence'] = [
    'title' => 'Pipe and method-chain equivalence',
    'group' => 'Specifications',
    'label' => 'Pipe and method chain',
    'kind' => 'contract',
    'checked' => 'Every documentation example written with the pipe operator and its method-chain version build the same specification, and the dashboard\'s two versions build the same charts.',
    'reference' => 'The method-chain versions, run on the same PHP version',
    'inputs' => 'docs/examples/*.chain.php, docs/examples/scripts/*.chain.php, and examples/dashboard.chain.php.',
    'comparison' => [['id' => 'same', 'applies_to' => 'the two specifications, compared as PHP arrays', 'kind' => 'exact']],
    'cases' => evidence_test_cases($tests, $runs, fn ($d, $t) => in_array(basename($t['file']), ['ChainExamplesTest.php', 'DashboardTest.php', 'PipeTest.php'], true), 'same'),
    'exclusions' => ['The pipe operator needs PHP 8.5, so tests/Pipes runs only on PHP 8.5.'],
] + $common;

$vlConvert = Export::available() ? trim((string) shell_exec(escapeshellarg((string) (getenv('PHIZ_VL_CONVERT') ?: 'vl-convert')).' --version 2>/dev/null')) : null;
$records['export-checks'] = [
    'title' => 'Export checks with vl-convert',
    'group' => 'Export',
    'label' => 'Export',
    'kind' => 'integration',
    'checked' => 'vl-convert renders every gallery example to SVG, writes SVG, PNG, and PDF files with the right signatures, reports conversion errors, and renders 21 plots as PNG images that match their baselines.',
    'reference' => 'vl-convert '.($vlConvert ? preg_replace('/^vl-convert\s*/', '', $vlConvert) : 'not found').', and the PNG baselines in tests/Visual/baselines',
    'versions' => ['vl-convert' => $vlConvert ? preg_replace('/^vl-convert\s*/', '', $vlConvert) : null, 'Vega-Lite (vl-convert)' => '6.1'],
    'inputs' => [['name' => 'gallery', 'detail' => '46 examples in examples/catalog.php', 'url' => 'examples/catalog.php'], ['name' => 'baselines', 'detail' => '21 PNG images', 'url' => 'tests/Visual/baselines']],
    'comparison' => [
        ['id' => 'smoke', 'applies_to' => 'SVG output: starts with <svg and contains a mark group (role-mark)', 'kind' => 'exact'],
        ['id' => 'signature', 'applies_to' => 'file signatures: <svg, the PNG magic bytes, %PDF-, and a Vega spec with marks; conversion errors raise ExportFailed', 'kind' => 'exact'],
        ['id' => 'pixels', 'applies_to' => 'PNG renderings: at most 0.2% of pixels may differ from the baseline by more than 40 of 255 in their largest channel; a size change fails', 'kind' => 'exact'],
    ],
    'cases' => [
        ...evidence_test_cases($tests, $runs, fn ($d) => $d === 'renders every gallery example to SVG with vl-convert', 'smoke'),
        ...evidence_test_cases($tests, $runs, fn ($d) => in_array($d, ['renders SVG, PNG, PDF, and Vega', 'reports conversion errors'], true), 'signature'),
        ...evidence_test_cases($tests, $runs, fn ($d, $t) => basename($t['file']) === 'VisualTest.php', 'pixels'),
    ],
    'exclusions' => ['PNG and PDF output are checked by signature and by the visual baselines, not byte for byte.', 'The visual plots use Liberation Sans, which vl-convert bundles, and no jitter, so they render the same on every platform.'],
] + $common;

@mkdir("{$root}/site/verifications", 0777, true);
foreach ($records as $id => $record) {
    file_put_contents("{$root}/site/verifications/{$id}.json", json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    $states = array_count_values(array_column($record['cases'], 'status'));
    fwrite(STDERR, sprintf("%s: %d cases (%s)\n", $id, count($record['cases']), http_build_query($states, '', ', ')));
}
exit($status === 0 ? 0 : 1);

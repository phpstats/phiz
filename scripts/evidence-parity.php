<?php

declare(strict_types=1);

/*
 * Writes the agreement-with-R verification records to site/verifications/: phiz's values against the references in
 * tests/fixtures/r-references.json, which scripts/evidence/r-references.R computes with R. Exits 1 if a case fails.
 *
 *     php scripts/evidence-parity.php
 */

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/evidence/environment.php';
require __DIR__.'/evidence/parity.php';

$root = dirname(__DIR__);
$fixture = json_decode((string) file_get_contents("{$root}/tests/fixtures/r-references.json"), true, flags: JSON_THROW_ON_ERROR);
$environment = evidence_environment();
$failed = 0;

@mkdir("{$root}/site/verifications", 0777, true);
foreach (evidence_parity($fixture) as $id => $record) {
    $record = ['title' => $record['title'], 'label' => $record['label'], 'kind' => 'reference'] + $record + [
        'reproduce' => ['Rscript scripts/evidence/r-references.R', 'php scripts/evidence-parity.php'],
        'source' => "site/verifications/{$id}.json",
        'data' => 'tests/fixtures/r-references.json',
        'environment' => $environment,
        'group' => 'Agreement with R',
    ];
    file_put_contents("{$root}/site/verifications/{$id}.json", json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n");
    $states = array_count_values(array_column($record['cases'], 'status'));
    $failed += $states['fail'] ?? 0;
    fwrite(STDERR, sprintf("%s: %d cases (%s)\n", $id, count($record['cases']), http_build_query($states, '', ', ')));
}
exit($failed === 0 ? 0 : 1);

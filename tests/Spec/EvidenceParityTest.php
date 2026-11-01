<?php

declare(strict_types=1);

/*
 * The agreement-with-R checks behind site/verifications (scripts/evidence/parity.php), against the references that
 * scripts/evidence/r-references.R computed. Every case must pass.
 */

require_once __DIR__.'/../../scripts/evidence/parity.php';

dataset('parity', function () {
    $records = evidence_parity(json_decode((string) file_get_contents(__DIR__.'/../fixtures/r-references.json'), true));
    foreach ($records as $id => $record) {
        foreach ($record['cases'] as $case) {
            yield "{$id}: {$case['name']}, {$case['quantity']}" => [$case];
        }
    }
});

it('agrees with R', function (array $case) {
    expect($case['status'])->toBe('pass', $case['reason'] ?? '');
})->with('parity');

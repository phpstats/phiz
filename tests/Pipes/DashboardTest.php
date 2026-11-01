<?php

declare(strict_types=1);

use Phibble\DataFrame;
use Phiz\Datasets;

require_once __DIR__.'/../../examples/dashboard.php';

it('builds the dashboard charts from phplyr summaries of a phibble table', function () {
    $charts = fuel_dashboard(DataFrame::fromRows(Datasets::mpg()), drive: 'f', year: 2008, selected: 'compact');
    $classes = array_column($charts['summary']->toRows(), 'class');

    expect($classes)->toBe(['compact', 'subcompact', 'midsize', 'minivan'])
        ->and($charts['bars'])->toBeValidVegaLite()
        ->and($charts['scatter'])->toBeValidVegaLite()
        ->and($charts['trend'])->toBeValidVegaLite()
        ->and(array_unique(array_column($charts['trend']->data->rows, 'year')))->toEqualCanonicalizing([1999, 2008]);
});

it('builds the same dashboard with pipes and with method chains', function () {
    $examples = __DIR__.'/../../examples';

    expect(dashboardCharts("{$examples}/dashboard.php"))->toBe(dashboardCharts("{$examples}/dashboard.chain.php"));
});

it('builds the same documentation examples with pipes and with method chains', function () {
    foreach (glob(__DIR__.'/../../docs/examples/*.chain.php') as $chain) {
        $pipe = (static fn () => require str_replace('.chain.php', '.php', $chain))();

        expect((static fn () => require $chain)()->toArray())->toBe($pipe->toArray());
    }
});

/**
 * Runs an example-page script in an empty directory beside the vendor directory, as a reader would.
 *
 * @return array{0: array<string, mixed>, 1: array<string, string>} The script's spec, and the files it wrote by name.
 */
function runExampleScript(string $file): array
{
    $dir = sys_get_temp_dir().'/phiz-script-'.bin2hex(random_bytes(6));
    mkdir($dir);
    symlink(dirname(__DIR__, 2).'/vendor', "{$dir}/vendor");
    $cwd = getcwd();
    chdir($dir);
    try {
        $spec = (static function (string $exampleScript) {
            require $exampleScript;

            return $plot->toArray();
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

    return [$spec, $files];
}

it('runs each example-page script and its method-chain version to the same chart and files', function (string $chain) {
    $pipe = str_replace('.chain.php', '.php', $chain);
    if (str_contains((string) file_get_contents($pipe), '.svg') && ! Phiz\Export::available()) {
        $this->markTestSkipped('This script writes images, which need vl-convert.');
    }

    expect(runExampleScript($chain))->toBe(runExampleScript($pipe));
})->with(array_map(fn ($file) => [$file], glob(__DIR__.'/../../docs/examples/scripts/*.chain.php')));

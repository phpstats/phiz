<?php

declare(strict_types=1);

namespace Phiz;

use Phiz\Exceptions\ExportFailed;
use Phiz\Exceptions\VlConvertNotFound;

/**
 * Static rendering through the vl-convert binary (https://github.com/vega/vl-convert): SVG, PNG, JPEG and PDF
 * without a browser, for reports, email and print.
 *
 * The binary is found through the PHIZ_VL_CONVERT or VL_CONVERT environment variables, then PATH, or given to the
 * constructor. vl-convert bundles its own Vega-Lite, pinned here to Assets::VL_CONVERT_VEGA_LITE.
 */
final class Export
{
    public function __construct(
        private readonly ?string $binary = null,
        private readonly float $timeout = 60.0,
        private readonly ?string $fontDir = null,
    ) {}

    /** The vl-convert binary path, or null when none is found. */
    public static function find(): ?string
    {
        foreach (['PHIZ_VL_CONVERT', 'VL_CONVERT'] as $variable) {
            $value = getenv($variable);
            if (is_string($value) && $value !== '' && is_executable($value)) {
                return $value;
            }
        }
        $names = PHP_OS_FAMILY === 'Windows' ? ['vl-convert.exe', 'vl-convert'] : ['vl-convert'];
        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            foreach ($names as $name) {
                $path = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name;
                if ($dir !== '' && is_file($path) && is_executable($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    public static function available(): bool
    {
        return self::find() !== null;
    }

    public function svg(Chart|array $chart): string
    {
        return $this->run('vl2svg', 'svg', $chart);
    }

    /** PNG bytes; scale multiplies the pixel size (2 for high-DPI screens), ppi sets the embedded resolution. */
    public function png(Chart|array $chart, float $scale = 2.0, ?int $ppi = null): string
    {
        if (! ($scale > 0)) {
            throw new \InvalidArgumentException('scale must be positive.');
        }

        return $this->run('vl2png', 'png', $chart, ['--scale', (string) $scale, ...($ppi === null ? [] : ['--ppi', (string) $ppi])]);
    }

    public function jpeg(Chart|array $chart, float $scale = 2.0, ?int $quality = null): string
    {
        return $this->run('vl2jpeg', 'jpeg', $chart, ['--scale', (string) $scale, ...($quality === null ? [] : ['--quality', (string) $quality])]);
    }

    public function pdf(Chart|array $chart): string
    {
        return $this->run('vl2pdf', 'pdf', $chart);
    }

    /** The compiled Vega (not Vega-Lite) spec. */
    public function vega(Chart|array $chart): array
    {
        return json_decode($this->run('vl2vg', 'json', $chart), true, 512, JSON_THROW_ON_ERROR);
    }

    /** Writes the chart to a file whose extension picks the format: svg, png, jpg/jpeg, pdf, html, or json. */
    public function save(Chart|array $chart, string $path, float $scale = 2.0): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contents = match ($extension) {
            'svg' => $this->svg($chart),
            'png' => $this->png($chart, $scale),
            'jpg', 'jpeg' => $this->jpeg($chart, $scale),
            'pdf' => $this->pdf($chart),
            'json' => json_encode(self::spec($chart), Chart::JSON_FLAGS | JSON_PRETTY_PRINT)."\n",
            'html', 'htm' => $chart instanceof Chart ? $chart->html() : Html::page($chart),
            default => throw new \InvalidArgumentException("Cannot tell the format from '{$path}'; use .svg, .png, .jpg, .pdf, .html, or .json."),
        };
        if (file_put_contents($path, $contents) === false) {
            throw new ExportFailed("Cannot write {$path}.");
        }

        return $path;
    }

    private static function spec(Chart|array $chart): array
    {
        return $chart instanceof Chart ? $chart->toArray() : $chart;
    }

    private function run(string $command, string $extension, Chart|array $chart, array $extra = []): string
    {
        $binary = $this->binary ?? self::find() ?? throw new VlConvertNotFound();
        $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phiz-'.bin2hex(random_bytes(8));
        $input = "{$base}.vl.json";
        $output = "{$base}.{$extension}";
        try {
            if (file_put_contents($input, json_encode(self::spec($chart), Chart::JSON_FLAGS)) === false) {
                throw new ExportFailed('Cannot write the spec to a temporary file.');
            }
            $args = [$binary, $command, '--input', $input, '--output', $output, '--vl-version', Assets::VL_CONVERT_VEGA_LITE, ...$extra];
            if ($this->fontDir !== null) {
                array_push($args, '--font-dir', $this->fontDir);
            }
            $this->exec($args);
            $contents = is_file($output) ? file_get_contents($output) : false;
            if ($contents === false) {
                throw new ExportFailed("vl-convert {$command} produced no output.");
            }

            return $contents;
        } finally {
            foreach ([$input, $output] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function exec(array $args): void
    {
        $process = proc_open($args, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new ExportFailed('Cannot start vl-convert.');
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stderr = '';
        $deadline = microtime(true) + $this->timeout;
        while (true) {
            $stderr .= (string) stream_get_contents($pipes[2]);
            stream_get_contents($pipes[1]);
            $status = proc_get_status($process);
            if (! $status['running']) {
                break;
            }
            if (microtime(true) > $deadline) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                throw new ExportFailed("vl-convert timed out after {$this->timeout} seconds.");
            }
            usleep(10_000);
        }
        $stderr .= (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        if ($status['exitcode'] !== 0) {
            throw new ExportFailed('vl-convert failed (exit '.$status['exitcode'].'): '.trim($stderr));
        }
    }
}

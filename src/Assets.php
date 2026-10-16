<?php

declare(strict_types=1);

namespace Phiz;

/**
 * The browser libraries a rendered plot needs: vega, vega-lite and vega-embed, pinned to tested versions.
 *
 * cdn() loads them from jsDelivr with subresource integrity; local() points at copies you serve yourself (the
 * build/*.min.js files of the same npm versions); inline() embeds files from disk for fully offline HTML.
 */
final class Assets
{
    public const SCHEMA = 'https://vega.github.io/schema/vega-lite/v6.json';

    public const VEGA = '6.4.0';

    public const VEGA_LITE = '6.4.3';

    public const VEGA_EMBED = '7.3.0';

    /** The Vega-Lite version vl-convert compiles with for static export. */
    public const VL_CONVERT_VEGA_LITE = '6.1';

    private const INTEGRITY = [
        'vega' => 'sha384-VKdcJr3ZaBIJMbVcopTAI/JEuUkSY6qnwVu9iLuw0DnQ9gQ1JjsfZJhXFQAgNi43',
        'vega-lite' => 'sha384-9/70gNCfOu6G7xXvkdreMfuqAEsoaGJVXV2BN/JLRXkSmcGvnMqtsRx8HZtUWAvI',
        'vega-embed' => 'sha384-Muy1QRxYFeNrA1zShc1KtN4OfipkR/61gt+HWqN57c1Zxu3Oe16TsilsruVVjfKL',
    ];

    /**
     * @param array<string, string> $sources Library => URL, or file contents when $inline.
     * @param array<string, string> $integrity Library => SRI hash.
     */
    private function __construct(
        private readonly array $sources,
        private readonly array $integrity = [],
        private readonly bool $inline = false,
    ) {}

    public static function cdn(string $base = 'https://cdn.jsdelivr.net/npm'): self
    {
        $base = rtrim($base, '/');

        return new self([
            'vega' => "{$base}/vega@".self::VEGA.'/build/vega.min.js',
            'vega-lite' => "{$base}/vega-lite@".self::VEGA_LITE.'/build/vega-lite.min.js',
            'vega-embed' => "{$base}/vega-embed@".self::VEGA_EMBED.'/build/vega-embed.min.js',
        ], $base === 'https://cdn.jsdelivr.net/npm' ? self::INTEGRITY : []);
    }

    /** Copies served from your own URL prefix: {base}/vega.min.js, {base}/vega-lite.min.js, {base}/vega-embed.min.js. */
    public static function local(string $base): self
    {
        $base = rtrim($base, '/');

        return new self(['vega' => "{$base}/vega.min.js", 'vega-lite' => "{$base}/vega-lite.min.js", 'vega-embed' => "{$base}/vega-embed.min.js"]);
    }

    /** Explicit URLs for each library. */
    public static function urls(string $vega, string $vegaLite, string $vegaEmbed): self
    {
        return new self(['vega' => $vega, 'vega-lite' => $vegaLite, 'vega-embed' => $vegaEmbed]);
    }

    /** Embeds the three files from a directory containing vega.min.js, vega-lite.min.js and vega-embed.min.js. */
    public static function inline(string $directory): self
    {
        $sources = [];
        foreach (['vega', 'vega-lite', 'vega-embed'] as $library) {
            $path = rtrim($directory, '/')."/{$library}.min.js";
            $contents = is_file($path) ? file_get_contents($path) : false;
            if ($contents === false) {
                throw new \RuntimeException("Cannot read {$path}.");
            }
            $sources[$library] = $contents;
        }

        return new self($sources, inline: true);
    }

    /** No library tags: for pages that already load vega-embed (bundled with npm, say). */
    public static function none(): self
    {
        return new self([]);
    }

    /** Script tags for the libraries, followed by the phiz loader. */
    public function scripts(bool $loader = true): string
    {
        $tags = '';
        foreach ($this->sources as $library => $source) {
            if ($this->inline) {
                $tags .= '<script>'.str_ireplace('</script', '<\/script', $source)."</script>\n";
                continue;
            }
            $attributes = 'src="'.htmlspecialchars($source, ENT_QUOTES).'"';
            if (isset($this->integrity[$library])) {
                $attributes .= ' integrity="'.$this->integrity[$library].'" crossorigin="anonymous"';
            }
            $tags .= "<script {$attributes}></script>\n";
        }

        return $loader ? $tags.'<script>'.self::loader()."</script>\n" : $tags;
    }

    /** The phiz loader script: renders every [data-phiz] element and re-renders when its spec attribute changes. */
    public static function loader(): string
    {
        static $script = null;

        return $script ??= trim((string) file_get_contents(dirname(__DIR__).'/resources/js/phiz.js'));
    }
}

<?php

declare(strict_types=1);

namespace Phiz;

/**
 * HTML rendering through vega-embed. The spec travels in a data-phiz attribute that the phiz loader script renders,
 * so the same markup works in static pages, Blade views, and Livewire components that re-render it.
 */
final class Html
{
    /**
     * The container element for a chart.
     *
     * @param array<string, mixed> $options vega-embed options; 'theme' => 'auto' follows prefers-color-scheme.
     * @param array<string, string|int|bool|null> $attributes Extra attributes for the container (class, style, id, ...).
     */
    public static function container(Chart|array $chart, array $options = [], array $attributes = []): string
    {
        $spec = $chart instanceof Chart ? $chart->toArray() : $chart;
        $attrs = ['data-phiz' => json_encode($spec, Chart::JSON_FLAGS)];
        if ($options !== []) {
            $attrs['data-phiz-options'] = json_encode($options, Chart::JSON_FLAGS);
        }
        $attrs += $attributes;

        return '<div'.self::attributes($attrs).'><div data-phiz-view wire:ignore></div></div>';
    }

    /**
     * A container that loads its spec from a URL when it scrolls into view, for pages with many charts. The URL is the
     * href of a link inside the container, so the page still links to each spec without JavaScript, and tools that
     * rewrite links move the spec files with the page.
     *
     * @param array<string, mixed> $options vega-embed options, as for container().
     * @param array<string, string|int|bool|null> $attributes Extra attributes for the container.
     */
    public static function deferred(string $url, array $options = [], array $attributes = [], string $text = 'Vega-Lite specification'): string
    {
        $attrs = ['data-phiz-deferred' => true];
        if ($options !== []) {
            $attrs['data-phiz-options'] = json_encode($options, Chart::JSON_FLAGS);
        }
        $attrs += $attributes;

        return '<div'.self::attributes($attrs).'><div data-phiz-view wire:ignore><a data-phiz-spec href="'
            .htmlspecialchars($url, ENT_QUOTES | ENT_HTML5).'">'.htmlspecialchars($text, ENT_QUOTES | ENT_HTML5).'</a></div></div>';
    }

    /** A self-contained fragment: the container plus script tags (null assets means the pinned CDN copies). */
    public static function embed(Chart|array $chart, array $options = [], Assets|false|null $assets = null, array $attributes = []): string
    {
        $html = self::container($chart, $options, $attributes);

        return $assets === false ? $html : $html."\n".($assets ?? Assets::cdn())->scripts();
    }

    public static function page(Chart|array|null $chart, ?string $title = null, array $options = [], ?Assets $assets = null, string $body = ''): string
    {
        $spec = $chart instanceof Chart ? $chart->toArray() : $chart;
        $title ??= is_array($spec['title'] ?? null) ? ($spec['title']['text'] ?? 'Plot') : ($spec['title'] ?? 'Plot');
        $content = $chart === null ? $body : self::container($chart, $options).$body;

        return "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
            .'<title>'.htmlspecialchars((string) (is_array($title) ? implode(' ', $title) : $title), ENT_QUOTES | ENT_HTML5)."</title>\n"
            ."<style>body{margin:24px;font-family:system-ui,sans-serif;background:#fff;color:#222}</style>\n"
            .($assets ?? Assets::cdn())->scripts()
            ."</head>\n<body>\n".$content."\n</body>\n</html>\n";
    }

    /** @param array<string, string|int|bool|null> $attributes */
    public static function attributes(array $attributes): string
    {
        $out = '';
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            $out .= $value === true ? " {$name}" : ' '.$name.'="'.htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5).'"';
        }

        return $out;
    }
}

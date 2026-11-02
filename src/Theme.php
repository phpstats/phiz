<?php

declare(strict_types=1);

namespace Phiz;

use InvalidArgumentException;

/**
 * Vega-Lite config presets modelled on ggplot2's complete themes, and the options theme() adjusts.
 *
 * Presets: gray (alias grey, ggplot2), bw, linedraw, minimal, classic, light, dark, void, and vega (Vega-Lite's
 * defaults).
 * Themes are inlined in the spec as config, so HTML and static exports look the same.
 */
final class Theme
{
    public const PRESETS = ['vega', 'gray', 'grey', 'ggplot2', 'bw', 'linedraw', 'minimal', 'classic', 'light', 'dark', 'void'];

    /** @return array<string, mixed> */
    /** Font sizes scaled from a base size in pixels, with ggplot2's proportions. */
    public static function fonts(int|float $baseSize = 11): array
    {
        $small = round($baseSize * 0.8, 1);

        return [
            'axis' => ['labelFontSize' => $small, 'titleFontSize' => $baseSize],
            'legend' => ['labelFontSize' => $small, 'titleFontSize' => $baseSize],
            'header' => ['labelFontSize' => $small, 'titleFontSize' => $baseSize],
            'title' => ['fontSize' => round($baseSize * 1.2, 1), 'subtitleFontSize' => $baseSize],
            'caption' => ['fontSize' => $small],
        ];
    }

    /** @return array<string, mixed> */
    public static function preset(string $name, int|float $baseSize = 11): array
    {
        $fonts = self::merge(self::fonts($baseSize), [
            'axis' => ['titleFontWeight' => 'normal'],
            'legend' => ['titleFontWeight' => 'normal'],
            'title' => ['anchor' => 'start', 'fontWeight' => 'normal'],
        ]);

        $config = match ($name) {
            'vega' => [],
            'gray', 'grey', 'ggplot2' => [
                'view' => ['fill' => '#EBEBEB', 'stroke' => null],
                'axis' => ['grid' => true, 'gridColor' => '#FFFFFF', 'domain' => false, 'tickColor' => '#333333', 'labelColor' => '#4D4D4D', 'titleColor' => '#000000'],
                'header' => ['labelColor' => '#1A1A1A'],
                // 'hue' is expanded at compile time to ggplot2's hue_pal() for the plot's number of levels.
                'range' => ['category' => 'hue'],
                'mark' => ['color' => '#595959'],
                'point' => ['color' => '#000000'],
                'line' => ['color' => '#000000'],
            ],
            'bw' => [
                'view' => ['fill' => '#FFFFFF', 'stroke' => '#333333'],
                'axis' => ['grid' => true, 'gridColor' => '#EBEBEB', 'domain' => false, 'tickColor' => '#333333', 'labelColor' => '#4D4D4D'],
            ],
            'linedraw' => [
                'view' => ['fill' => '#FFFFFF', 'stroke' => '#000000'],
                'axis' => ['grid' => true, 'gridColor' => '#000000', 'gridWidth' => 0.2, 'gridOpacity' => 0.6, 'domain' => false, 'tickColor' => '#000000', 'labelColor' => '#000000'],
                'header' => ['labelColor' => '#000000'],
            ],
            'minimal' => [
                'view' => ['stroke' => null],
                'axis' => ['grid' => true, 'gridColor' => '#EBEBEB', 'domain' => false, 'ticks' => false, 'labelColor' => '#4D4D4D'],
            ],
            'classic' => [
                'view' => ['stroke' => null],
                'axis' => ['grid' => false, 'domain' => true, 'domainColor' => '#000000', 'tickColor' => '#000000', 'labelColor' => '#4D4D4D'],
            ],
            'light' => [
                'view' => ['stroke' => '#B3B3B3'],
                'axis' => ['grid' => true, 'gridColor' => '#DEDEDE', 'domain' => false, 'tickColor' => '#B3B3B3', 'labelColor' => '#4D4D4D'],
            ],
            'dark' => [
                'background' => '#1F1F1F',
                'view' => ['fill' => '#303030', 'stroke' => null],
                'axis' => ['grid' => true, 'gridColor' => '#424242', 'domain' => false, 'tickColor' => '#888888', 'labelColor' => '#CCCCCC', 'titleColor' => '#EEEEEE'],
                'legend' => ['labelColor' => '#CCCCCC', 'titleColor' => '#EEEEEE'],
                'header' => ['labelColor' => '#CCCCCC', 'titleColor' => '#EEEEEE'],
                'title' => ['color' => '#EEEEEE', 'subtitleColor' => '#CCCCCC'],
                'mark' => ['color' => '#9ECAE1'],
            ],
            'void' => [
                'view' => ['stroke' => null],
                'axis' => ['domain' => false, 'grid' => false, 'ticks' => false, 'labels' => false, 'title' => null],
            ],
            default => throw new InvalidArgumentException("Unknown theme '{$name}'. Presets: ".implode(', ', self::PRESETS).'.'),
        };

        return $name === 'vega' ? ($baseSize == 11 ? [] : self::fonts($baseSize)) : self::merge($fonts, $config);
    }

    /**
     * Config for the individual theme() options.
     *
     * @param string|null $legendPosition right, left, top, bottom, or none.
     * @return array<string, mixed>
     */
    public static function options(
        ?string $font = null,
        ?string $legendPosition = null,
        ?string $background = null,
        ?string $panelBackground = null,
        ?string $panelBorder = null,
        ?bool $grid = null,
        ?string $titleAnchor = null,
        int|float|null $padding = null,
        int|float|null $xLabelAngle = null,
        int|float|null $yLabelAngle = null,
        ?string $legendDirection = null,
        ?bool $legendTitle = null,
        ?bool $axisLine = null,
        ?bool $ticks = null,
        ?string $gridColor = null,
        ?string $textColor = null,
        int|float|null $captionSize = null,
        ?string $captionColor = null,
        ?string $captionAnchor = null,
    ): array {
        $config = [];
        foreach (['axisX' => $xLabelAngle, 'axisY' => $yLabelAngle] as $axis => $angle) {
            if ($angle !== null) {
                // ggplot2 turns text anticlockwise; Vega-Lite turns labels clockwise.
                $config[$axis] = ['labelAngle' => -$angle] + ($axis === 'axisX' && $angle != 0 ? ['labelAlign' => $angle > 0 ? 'right' : 'left', 'labelBaseline' => abs($angle) >= 90 ? 'middle' : 'top'] : []);
            }
        }
        if ($legendDirection !== null) {
            $config['legend']['direction'] = $legendDirection;
        }
        if ($legendTitle === false) {
            $config['legend']['title'] = null;
        }
        if ($axisLine !== null) {
            $config['axis']['domain'] = $axisLine;
            if ($axisLine) {
                $config['axis']['domainColor'] = '#000000';
            }
        }
        if ($ticks !== null) {
            $config['axis']['ticks'] = $ticks;
        }
        if ($gridColor !== null) {
            $config['axis']['gridColor'] = $gridColor;
        }
        if ($textColor !== null) {
            foreach (['axis' => ['labelColor', 'titleColor'], 'legend' => ['labelColor', 'titleColor'], 'header' => ['labelColor', 'titleColor'], 'title' => ['color', 'subtitleColor'], 'caption' => ['color']] as $part => $keys) {
                foreach ($keys as $key) {
                    $config[$part][$key] = $textColor;
                }
            }
        }
        if ($font !== null) {
            $config['font'] = $font;
        }
        if ($legendPosition === 'none') {
            $config['legend']['disable'] = true;
        } elseif ($legendPosition !== null) {
            if (! in_array($legendPosition, ['right', 'left', 'top', 'bottom', 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'none'], true)) {
                throw new InvalidArgumentException("Unknown legend position '{$legendPosition}'.");
            }
            $config['legend']['orient'] = $legendPosition;
        }
        if ($background !== null) {
            $config['background'] = $background;
        }
        if ($panelBackground !== null) {
            $config['view']['fill'] = $panelBackground;
        }
        if ($panelBorder !== null) {
            $config['view']['stroke'] = $panelBorder === 'none' ? null : $panelBorder;
        }
        if ($grid !== null) {
            $config['axis']['grid'] = $grid;
        }
        if ($titleAnchor !== null) {
            $config['title']['anchor'] = $titleAnchor;
        }
        if ($padding !== null) {
            $config['padding'] = $padding;
        }
        if ($captionSize !== null) {
            $config['caption']['fontSize'] = $captionSize;
        }
        if ($captionColor !== null) {
            $config['caption']['color'] = $captionColor;
        }
        if ($captionAnchor !== null) {
            $config['caption']['anchor'] = $captionAnchor;
        }

        return $config;
    }

    /**
     * The config every specification starts from: about five ticks on continuous axes, as R's pretty() gives, and the
     * named mark styles that fitted lines and confidence bands use, so a theme or the dark variant can recolor them.
     *
     * @return array<string, mixed>
     */
    public static function base(): array
    {
        return [
            'axisQuantitative' => ['tickCount' => 5],
            'style' => [
                'phiz-fit' => ['color' => '#3366FF'],
                'phiz-band' => ['color' => '#999999', 'opacity' => 0.4],
            ],
        ];
    }

    /**
     * Config that turns a light theme dark, for pages in dark mode: a transparent background, muted grid lines,
     * light text, and mark colors with enough contrast on a dark page. phiz's loader merges it over the spec's config
     * when the page is dark. A spec that is already dark, as theme('dark') makes it, needs none.
     *
     * @param array<string, mixed> $config The spec's config.
     * @return array<string, mixed>
     */
    public static function dark(array $config): array
    {
        if (in_array($config['background'] ?? null, ['#1F1F1F', '#000000', 'black'], true)) {
            return [];
        }
        $panel = $config['view']['fill'] ?? null;
        $grayPanel = is_string($panel) && ! in_array(strtoupper($panel), ['#FFFFFF', 'WHITE'], true);
        [$text, $muted, $line] = ['#E4E4E7', '#A1A1AA', '#71717A'];
        $mark = '#8AB4F8';
        $dark = [
            'background' => 'transparent',
            // A theme that removes the panel border keeps it removed; any other border, Vega-Lite's default included, is muted.
            'view' => ['fill' => $grayPanel ? '#27272A' : null] + (array_key_exists('stroke', $config['view'] ?? []) && $config['view']['stroke'] === null ? [] : ['stroke' => '#3F3F46']),
            'axis' => ['gridColor' => $grayPanel ? '#3F3F46' : 'rgba(255, 255, 255, 0.12)', 'gridOpacity' => 1, 'domainColor' => $line, 'tickColor' => $line, 'labelColor' => $muted, 'titleColor' => $text],
            'legend' => ['labelColor' => $text, 'titleColor' => $text],
            'header' => ['labelColor' => $text, 'titleColor' => $text],
            'title' => ['color' => $text, 'subtitleColor' => $muted],
            'mark' => ['color' => $mark],
            'point' => ['color' => $mark],
            'line' => ['color' => $mark],
            'text' => ['color' => $text],
            'rule' => ['color' => $muted],
            'style' => [
                'phiz-fit' => ['color' => '#A5C8FF'],
                'phiz-band' => ['color' => '#FFFFFF', 'opacity' => 0.16],
            ],
        ];
        if (isset($config['caption'])) {
            $dark['caption'] = ['color' => $muted];
        }

        return $dark;
    }

    /** Recursive merge where later scalars and lists replace earlier ones. */
    public static function merge(array $base, array ...$overrides): array
    {
        foreach ($overrides as $override) {
            foreach ($override as $key => $value) {
                if (is_array($value) && ! array_is_list($value) && isset($base[$key]) && is_array($base[$key]) && ! array_is_list($base[$key])) {
                    $base[$key] = self::merge($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }
}

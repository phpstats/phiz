<?php

declare(strict_types=1);

namespace Phiz;

use InvalidArgumentException;

/**
 * Vega-Lite config presets modelled on ggplot2's complete themes, and the options theme() adjusts.
 *
 * Presets: gray (alias grey, ggplot2), bw, minimal, classic, light, dark, void, and vega (Vega-Lite's defaults).
 * Themes are inlined in the spec as config, so HTML and static exports look the same.
 */
final class Theme
{
    public const PRESETS = ['vega', 'gray', 'grey', 'ggplot2', 'bw', 'minimal', 'classic', 'light', 'dark', 'void'];

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
    ): array {
        $config = [];
        if ($font !== null) {
            $config['font'] = $font;
        }
        if ($legendPosition === 'none') {
            $config['legend'] = ['disable' => true];
        } elseif ($legendPosition !== null) {
            if (! in_array($legendPosition, ['right', 'left', 'top', 'bottom', 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'none'], true)) {
                throw new InvalidArgumentException("Unknown legend position '{$legendPosition}'.");
            }
            $config['legend'] = ['orient' => $legendPosition];
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

        return $config;
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

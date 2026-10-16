<?php

declare(strict_types=1);

namespace Phit;

/**
 * Translates fixed visual properties from ggplot2 names to Vega-Lite mark properties.
 *
 * Mark kinds: point (symbols), line (lines and rules), filled (bars, areas, rects, where color is the outline and
 * fill the interior, as in ggplot2), and text. Unknown names pass through, so any Vega-Lite mark property works.
 */
final class Style
{
    public const LINETYPES = [
        'solid' => [], 'blank' => [0, 1], 'dashed' => [4, 4], 'dotted' => [1, 3], 'dotdash' => [1, 3, 4, 3],
        'longdash' => [8, 4], 'twodash' => [2, 2, 6, 2],
    ];

    private const SHAPES = [
        0 => ['square', false], 1 => ['circle', false], 2 => ['triangle-up', false], 3 => ['cross', false],
        4 => ['cross', false], 5 => ['diamond', false], 6 => ['triangle-down', false], 8 => ['cross', false],
        15 => ['square', true], 16 => ['circle', true], 17 => ['triangle-up', true], 18 => ['diamond', true],
        19 => ['circle', true], 20 => ['circle', true], 21 => ['circle', true], 22 => ['square', true],
        23 => ['diamond', true], 24 => ['triangle-up', true], 25 => ['triangle-down', true],
    ];

    /**
     * @param array<string, mixed> $style
     * @return array<string, mixed>
     */
    public static function mark(array $style, string $kind): array
    {
        $out = [];
        foreach ($style as $name => $value) {
            if ($value === null) {
                continue;
            }
            switch ($name) {
                case 'alpha':
                    $out['opacity'] = $value;
                    break;
                case 'colour':
                case 'color':
                    $out[$kind === 'filled' ? 'stroke' : 'color'] = $value;
                    break;
                case 'fill':
                    $out['fill'] = $value;
                    if ($kind === 'point') {
                        $out['filled'] = true;
                    }
                    break;
                case 'size':
                    $out[match ($kind) { 'point' => 'size', 'text' => 'fontSize', default => 'strokeWidth' }] = $value;
                    break;
                case 'linewidth':
                    $out['strokeWidth'] = $value;
                    break;
                case 'stroke':
                    $out[is_numeric($value) ? 'strokeWidth' : 'stroke'] = $value;
                    break;
                case 'linetype':
                    $out['strokeDash'] = self::dash($value);
                    break;
                case 'shape':
                    [$shape, $filled] = is_int($value) ? (self::SHAPES[$value] ?? ['circle', true]) : [$value, null];
                    $out['shape'] = $shape;
                    if ($filled !== null) {
                        $out['filled'] = $filled;
                    }
                    break;
                case 'angle':
                    // ggplot2 turns text anticlockwise; Vega-Lite turns it clockwise.
                    $out['angle'] = is_numeric($value) ? fmod(360 - fmod((float) $value, 360), 360) : $value;
                    break;
                case 'hjust':
                    $out['align'] = $value <= 0.25 ? 'left' : ($value >= 0.75 ? 'right' : 'center');
                    break;
                case 'vjust':
                    $out['baseline'] = $value <= 0.25 ? 'bottom' : ($value >= 0.75 ? 'top' : 'middle');
                    break;
                case 'nudgeX':
                case 'nudge_x':
                    $out['dx'] = $value;
                    break;
                case 'nudgeY':
                case 'nudge_y':
                    $out['dy'] = is_numeric($value) ? -$value : $value;
                    break;
                case 'family':
                    $out['font'] = $value;
                    break;
                case 'fontface':
                    if (str_contains((string) $value, 'bold')) {
                        $out['fontWeight'] = 'bold';
                    }
                    if (str_contains((string) $value, 'italic')) {
                        $out['fontStyle'] = 'italic';
                    }
                    break;
                default:
                    $out[$name] = $value;
            }
        }

        return $out;
    }

    /** A ggplot2 linetype name, a hex dash string such as '44', or a dash array. */
    public static function dash(mixed $linetype): mixed
    {
        if (is_array($linetype) || $linetype instanceof Expr) {
            return $linetype;
        }
        if (is_string($linetype) && isset(self::LINETYPES[$linetype])) {
            return self::LINETYPES[$linetype];
        }
        if (is_string($linetype) && preg_match('/^[0-9a-fA-F]{2,8}$/', $linetype)) {
            return array_map(hexdec(...), str_split($linetype));
        }
        if (is_int($linetype)) {
            return array_values(self::LINETYPES)[$linetype] ?? [];
        }
        throw new \InvalidArgumentException("Unknown linetype '{$linetype}'. Use ".implode(', ', array_keys(self::LINETYPES)).', a dash string such as "44", or a dash array.');
    }
}

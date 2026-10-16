<?php

declare(strict_types=1);

namespace Phiz;

/**
 * Color palettes computed in PHP.
 */
final class Palette
{
    /**
     * ggplot2's default discrete palette, scales::hue_pal(): n hues evenly spaced from 15 degrees, at chroma 100 and
     * luminance 65 in CIE-LCh(uv), as hex sRGB.
     *
     * @return list<string>
     */
    public static function hue(int $n, float $from = 15.0, float $chroma = 100.0, float $luminance = 65.0): array
    {
        if ($n < 1) {
            return [];
        }
        // hue_pal spans 360 degrees and drops the endpoint, so the first and last hues differ.
        $to = $from + 360;
        $step = ($to - $from) / $n;
        $colors = [];
        for ($i = 0; $i < $n; $i++) {
            $colors[] = self::hcl(fmod($from + $i * $step, 360), $chroma, $luminance);
        }

        return $colors;
    }

    /** R's grDevices::hcl(): polar CIE-LUV with the D65 white point, gamma-encoded and clipped to sRGB. */
    public static function hcl(float $h, float $c, float $l): string
    {
        [$xn, $yn, $zn] = [95.047, 100.000, 108.883];
        $un = 4 * $xn / ($xn + 15 * $yn + 3 * $zn);
        $vn = 9 * $yn / ($xn + 15 * $yn + 3 * $zn);

        $u = $c * cos(deg2rad($h));
        $v = $c * sin(deg2rad($h));
        if ($l <= 0) {
            return '#000000';
        }
        $y = $yn * ($l > 8 ? (($l + 16) / 116) ** 3 : $l / 903.2962962962963);
        $up = $u / (13 * $l) + $un;
        $vp = $v / (13 * $l) + $vn;
        $x = 9.0 * $y * $up / (4 * $vp);
        $z = -$x / 3 - 5 * $y + 3 * $y / $vp;

        $rgb = [
            3.240479 * $x - 1.537150 * $y - 0.498535 * $z,
            -0.969256 * $x + 1.875992 * $y + 0.041556 * $z,
            0.055648 * $x - 0.204043 * $y + 1.057311 * $z,
        ];
        $hex = '#';
        foreach ($rgb as $channel) {
            $channel /= 100;
            $channel = $channel > 0.00304 ? 1.055 * $channel ** (1 / 2.4) - 0.055 : 12.92 * $channel;
            $hex .= sprintf('%02X', (int) round(255 * max(0.0, min(1.0, $channel))));
        }

        return $hex;
    }
}

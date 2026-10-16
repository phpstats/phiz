<?php

declare(strict_types=1);

namespace Phiz;

/**
 * Axis and legend label formatting, as the scales package's label_*() functions: a d3 format, a time format, or a
 * Vega expression of datum.value. Pass one as a scale's labels: scaleY(labels: label_percent()).
 */
final class Label
{
    public function __construct(
        public readonly ?string $format = null,
        public readonly ?string $formatType = null,
        public readonly ?string $expr = null,
    ) {}

    /**
     * Numbers: accuracy rounds (0.01 keeps two decimals; null trims trailing zeros), scale multiplies first, and
     * bigMark separates thousands.
     */
    public static function number(int|float|null $accuracy = null, int|float $scale = 1, string $prefix = '', string $suffix = '', string $bigMark = ','): self
    {
        $specifier = ($bigMark === '' ? '' : ',').($accuracy === null ? '~f' : '.'.self::decimals($accuracy).'f');
        $value = $scale == 1 ? 'datum.value' : 'datum.value * '.$scale;
        $formatted = "format({$value}, '{$specifier}')";
        if ($bigMark !== '' && $bigMark !== ',') {
            $formatted = "replace({$formatted}, /,/g, ".json_encode($bigMark).')';
        }
        $expr = ($prefix === '' ? '' : json_encode($prefix).' + ').$formatted.($suffix === '' ? '' : ' + '.json_encode($suffix));

        return new self(expr: $expr);
    }

    private static function decimals(int|float $accuracy): int
    {
        if (! ($accuracy > 0)) {
            throw new \InvalidArgumentException('Label accuracy must be positive.');
        }

        return max(0, (int) -floor(log10($accuracy) + 1e-9));
    }
}

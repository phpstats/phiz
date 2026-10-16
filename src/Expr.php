<?php

declare(strict_types=1);

namespace Phiz;

use JsonSerializable;

/**
 * A Vega expression, such as a parameter name bound to a slider. Usable wherever Vega-Lite takes an ExprRef: mark
 * properties (alpha: expr('opacity')), rule positions (hline(yintercept: expr('cutoff'))), and so on.
 */
final class Expr implements JsonSerializable
{
    public function __construct(public readonly string $expr) {}

    /** @return array{expr: string} */
    public function jsonSerialize(): array
    {
        return ['expr' => $this->expr];
    }
}

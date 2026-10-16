<?php

declare(strict_types=1);

namespace Phiz\Exceptions;

final class VlConvertNotFound extends \RuntimeException implements PhizException
{
    public function __construct()
    {
        parent::__construct(
            'Static export needs the vl-convert binary, which was not found. Download it from '
            .'https://github.com/vega/vl-convert/releases and put it on PATH, or set PHIZ_VL_CONVERT to its path. '
            .'HTML output (->html(), ->embed()) works without it.'
        );
    }
}

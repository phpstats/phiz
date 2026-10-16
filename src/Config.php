<?php

declare(strict_types=1);

namespace Phiz;

/**
 * Process-wide settings for large data. In Laravel they come from config/phiz.php.
 */
final class Config
{
    /**
     * Above this many rows, bin2d(), histogram(), and hex() bin in PHP by default ('auto'), so only the bins are
     * embedded in the spec.
     */
    public static int $serverRows = 10_000;

    /** A spec that embeds more inline rows than this raises an E_USER_WARNING; null turns the warning off. */
    public static ?int $warnRows = 20_000;
}

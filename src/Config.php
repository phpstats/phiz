<?php

declare(strict_types=1);

namespace Phit;

/**
 * Process-wide settings for large data. In Laravel they come from config/phit.php.
 */
final class Config
{
    /**
     * Above this many rows, bin2d(), histogram(), and hex() bin in PHP by default ('auto'), so only the bins are
     * embedded in the spec.
     */
    public static int $serverRows = 10_000;

    /** A spec that embeds more inline rows than this triggers the warning; null turns the warning off. */
    public static ?int $warnRows = 20_000;

    /**
     * Receives the size warning message. Null raises an E_USER_WARNING; the Laravel provider logs it instead,
     * because Laravel turns PHP warnings into exceptions.
     *
     * @var (\Closure(string): void)|null
     */
    public static ?\Closure $warn = null;
}

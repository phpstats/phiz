<?php

declare(strict_types=1);

namespace Phiz;

/**
 * Small classic datasets for examples, tests, and documentation, as rows (numbers parsed as int or float).
 *
 * mpg: fuel economy of 234 cars, 1999 and 2008 (from ggplot2; source fueleconomy.gov).
 * mtcars: 32 cars from the 1974 Motor Trend road tests, with the model name as a column.
 * economics: US monthly economic time series, 1967-2015 (from ggplot2; source FRED).
 * faithful: 272 Old Faithful eruptions, duration and waiting time in minutes.
 */
final class Datasets
{
    /** @var array<string, list<array<string, mixed>>> */
    private static array $cache = [];

    /**
     * ggplot2's mpg: 234 cars from 1999 and 2008 with displacement (displ, liters), city and highway mpg (cty, hwy), drive (drv), and class.
     *
     * @return list<array<string, mixed>> */
    public static function mpg(): array
    {
        return self::load('mpg');
    }

    /**
     * R's mtcars: 32 cars with mpg, cylinders (cyl), weight (wt, 1,000 lb), horsepower (hp), transmission (am, 1 manual), and the model name.
     *
     * @return list<array<string, mixed>> */
    public static function mtcars(): array
    {
        return self::load('mtcars');
    }

    /**
     * ggplot2's economics: 574 monthly US rows from 1967 with date, pce ($bn), pop (thousands), psavert (%), uempmed (weeks), and unemploy (thousands).
     *
     * @return list<array<string, mixed>> */
    public static function economics(): array
    {
        return self::load('economics');
    }

    /**
     * R's faithful: 272 Old Faithful eruptions with their length (eruptions) and the wait before them (waiting), in minutes.
     *
     * @return list<array<string, mixed>> */
    public static function faithful(): array
    {
        return self::load('faithful');
    }

    /**
     * A bundled dataset by name, from resources/data/{name}.csv, cached after the first load; an unknown name throws.
     *
     * @return list<array<string, mixed>> */
    public static function load(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        $path = dirname(__DIR__)."/resources/data/{$name}.csv";
        $handle = preg_match('/^[a-z_]+$/', $name) && is_file($path) ? fopen($path, 'r') : false;
        if ($handle === false) {
            throw new \InvalidArgumentException("Unknown dataset '{$name}'.");
        }
        $header = fgetcsv($handle, escape: '');
        $rows = [];
        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $row = [];
            foreach ($header as $i => $column) {
                $value = $line[$i] ?? null;
                $row[$column] = match (true) {
                    $value === null || $value === '' || $value === 'NA' => null,
                    (bool) preg_match('/^-?\d+$/', $value) => (int) $value,
                    is_numeric($value) => (float) $value,
                    default => $value,
                };
            }
            $rows[] = $row;
        }
        fclose($handle);

        return self::$cache[$name] = $rows;
    }
}

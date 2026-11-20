<?php declare(strict_types=1);

namespace InReNa\Service\Observation;

/**
 * Validates query values before they reach date parsing or DAL UUID fields.
 */
final class PublicFilterInput
{
    public static function date(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }

    public static function uuid(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[0-9a-f]{32}$/iD', $value) ? $value : null;
    }

    public static function miniwald(mixed $value): ?string
    {
        return $value === 'other' ? 'other' : self::uuid($value);
    }

    public static function page(mixed $value): int
    {
        if (!is_scalar($value) || !preg_match('/^[1-9][0-9]{0,5}$/D', (string) $value)) {
            return 1;
        }

        return (int) $value;
    }
}

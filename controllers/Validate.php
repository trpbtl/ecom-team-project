<?php
/**
 * Server-side validation rules shared by the controllers.
 * The same patterns are used in js/validate.js for instant feedback in the browser.
 */
class Validate
{
    public const EMAIL    = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    public const PHONE    = '/^[0-9+\-\s]{7,15}$/';
    public const PASSWORD = '/^(?=.*\d).{8,}$/';
    public const HEX      = '/^#[0-9a-fA-F]{6}$/';

    /** trim + strip_tags + collapse spaces. */
    public static function clean($value): string
    {
        return preg_replace('/\s+/', ' ', trim(strip_tags((string) $value)));
    }

    public static function text(string $value, int $min, int $max): bool
    {
        $len = mb_strlen($value);
        return $len >= $min && $len <= $max;
    }

    public static function email(string $value): bool
    {
        return strlen($value) <= 100 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false && preg_match(self::EMAIL, $value);
    }

    public static function phone(string $value): bool
    {
        return (bool) preg_match(self::PHONE, $value);
    }

    public static function password(string $value): bool
    {
        return strlen($value) <= 72 && (bool) preg_match(self::PASSWORD, $value);   // bcrypt reads at most 72 bytes
    }

    public static function positiveInt($value): ?int
    {
        $n = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $n === false ? null : $n;
    }

    public static function fail(string $message): array
    {
        return ['success' => false, 'error' => $message];
    }
}

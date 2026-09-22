<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class Settings
{
    public static function otpLength(): int
    {
        return self::integer(
            AuthDefaults::OTP_LENGTH,
            4,
            9
        );
    }

    public static function otpLifetime(): int
    {
        return self::integer(
            AuthDefaults::OTP_LIFETIME,
            1,
            604800
        );
    }

    public static function otpMaxAttempts(): int
    {
        return self::integer(
            AuthDefaults::OTP_MAX_ATTEMPTS,
            1,
            100
        );
    }

    public static function sessionLifetime(): int
    {
        return self::integer(
            AuthDefaults::SESSION_LIFETIME,
            1,
            315360000
        );
    }

    public static function emailAccessMode(): string
    {
        $value = Configuration::get(
            AuthDefaults::EMAIL_ACCESS_MODE
        );

        if (
            !is_string($value)
            || !in_array($value, ['any', 'whitelist'], true)
        ) {
            throw self::invalid(
                AuthDefaults::EMAIL_ACCESS_MODE
            );
        }

        return $value;
    }

    public static function cookieName(): string
    {
        $value = Configuration::get(
            AuthDefaults::COOKIE_NAME
        );

        if (!is_string($value)) {
            throw self::invalid(AuthDefaults::COOKIE_NAME);
        }

        return self::validateCookieName($value);
    }

    public static function cookieSameSite(): string
    {
        $value = Configuration::get(
            AuthDefaults::COOKIE_SAME_SITE
        );

        if (!is_string($value)) {
            throw self::invalid(
                AuthDefaults::COOKIE_SAME_SITE
            );
        }

        return self::validateSameSite($value);
    }

    public static function requestCodeEmailRateLimit(): array
    {
        return self::rateLimit(
            AuthDefaults::REQUEST_CODE_EMAIL_RATE_LIMIT
        );
    }

    public static function requestCodeIpRateLimit(): array
    {
        return self::rateLimit(
            AuthDefaults::REQUEST_CODE_IP_RATE_LIMIT
        );
    }

    public static function verifyCodeEmailRateLimit(): array
    {
        return self::rateLimit(
            AuthDefaults::VERIFY_CODE_EMAIL_RATE_LIMIT
        );
    }

    public static function verifyCodeIpRateLimit(): array
    {
        return self::rateLimit(
            AuthDefaults::VERIFY_CODE_IP_RATE_LIMIT
        );
    }

    public static function trustedProxies(): array
    {
        $value = Configuration::get(
            AuthDefaults::TRUSTED_PROXIES
        );

        if (!is_array($value) || !array_is_list($value)) {
            throw self::invalid(
                AuthDefaults::TRUSTED_PROXIES
            );
        }

        try {
            return ClientIp::normalizeTrustedProxies($value);
        } catch (\InvalidArgumentException $exception) {
            throw new \UnexpectedValueException(
                'Stored trusted proxy configuration is invalid.',
                0,
                $exception
            );
        }
    }

    public static function validateOtpLength(int $value): int
    {
        return self::validateInteger(
            $value,
            4,
            9,
            'OTP length'
        );
    }

    public static function validateOtpLifetime(int $value): int
    {
        return self::validateInteger(
            $value,
            1,
            604800,
            'OTP lifetime'
        );
    }

    public static function validateOtpMaxAttempts(int $value): int
    {
        return self::validateInteger(
            $value,
            1,
            100,
            'OTP maximum attempts'
        );
    }

    public static function validateSessionLifetime(int $value): int
    {
        return self::validateInteger(
            $value,
            1,
            315360000,
            'Session lifetime'
        );
    }

    public static function validateRateLimit(
        int $maxAttempts,
        int $windowSeconds
    ): array {
        return [
            'max_attempts' => self::validateInteger(
                $maxAttempts,
                1,
                1000000,
                'Rate-limit maximum attempts'
            ),
            'window_seconds' => self::validateInteger(
                $windowSeconds,
                1,
                31536000,
                'Rate-limit window'
            ),
        ];
    }

    public static function validateCookieName(
        string $value
    ): string {
        $value = trim($value);

        if (
            $value === ''
            || strlen($value) > 128
            || !preg_match(
                '/^[A-Za-z0-9!#$%&\'*+\-.^_`|~]+$/',
                $value
            )
        ) {
            throw new \InvalidArgumentException(
                'Cookie name is invalid.'
            );
        }

        return $value;
    }

    public static function validateSameSite(
        string $value
    ): string {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'lax' => 'Lax',
            'strict' => 'Strict',
            'none' => 'None',
            default => throw new \InvalidArgumentException(
                'SameSite must be Lax, Strict, or None.'
            ),
        };
    }

    private static function integer(
        string $key,
        int $minimum,
        int $maximum
    ): int {
        $value = Configuration::get($key);

        if (
            !is_int($value)
            || $value < $minimum
            || $value > $maximum
        ) {
            throw self::invalid($key);
        }

        return $value;
    }

    private static function rateLimit(string $key): array
    {
        $value = Configuration::get($key);

        if (
            !is_array($value)
            || !isset(
                $value['max_attempts'],
                $value['window_seconds']
            )
            || !is_int($value['max_attempts'])
            || !is_int($value['window_seconds'])
        ) {
            throw self::invalid($key);
        }

        try {
            return self::validateRateLimit(
                $value['max_attempts'],
                $value['window_seconds']
            );
        } catch (\InvalidArgumentException $exception) {
            throw new \UnexpectedValueException(
                'Stored authentication rate-limit configuration is invalid.',
                0,
                $exception
            );
        }
    }

    private static function validateInteger(
        int $value,
        int $minimum,
        int $maximum,
        string $name
    ): int {
        if ($value < $minimum || $value > $maximum) {
            throw new \InvalidArgumentException(
                "{$name} must be between {$minimum} and {$maximum}."
            );
        }

        return $value;
    }

    private static function invalid(
        string $key
    ): \UnexpectedValueException {
        return new \UnexpectedValueException(
            "Stored authentication configuration '{$key}' is invalid."
        );
    }
}

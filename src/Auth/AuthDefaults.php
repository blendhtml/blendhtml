<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class AuthDefaults
{
    public const OTP_LENGTH = 'otp_length';
    public const OTP_LIFETIME = 'otp_lifetime';
    public const OTP_MAX_ATTEMPTS = 'otp_max_attempts';
    public const SESSION_LIFETIME = 'session_lifetime';
    public const EMAIL_ACCESS_MODE = 'email_access_mode';
    public const COOKIE_NAME = 'cookie_name';
    public const COOKIE_SAME_SITE = 'cookie_same_site';
    public const REQUEST_CODE_EMAIL_RATE_LIMIT =
        'request_code_email_rate_limit';
    public const REQUEST_CODE_IP_RATE_LIMIT =
        'request_code_ip_rate_limit';
    public const VERIFY_CODE_EMAIL_RATE_LIMIT =
        'verify_code_email_rate_limit';
    public const VERIFY_CODE_IP_RATE_LIMIT =
        'verify_code_ip_rate_limit';
    public const TRUSTED_PROXIES = 'trusted_proxies';

    public const FLOW_COOKIE_NAME = 'blendhtml_auth_flow';
    public const CSRF_COOKIE_NAME = 'blendhtml_auth_csrf';

    private const VALUES = [
        self::OTP_LENGTH => 6,
        self::OTP_LIFETIME => 600,
        self::OTP_MAX_ATTEMPTS => 5,
        self::SESSION_LIFETIME => 1209600,
        self::EMAIL_ACCESS_MODE => 'any',
        self::COOKIE_NAME => 'blendhtml_auth',
        self::COOKIE_SAME_SITE => 'Lax',
        self::REQUEST_CODE_EMAIL_RATE_LIMIT => [
            'max_attempts' => 5,
            'window_seconds' => 900,
        ],
        self::REQUEST_CODE_IP_RATE_LIMIT => [
            'max_attempts' => 20,
            'window_seconds' => 900,
        ],
        self::VERIFY_CODE_EMAIL_RATE_LIMIT => [
            'max_attempts' => 10,
            'window_seconds' => 900,
        ],
        self::VERIFY_CODE_IP_RATE_LIMIT => [
            'max_attempts' => 30,
            'window_seconds' => 900,
        ],
        self::TRUSTED_PROXIES => [],
    ];

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::VALUES);
    }

    public static function value(string $key): mixed
    {
        if (!self::has($key)) {
            throw new \InvalidArgumentException(
                'Unknown authentication configuration key.'
            );
        }

        return self::VALUES[$key];
    }
}

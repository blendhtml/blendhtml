<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use RuntimeException;

final class BrowserCookies
{
    public static function authSecret(): ?string
    {
        $value = $_COOKIE[Settings::cookieName()] ?? null;

        return is_string($value)
            ? $value
            : null;
    }

    public static function setAuth(
        string $secret,
        int $expires
    ): void {
        self::set(
            Settings::cookieName(),
            $secret,
            $expires,
            Settings::cookieSameSite()
        );
    }

    public static function clearAuth(): void
    {
        self::clear(
            Settings::cookieName(),
            Settings::cookieSameSite()
        );
    }

    public static function flowSecret(): ?string
    {
        $value =
            $_COOKIE[AuthDefaults::FLOW_COOKIE_NAME]
            ?? null;

        return is_string($value)
            ? $value
            : null;
    }

    public static function setFlow(
        string $secret,
        int $expires
    ): void {
        self::set(
            AuthDefaults::FLOW_COOKIE_NAME,
            $secret,
            $expires,
            'Lax'
        );
    }

    public static function clearFlow(): void
    {
        self::clear(
            AuthDefaults::FLOW_COOKIE_NAME,
            'Lax'
        );
    }

    public static function csrfSecret(): ?string
    {
        $value =
            $_COOKIE[AuthDefaults::CSRF_COOKIE_NAME]
            ?? null;

        return is_string($value)
            ? $value
            : null;
    }

    public static function setCsrf(
        string $secret,
        int $expires
    ): void {
        self::set(
            AuthDefaults::CSRF_COOKIE_NAME,
            $secret,
            $expires,
            'Lax'
        );
    }

    public static function clearCsrf(): void
    {
        self::clear(
            AuthDefaults::CSRF_COOKIE_NAME,
            'Lax'
        );
    }

    private static function set(
        string $name,
        string $value,
        int $expires,
        string $sameSite
    ): void {
        if (headers_sent()) {
            throw new RuntimeException(
                'Authentication cookies must be set before response output.'
            );
        }

        $accepted = setcookie(
            $name,
            $value,
            [
                'expires' => $expires,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => $sameSite,
            ]
        );

        if (!$accepted) {
            throw new RuntimeException(
                'Unable to set a secure browser cookie.'
            );
        }

        $_COOKIE[$name] = $value;
    }

    private static function clear(
        string $name,
        string $sameSite
    ): void {
        if (!headers_sent()) {
            setcookie(
                $name,
                '',
                [
                    'expires' => 1,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => $sameSite,
                ]
            );
        }

        unset($_COOKIE[$name]);
    }
}

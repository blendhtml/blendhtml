<?php

namespace Blendhtml\Core;

final class UrlLocale
{
    public static function process(string $uriWithoutLocale, ?string $initialLocale = null): void
    {
        if (
            false === filter_var(
                getenv('LOCALE_IN_URL') === false ? true : getenv('LOCALE_IN_URL'),
                FILTER_VALIDATE_BOOLEAN
            )
        ) {
            if ($initialLocale !== null) {
                self::redirect($uriWithoutLocale);
            }

            return;
        }

        $url = parse_url($uriWithoutLocale, PHP_URL_PATH) ?: '/';
        $query = parse_url($uriWithoutLocale, PHP_URL_QUERY);

        $allowedLocales = Locales::allowedList();

        $urlLocale = null;

        if (in_array($initialLocale, $allowedLocales, true)) {
            $urlLocale = $initialLocale;
        }

        /**
         * URL locale wins.
         */
        if ($urlLocale !== null) {
            $cookieLocale = $_COOKIE['blendhtml_locale'] ?? null;

            if ($cookieLocale !== $urlLocale) {
                $_COOKIE['blendhtml_locale'] = $urlLocale;

                setcookie(
                    'blendhtml_locale',
                    $urlLocale,
                    [
                        'expires' => time() + 31536000,
                        'path' => '/',
                        'samesite' => 'Lax',
                    ]
                );
            }

            return;
        }

        $cookieLocale = $_COOKIE['blendhtml_locale'] ?? null;
        $defaultLocale = getenv('LOCALE') ?: 'en';

        $urlLocale =
            $cookieLocale !== null
            // @note Extra safety, should not happen
            && in_array($cookieLocale, $allowedLocales, true)
                ? $cookieLocale
                : $defaultLocale;

        self::redirect('/' . $urlLocale . $uriWithoutLocale);
    }

    private static function redirect(string $location): never
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $status = match ($method) {
            'GET', 'HEAD' => 302,
            default => 307,
        };

        header('Location: ' . $location, true, $status);
        exit;
    }
}
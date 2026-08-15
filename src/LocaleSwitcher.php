<?php

namespace Blendhtml\Core;

final class LocaleSwitcher
{
    public static function handle(
        string $locale,
        ?string $redirect = null
    ): never
    {
        $allowedLocales = Locales::allowedList();

        if (in_array($locale, $allowedLocales, true)) {
            setcookie(
                'bhtml_locale',
                $locale,
                [
                    'expires' => time() + 31536000,
                    'path' => '/',
                    'samesite' => 'Lax',
                ]
            );

            if ($redirect !== null) {

                $path = parse_url(
                    $redirect,
                    PHP_URL_PATH
                ) ?: '/';

                $segments = explode(
                    '/',
                    trim($path, '/')
                );

                if (
                    isset($segments[0])
                    && in_array(
                        $segments[0],
                        $allowedLocales,
                        true
                    )
                ) {
                    array_shift($segments);
                }

                $target = '/' . $locale;

                if (!empty($segments)) {
                    $target .= '/'
                        . implode('/', $segments);
                }
                
                $query = parse_url($redirect, PHP_URL_QUERY);
                $target = empty($query) ? $target : $target . '?' . $query;

                header('Location: ' . $target);
                exit;
            }
        }

        http_response_code(204);
        exit;
    }
}
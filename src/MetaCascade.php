<?php

namespace BlendHtml\Core;

use BlendHtml\Core\Component\Cascade;

class MetaCascade
{
    public static function apply(Page $page): void
    {
        $data = self::resolve($page);

        foreach ($data as $key => $value) {
            if (
                !is_string($key)
                || !property_exists(Context::meta(), $key)
            ) {
                continue;
            }

            [$resolved, $resolvedValue] = self::resolveValue($value);

            if ($resolved === false) {
                continue;
            }

            Context::meta()->{$key} = $resolvedValue;
        }
    }

    private static function resolve(Page $page): array
    {
        $data = [];

        $pagesDirectory = dirname(getcwd());

        $relativeDirectory = substr(
            $page->directory,
            strlen($pagesDirectory)
        );

        $segments = array_values(
            array_filter(
                explode(
                    '/',
                    trim(
                        $relativeDirectory,
                        '/'
                    )
                )
            )
        );

        $current = rtrim(
            $pagesDirectory,
            '/'
        );

        foreach ($segments as $segment) {
            $current .= '/' . $segment;

            $file =
                $current
                . '/meta.json';

            if (!is_file($file)) {
                continue;
            }

            $result = json_decode(
                file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!is_array($result)) {
                continue;
            }

            $data = Cascade::merge(
                $data,
                $result
            );
        }

        return $data;
    }

    private static function resolveValue(mixed $value): array
    {
        if (!is_array($value)) {
            return [
                true,
                self::normalizeValue($value),
            ];
        }

        $locale = Context::localeOrNull() ?? getenv('LOCALE') ?: 'en';

        if (array_key_exists($locale, $value)) {
            return [
                true,
                self::normalizeValue($value[$locale]),
            ];
        }

        $defaultLocale = getenv('LOCALE') ?: 'en';

        if (array_key_exists($defaultLocale, $value)) {
            return [
                true,
                self::normalizeValue($value[$defaultLocale]),
            ];
        }

        foreach (Locales::allowedList() as $allowedLocale) {
            if (array_key_exists($allowedLocale, $value)) {
                return [
                    true,
                    self::normalizeValue($value[$allowedLocale]),
                ];
            }
        }

        return [
            false,
            null,
        ];
    }

    private static function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return null;
    }
}

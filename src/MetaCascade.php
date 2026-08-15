<?php

namespace Blendhtml\Core;

use Blendhtml\Core\Component\Cascade;

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

        $root = rtrim(
            $page->rootDirectory,
            '/'
        );

        $target = rtrim(
            $page->directory,
            '/'
        );

        $data = self::mergeFile(
            $data,
            $root . '/meta.json'
        );

        if ($target === $root) {
            return $data;
        }

        $relative = trim(
            substr($target, strlen($root)),
            '/'
        );

        $current = $root;

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '') {
                continue;
            }

            $current .= '/' . $segment;

            $data = self::mergeFile(
                $data,
                $current . '/meta.json'
            );
        }

        return $data;
    }

    private static function mergeFile(
        array $data,
        string $file
    ): array {
        if (!is_file($file)) {
            return $data;
        }

        $result = json_decode(
            file_get_contents($file),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($result)) {
            return $data;
        }

        return Cascade::merge(
            $data,
            $result
        );
    }

    private static function resolveValue(mixed $value): array
    {
        if (!is_array($value)) {
            return [
                true,
                self::normalizeValue($value),
            ];
        }

        $locale = Context::localeOrNull() ?? (getenv('LOCALE') ?: 'en');

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

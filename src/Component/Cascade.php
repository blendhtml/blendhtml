<?php

namespace BlendHtml\Core\Component;

use BlendHtml\Core\Context;

final class Cascade
{
    public static function paths(): array
    {
        $root = Context::root();
        $page = Context::page();

        $segments = explode('/', trim($page, '/'));

        $paths = [];

        for ($i = count($segments); $i >= 0; $i--) {

            $subPath = implode(
                '/',
                array_slice($segments, 0, $i)
            );

            $paths[] = $subPath === ''
                ? $root
                : $root . '/' . $subPath;
        }

        $paths[] = dirname($root) . '/vendor/blendhtml/components';

        return array_reverse($paths);
    }

    public static function load(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = match (
        pathinfo($file, PATHINFO_EXTENSION)
        ) {
            'json' => json_decode(
                file_get_contents($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),

            'php' => require $file,

            default => [],
        };

        return is_array($data)
            ? $data
            : [];
    }

    public static function vendor(string $base): bool
    {
        return str_contains(
            $base,
            'vendor/blendhtml/components'
        );
    }

    public static function merge(
        array $base,
        array $override
    ): array
    {
        foreach ($override as $key => $value) {

            if (
                is_string($key)
                && str_starts_with($key, '!')
            ) {

                $base[substr($key, 1)] = $value;

                continue;
            }

            if (
                isset($base[$key])
                && is_array($base[$key])
                && is_array($value)
            ) {

                $base[$key] = self::merge(
                    $base[$key],
                    $value
                );

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }
}
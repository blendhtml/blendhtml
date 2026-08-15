<?php

namespace Blendhtml\Core\Component;

use Blendhtml\Core\Context;
use Blendhtml\Core\Vendor;

final class Cascade
{
    public static function paths(): array
    {
        $root = Context::root();
        $page = Context::page();

        $segments = array_values(
            array_filter(
                explode('/', trim($page, '/')),
                static fn(string $segment): bool => $segment !== ''
            )
        );

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

        $paths[] = Vendor::componentsPath();

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
        return Vendor::isComponentsPath($base);
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

<?php

namespace Blendhtml\Core;

final class PageJsonCascade
{
    public static function resolve(
        string $directory,
        string $rootDirectory
    ): array {
        $data = [];

        $root = rtrim($rootDirectory, '/');
        $target = rtrim($directory, '/');

        $data = self::mergeFile(
            $data,
            $root . '/page.json'
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
                $current . '/page.json'
            );
        }

        return $data;
    }

    private static function mergeFile(
        array $data,
        string $file
    ): array {
        $directory = dirname($file);

        if (
            is_file($directory . '/index.html.twig')
            && is_file($file)
        ) {
            throw new \LogicException(
                "page.json cannot exist along with index.html.twig: {$directory}"
            );
        }

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

        return self::merge(
            $data,
            $result
        );
    }

    private static function merge(
        array $base,
        array $override
    ): array {
        if (array_is_list($override)) {
            return $override;
        }

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
                if (
                    array_is_list($base[$key])
                    || array_is_list($value)
                ) {
                    $base[$key] = $value;

                    continue;
                }

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

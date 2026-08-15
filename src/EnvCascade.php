<?php

namespace Blendhtml\Core;

class EnvCascade
{
    public static function resolve(
        ?string $dir = null,
        ?string $root = null
    ): array {
        if ($root === null) {
            return self::resolveFromProjectRoot($dir);
        }

        return self::resolveFromRoot(
            $dir ?? $root,
            $root
        );
    }

    private static function resolveFromProjectRoot(?string $dir = null): array
    {
        $projectRoot = self::normalizePath(
            realpath(dirname(getcwd())) ?: dirname(getcwd())
        );

        $target = self::normalizePath(
            realpath($dir ?? Context::root()) ?: ($dir ?? Context::root())
        );

        if ($target === $projectRoot) {
            return [];
        }

        if (!str_starts_with($target . '/', $projectRoot . '/')) {
            return [];
        }

        $relative = trim(
            substr($target, strlen($projectRoot)),
            '/'
        );

        if ($relative === '') {
            return [];
        }

        $values = [];
        $current = $projectRoot;

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '') {
                continue;
            }

            $current .= '/' . $segment;

            $fileValues = self::read($current . '/.env');

            if ($fileValues !== []) {
                $values = array_merge(
                    $values,
                    $fileValues
                );
            }
        }

        return $values;
    }

    private static function resolveFromRoot(
        string $dir,
        string $root
    ): array {
        $root = self::normalizePath(
            realpath($root) ?: $root
        );

        $target = self::normalizePath(
            realpath($dir) ?: $dir
        );

        if (
            $target !== $root
            && !str_starts_with($target . '/', $root . '/')
        ) {
            return [];
        }

        $values = self::read($root . '/.env');

        if ($target === $root) {
            return $values;
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

            $fileValues = self::read($current . '/.env');

            if ($fileValues !== []) {
                $values = array_merge(
                    $values,
                    $fileValues
                );
            }
        }

        return $values;
    }

    private static function read(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $lines = file(
            $file,
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        );

        if ($lines === false) {
            return [];
        }

        $values = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (
                $line === ''
                || str_starts_with($line, '#')
                || !str_contains($line, '=')
            ) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);

            if ($key === '') {
                continue;
            }

            $values[$key] = trim($value);
        }

        return $values;
    }

    private static function normalizePath(string $path): string
    {
        return rtrim(
            str_replace('\\', '/', $path),
            '/'
        );
    }
}

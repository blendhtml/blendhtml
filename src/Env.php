<?php

namespace Blendhtml\Core;

class Env
{
    private static array $loaded = [];
    private static array $rewritten = [];

    public static function load(?string $dir = null): void
    {
        $dir = rtrim($dir ?? dirname(getcwd()), '/');
        $file = $dir . '/.env';

        foreach (self::read($file) as $key => $value) {
            self::set($key, $value);
            self::$loaded[$key] = $value;
        }
    }

    public static function rewrite(array $values): void
    {
        foreach (array_keys(self::$rewritten) as $key) {
            if (array_key_exists($key, $values)) {
                continue;
            }

            if (array_key_exists($key, self::$loaded)) {
                self::set($key, self::$loaded[$key]);
            } else {
                self::unset($key);
            }
        }

        self::$rewritten = [];

        foreach ($values as $key => $value) {
            if (
                !is_string($key)
                || trim($key) === ''
                || is_array($value)
                || is_object($value)
            ) {
                continue;
            }

            $key = trim($key);
            $value = (string)$value;

            self::set($key, $value);
            self::$rewritten[$key] = true;
        }
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

    private static function set(
        string $key,
        string $value
    ): void {
        putenv($key . '=' . $value);

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private static function unset(string $key): void
    {
        putenv($key);

        unset(
            $_ENV[$key],
            $_SERVER[$key]
        );
    }
}

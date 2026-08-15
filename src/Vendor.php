<?php

namespace Blendhtml\Core;

final class Vendor
{
    public static function namespace(): string
    {
        $namespace = getenv('VENDOR_NAMESPACE');

        if ($namespace === false || trim($namespace) === '') {
            return 'blendhtml';
        }

        $namespace = trim($namespace);
        $namespace = trim($namespace, '"\'');

        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $namespace)) {
            throw new \RuntimeException(
                sprintf('Invalid VENDOR_NAMESPACE "%s".', $namespace)
            );
        }

        return $namespace;
    }

    public static function root(?string $projectRoot = null): string
    {
        return self::normalizePath(
            ($projectRoot ?? dirname(__DIR__, 4))
            . '/vendor/'
            . self::namespace()
        );
    }

    public static function packagePath(
        string $package,
        ?string $projectRoot = null
    ): string {
        $package = trim($package, '/');

        if ($package === '') {
            throw new \RuntimeException('Vendor package name cannot be empty.');
        }

        return self::root($projectRoot) . '/' . $package;
    }

    public static function componentsPath(?string $projectRoot = null): string
    {
        return self::packagePath('components', $projectRoot);
    }

    public static function isComponentsPath(string $path): bool
    {
        return self::relativeToComponents($path) !== null;
    }

    public static function relativeToComponents(
        string $path,
        ?string $projectRoot = null
    ): ?string {
        $path = self::normalizePath($path);
        $componentsPath = self::normalizePath(
            self::componentsPath($projectRoot)
        );

        if ($path === $componentsPath) {
            return '';
        }

        $prefix = $componentsPath . '/';

        if (!str_starts_with($path, $prefix)) {
            return null;
        }

        return substr($path, strlen($prefix));
    }

    private static function normalizePath(string $path): string
    {
        return rtrim(
            str_replace('\\', '/', $path),
            '/'
        );
    }
}

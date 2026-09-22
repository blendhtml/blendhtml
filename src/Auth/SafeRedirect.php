<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class SafeRedirect
{
    public static function sanitize(
        mixed $redirect
    ): ?string {
        if (!is_string($redirect)) {
            return null;
        }

        $redirect = trim($redirect);

        if (
            $redirect === ''
            || strlen($redirect) > 2048
            || !str_starts_with($redirect, '/')
            || str_starts_with($redirect, '//')
            || preg_match('/[\x00-\x1F\x7F\\\\]/', $redirect)
        ) {
            return null;
        }

        $decoded = rawurldecode($redirect);

        if (
            str_starts_with($decoded, '//')
            || preg_match('/[\x00-\x1F\x7F\\\\]/', $decoded)
        ) {
            return null;
        }

        $parts = parse_url($redirect);

        if (
            $parts === false
            || isset(
                $parts['scheme'],
                $parts['host']
            )
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || isset($parts['fragment'])
        ) {
            return null;
        }

        $path = $parts['path'] ?? null;

        if (
            !is_string($path)
            || !str_starts_with($path, '/')
        ) {
            return null;
        }

        if (
            isset($parts['query'])
            && $parts['query'] !== ''
        ) {
            return $path . '?' . $parts['query'];
        }

        return $path;
    }
}

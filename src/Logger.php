<?php

namespace Blendhtml\Core;

use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Core\Auth\Exception\ForbiddenException;
use Blendhtml\Core\Auth\Exception\UnauthorizedException;
use RuntimeException;
use Throwable;

final class Logger
{
    private const LEVELS = [
        'debug',
        'notice',
        'warning',
        'error',
    ];

    private const JSON_FLAGS =
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_THROW_ON_ERROR;

    public static function exception(Throwable $exception): void
    {
        if (
            $exception instanceof AuthenticationException
            || $exception instanceof UnauthorizedException
            || $exception instanceof ForbiddenException
        ) {
            return;
        }

        try {
            $record = [
                'timestamp' => gmdate(DATE_ATOM),
                'level' => 'error',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '-',
                'host' => self::sanitizeLine(
                    $_SERVER['HTTP_HOST'] ?? '-'
                ),
                'uri' => self::sanitizeUrl(
                    Context::uriOrNull()
                    ?? ($_SERVER['REQUEST_URI'] ?? '-')
                ),
                'page' => Context::pageOrNull() ?? '-',
                'locale' => Context::localeOrNull() ?? '-',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '-',
                'referer' => self::sanitizeUrl(
                    $_SERVER['HTTP_REFERER'] ?? '-'
                ),
                'user_agent' => self::sanitizeLine(
                    $_SERVER['HTTP_USER_AGENT'] ?? '-'
                ),
                'cookies' => self::sanitizeData(
                    $_COOKIE,
                    true
                ),
                'get' => self::sanitizeData($_GET),
                'post' => self::sanitizeData($_POST),
                'exception' => get_class($exception),
                'message' => self::sanitizeMessage(
                    $exception->getMessage()
                ),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => self::safeTrace($exception),
            ];

            self::append(
                'error',
                str_repeat('=', 80)
                . PHP_EOL
                . json_encode(
                    $record,
                    JSON_PRETTY_PRINT | self::JSON_FLAGS
                )
                . PHP_EOL
                . str_repeat('=', 80)
                . PHP_EOL
                . PHP_EOL
            );
        } catch (Throwable) {
            // Logging must never alter application behavior.
        }
    }

    public static function event(
        string $event,
        array $context = [],
        string $level = 'notice'
    ): void {
        try {
            $level = self::level($level);

            $record = [
                'timestamp' => gmdate(DATE_ATOM),
                'level' => $level,
                'event' => $event,
                'method' => $_SERVER['REQUEST_METHOD'] ?? '-',
                'uri' => self::sanitizeUrl(
                    Context::uriOrNull()
                    ?? ($_SERVER['REQUEST_URI'] ?? '-')
                ),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '-',
                'context' => self::sanitizeData($context),
            ];

            self::append(
                $level,
                json_encode(
                    $record,
                    self::JSON_FLAGS
                ) . PHP_EOL
            );
        } catch (Throwable) {
            // Operational logging is best-effort.
        }
    }

    private static function append(
        string $level,
        string $content
    ): void {
        $level = self::level($level);
        $directory = self::projectRoot()
            . '/logs/system/'
            . $level;

        self::ensureDirectory($directory, 0770);

        $file = $directory
            . '/'
            . $level
            . '.'
            . gmdate('Y-m-d')
            . '.log';

        $written = file_put_contents(
            $file,
            $content,
            FILE_APPEND | LOCK_EX
        );

        if ($written === false) {
            throw new RuntimeException(
                'Unable to write the system log.'
            );
        }

        @chmod($file, 0660);
    }

    private static function level(string $level): string
    {
        $level = strtolower(trim($level));

        return in_array($level, self::LEVELS, true)
            ? $level
            : 'notice';
    }

    private static function ensureDirectory(
        string $directory,
        int $permissions
    ): void {
        if (
            !is_dir($directory)
            && !mkdir($directory, $permissions, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create the log directory.'
            );
        }

        @chmod($directory, $permissions);
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 4);
    }

    private static function sanitizeData(
        array $data,
        bool $cookies = false
    ): array {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $name = (string)$key;

            if (is_bool($value)) {
                $sanitized[$key] = $value;
            } elseif ($cookies || self::sensitiveKey($name)) {
                $sanitized[$key] =
                    self::hideSensitiveValue($value);
            } elseif (self::emailKey($name)) {
                $sanitized[$key] = is_string($value)
                    ? self::emailIdentifier($value)
                    : self::hideSensitiveValue($value);
            } elseif (is_array($value)) {
                $sanitized[$key] =
                    self::sanitizeData($value);
            } elseif (is_object($value)) {
                $sanitized[$key] =
                    '[object:' . get_class($value) . ']';
            } elseif (is_resource($value)) {
                $sanitized[$key] = '[resource]';
            } elseif (is_string($value)) {
                $sanitized[$key] =
                    self::sanitizeEmails($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private static function sensitiveKey(string $name): bool
    {
        $name = preg_replace(
            '/(?<=[a-z0-9])(?=[A-Z])/',
            '_',
            $name
        ) ?? $name;

        $name = strtolower(
            trim(
                preg_replace(
                    '/[^a-z0-9]+/',
                    '_',
                    $name
                ) ?? $name,
                '_'
            )
        );

        if (
            preg_match(
                '/_(count|length|status|type|enabled|present|expires_at)$/',
                $name
            )
        ) {
            return false;
        }

        if (
            preg_match(
                '/(^|_)(password|passwords|passwd|passphrase|secret|secrets|token|tokens|csrf|otp|authorization|credential|credentials)(_|$)/',
                $name
            )
        ) {
            return true;
        }

        if (
            in_array(
                $name,
                [
                    'cookie',
                    'cookies',
                    'http_cookie',
                    'set_cookie',
                    'api_key',
                    'private_key',
                    'signing_key',
                    'encryption_key',
                    'code',
                ],
                true
            )
            || str_ends_with($name, '_cookie')
            || str_ends_with($name, '_cookies')
        ) {
            return true;
        }

        return preg_match(
            '/(^|_)(auth|authorization|login|verification|one_time|recovery|backup|security|access|confirmation|invite|device)_code$/',
            $name
        ) === 1;
    }

    private static function emailKey(string $name): bool
    {
        $name = strtolower(trim($name));

        return $name === 'email'
            || str_ends_with($name, '_email');
    }

    private static function hideSensitiveValue(
        mixed $value
    ): mixed {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(
                [self::class, 'hideSensitiveValue'],
                $value
            );
        }

        return '[hidden]';
    }

    private static function emailIdentifier(
        string $email
    ): string {
        return 'email:'
            . substr(
                hash(
                    'sha256',
                    strtolower(trim($email))
                ),
                0,
                16
            );
    }

    private static function sanitizeEmails(
        string $value
    ): string {
        return preg_replace_callback(
            '/[A-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[A-Z0-9.-]+\.[A-Z]{2,63}/i',
            static fn(array $match): string =>
                self::emailIdentifier($match[0]),
            $value
        ) ?? $value;
    }

    private static function sanitizeMessage(
        string $message
    ): string {
        $message = self::sanitizeLine($message);

        $message = preg_replace(
            '/\b(?:Bearer|Basic)\s+\S+/i',
            '[authorization hidden]',
            $message
        ) ?? $message;

        $message = preg_replace(
            '/\b(password|passwd|passphrase|secret|token|csrf|otp|authorization|credentials?|cookie)\b\s*[:=]\s*(?:"[^"]*"|\'[^\']*\'|[^\s,;]+)/i',
            '$1=[hidden]',
            $message
        ) ?? $message;

        return preg_replace(
            '/\b(otp|verification[ _-]?code|login[ _-]?code)\s+([0-9]{4,9})\b/i',
            '$1 [hidden]',
            $message
        ) ?? $message;
    }

    private static function safeTrace(
        Throwable $exception
    ): string {
        $lines = [];

        foreach ($exception->getTrace() as $index => $frame) {
            $location = isset($frame['file'])
                ? self::sanitizeEmails(
                    $frame['file']
                    . '('
                    . ($frame['line'] ?? '-')
                    . ')'
                )
                : '[internal function]';

            $call =
                ($frame['class'] ?? '')
                . ($frame['type'] ?? '')
                . ($frame['function'] ?? '');

            $lines[] = "#{$index} {$location}: {$call}()";
        }

        $lines[] = '#' . count($lines) . ' {main}';

        return implode(PHP_EOL, $lines);
    }

    private static function sanitizeUrl(string $url): string
    {
        if ($url === '-') {
            return '-';
        }

        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && $path !== ''
            ? $path
            : '/';
    }

    private static function sanitizeLine(
        string $value
    ): string {
        return self::sanitizeEmails(
            trim(
                str_replace(
                    ["\r", "\n"],
                    ' ',
                    $value
                )
            )
        );
    }
}

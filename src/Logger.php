<?php

namespace BlendHtml\Core;

use Throwable;

final class Logger
{
    public static function exception(
        Throwable $exception
    ): void
    {
        $projectRoot =
            dirname(getcwd());

        $logDir =
            $projectRoot . '/logs';

        if (!is_dir($logDir)) {
            mkdir(
                $logDir,
                0777,
                true
            );
        }

        $file =
            $logDir
            . '/'
            . date('Y-m-d')
            . '.log';

        $content =
            str_repeat('=', 80)
            . "\n"
            . '[' . date('Y-m-d H:i:s') . "]\n\n"

            . 'METHOD: '
            . ($_SERVER['REQUEST_METHOD'] ?? '-')
            . "\n"

            . 'HOST: '
            . ($_SERVER['HTTP_HOST'] ?? '-')
            . "\n"

            . 'URI: '
            . (
                Context::uriOrNull()
                ?? ($_SERVER['REQUEST_URI'] ?? '-')
            )
            . "\n"

            . 'PAGE: '
            . (
                Context::pageOrNull()
                ?? '-'
            )
            . "\n"

            . 'LOCALE: '
            . (
                Context::localeOrNull()
                ?? '-'
            )
            . "\n"

            . 'IP: '
            . ($_SERVER['REMOTE_ADDR'] ?? '-')
            . "\n"

            . 'REFERER: '
            . ($_SERVER['HTTP_REFERER'] ?? '-')
            . "\n"

            . 'USER_AGENT: '
            . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
            . "\n\n"

            . 'COOKIE:' . "\n"
            . self::dump($_COOKIE)
            . "\n\n"

            . 'GET:' . "\n"
            . self::dump($_GET)
            . "\n\n"

            . 'POST:' . "\n"
            . self::dump($_POST)
            . "\n\n"

            . get_class($exception)
            . ': '
            . $exception->getMessage()
            . "\n\n"

            . 'FILE:' . "\n"
            . $exception->getFile()
            . ':'
            . $exception->getLine()
            . "\n";

        if (Context::devMode()) {
            $content .=
                "\nTRACE:\n"
                . $exception->getTraceAsString()
                . "\n";
        }

        $content .=
            str_repeat('=', 80)
            . "\n\n";

        file_put_contents(
            $file,
            $content,
            FILE_APPEND
        );
    }

    private static function dump(
        array $data
    ): string
    {
        if ($data === []) {
            return '(empty)';
        }

        return json_encode(
            $data,
            JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }
}
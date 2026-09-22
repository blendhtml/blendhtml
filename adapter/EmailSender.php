<?php

declare(strict_types=1);

namespace Blendhtml\CoreAdapter;

use RuntimeException;

final class EmailSender implements EmailSenderInterface
{
    public function send(
        string $recipient,
        string $subject,
        string $body,
        array $headers = []
    ): void {
        $directory = dirname(__DIR__, 4)
            . '/logs/auth-mail-debug';

        if (
            !is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create the mail log directory.'
            );
        }

        @chmod($directory, 0700);

        $record = [
            'timestamp' => gmdate(DATE_ATOM),
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'headers' => $headers,
            'accepted' => true,
        ];

        try {
            $json = json_encode(
                $record,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                'Unable to encode the mail log.',
                0,
                $exception
            );
        }

        $file = $directory
            . '/'
            . gmdate('Y-m-d')
            . '.jsonl';

        $written = file_put_contents(
            $file,
            $json . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($written === false) {
            throw new RuntimeException(
                'Unable to write the mail log.'
            );
        }

        @chmod($file, 0600);
    }
}
<?php

declare(strict_types=1);

namespace Blendhtml\Adapter;

use Blendhtml\CoreAdapter\EmailSenderInterface;
use RuntimeException;

final class EmailSender implements EmailSenderInterface
{
    public function send(
        string $recipient,
        string $subject,
        string $body,
        array $headers = []
    ): void {
        $accepted = @mail(
            $recipient,
            $subject,
            $body,
            implode("\r\n", $headers)
        );

        if (!$accepted) {
            throw new RuntimeException(
                'Email could not be sent.'
            );
        }
    }
}
<?php

declare(strict_types=1);

namespace Blendhtml\CoreAdapter;

interface EmailSenderInterface
{
    public function send(
        string $recipient,
        string $subject,
        string $body,
        array $headers = []
    ): void;
}
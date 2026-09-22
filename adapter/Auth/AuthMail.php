<?php

declare(strict_types=1);

namespace Blendhtml\CoreAdapter\Auth;

use Blendhtml\Core\Adapter;
use Blendhtml\Core\Auth\Exception\MailDeliveryException;
use Blendhtml\Core\Context;
use Throwable;

class AuthMail
{
    public function sendOtp(
        string $recipient,
        string $otp,
        int $lifetimeSeconds
    ): string {
        $applicationName = $this->applicationName();
        $minutes = (int)ceil($lifetimeSeconds / 60);

        $messages = $this->getMessages(
            $applicationName,
            $otp,
            $minutes,
            $lifetimeSeconds
        );

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Auto-Response-Suppress: All',
        ];

        try {
            Adapter::emailSender()->send(
                $recipient,
                $messages['subject'],
                implode(PHP_EOL, $messages['body']),
                $headers
            );
        } catch (Throwable $exception) {
            throw new MailDeliveryException($exception);
        }

        return 'mail';
    }

    protected function getMessages(
        string $applicationName,
        string $otp,
        int $minutes,
        int $lifetimeSeconds
    ): array {
        return match (strtolower(trim(Context::locale()))) {
            'et' => [
                'subject' => $applicationName . ' kinnituskood',
                'body' => [
                    "Teie {$applicationName} kinnituskood on:",
                    '',
                    $otp,
                    '',
                    "See kood aegub {$minutes} minuti ({$lifetimeSeconds} sekundi) pärast.",
                    'Kasutage seda koodi ainult rakenduses, kus te selle tellisite.',
                    'Kui te ei taotlenud seda koodi, võite seda e-kirja ignoreerida.',
                ],
            ],

            'ru' => [
                'subject' => $applicationName . ' код подтверждения',
                'body' => [
                    "Ваш код подтверждения {$applicationName}:",
                    '',
                    $otp,
                    '',
                    "Срок действия кода — {$minutes} мин. ({$lifetimeSeconds} секунд).",
                    'Используйте этот код только в приложении, где вы его запросили.',
                    'Если вы не запрашивали этот код, просто проигнорируйте это письмо.',
                ],
            ],

            default => [
                'subject' => $applicationName . ' verification code',
                'body' => [
                    "Your {$applicationName} verification code is:",
                    '',
                    $otp,
                    '',
                    "This code expires in {$minutes} minute(s) ({$lifetimeSeconds} seconds).",
                    'Use this code only in the application where you requested it.',
                    'If you did not request this code, you can ignore this email.',
                ],
            ],
        };
    }

    private function applicationName(): string
    {
        $name = trim((string)(getenv('AUTH_APP_NAME') ?: 'Blendhtml'));

        $name = preg_replace(
            '/[\r\n\x00-\x1F\x7F]+/',
            ' ',
            $name
        ) ?? 'Blendhtml';

        $name = trim($name);

        if ($name === '') {
            return 'Blendhtml';
        }

        return substr($name, 0, 80);
    }
}
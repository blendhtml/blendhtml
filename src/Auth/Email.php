<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

final class Email
{
    public static function normalize(string $email): string
    {
        $email = trim($email);

        if (function_exists('mb_strtolower')) {
            $email = mb_strtolower($email, 'UTF-8');
        } else {
            $email = strtolower($email);
        }

        if (
            $email === ''
            || strlen($email) > 254
            || preg_match('/[\x00-\x1F\x7F]/', $email)
            || filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new \InvalidArgumentException(
                'Invalid email address.'
            );
        }

        return $email;
    }

    public static function identifier(string $normalizedEmail): string
    {
        return 'email:'
            . substr(
                hash('sha256', $normalizedEmail),
                0,
                16
            );
    }
}

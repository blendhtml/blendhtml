<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final class AuthCleanup
{
    public static function removeExpiredLoginCodes(
        Connection $connection,
        DateTimeImmutable $now
    ): int {
        if (!self::enabled()) {
            return 0;
        }

        return $connection->executeStatement(
            'DELETE FROM auth__login_codes '
            . 'WHERE expires_at <= ?',
            [
                $now->format('Y-m-d H:i:s'),
            ]
        );
    }

    public static function removeExpiredSessions(
        Connection $connection,
        DateTimeImmutable $now
    ): int {
        if (!self::enabled()) {
            return 0;
        }

        return $connection->executeStatement(
            'DELETE FROM auth__sessions '
            . 'WHERE expires_at <= ?',
            [
                $now->format('Y-m-d H:i:s'),
            ]
        );
    }

    public static function removeExpiredCsrfTokens(
        Connection $connection,
        DateTimeImmutable $now
    ): int {
        if (!self::enabled()) {
            return 0;
        }

        return $connection->executeStatement(
            'DELETE FROM auth__csrf_tokens '
            . 'WHERE expires_at <= ?',
            [
                $now->format('Y-m-d H:i:s'),
            ]
        );
    }

    private static function enabled(): bool
    {
        return filter_var(
            getenv('AUTH_DB_AUTOCLEANUP') ?: false,
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
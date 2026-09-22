<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use Blendhtml\Core\Auth\Exception\AuthenticationException;
use Blendhtml\Core\Logger;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Throwable;

final class RateLimiter
{
    public static function hit(
        string $operation,
        string $email,
        string $ip,
        array $emailLimit,
        array $ipLimit
    ): void {
        $connection = Doctrine::em()
            ->getConnection();

        $subjects = [
            [
                'scope' => 'email',
                'subject' => $email,
                'limit' => $emailLimit,
            ],
            [
                'scope' => 'ip',
                'subject' => $ip,
                'limit' => $ipLimit,
            ],
        ];

        $now = self::now();

        foreach ($subjects as $entry) {
            self::ensureRow(
                $connection,
                $operation,
                $entry['scope'],
                self::subjectHash(
                    $entry['scope'],
                    $entry['subject']
                ),
                $now
            );
        }

        $limitedScope = null;

        $connection->beginTransaction();

        try {
            foreach ($subjects as $entry) {
                $scope = $entry['scope'];
                $limit = $entry['limit'];
                $hash = self::subjectHash(
                    $scope,
                    $entry['subject']
                );

                $cutoff = $now
                    ->modify(
                        '-' . $limit['window_seconds'] . ' seconds'
                    )
                    ->format('Y-m-d H:i:s');

                $connection->executeStatement(
                    'UPDATE auth__rate_limits '
                    . 'SET attempts = 0, window_started_at = ?, updated_at = ? '
                    . 'WHERE action = ? AND scope = ? AND subject_hash = ? '
                    . 'AND window_started_at <= ?',
                    [
                        $now->format('Y-m-d H:i:s'),
                        $now->format('Y-m-d H:i:s'),
                        $operation,
                        $scope,
                        $hash,
                        $cutoff,
                    ]
                );

                $updated = $connection->executeStatement(
                    'UPDATE auth__rate_limits '
                    . 'SET attempts = attempts + 1, updated_at = ? '
                    . 'WHERE action = ? AND scope = ? AND subject_hash = ? '
                    . 'AND attempts < ?',
                    [
                        $now->format('Y-m-d H:i:s'),
                        $operation,
                        $scope,
                        $hash,
                        $limit['max_attempts'],
                    ]
                );

                if ($updated !== 1) {
                    $limitedScope = $scope;

                    throw new AuthenticationException(
                        AuthenticationException::RATE_LIMITED,
                        'Too many authentication attempts. Please try again later.',
                        429,
                        self::retryAfter(
                            $connection,
                            $operation,
                            $scope,
                            $hash,
                            $limit['window_seconds'],
                            $now
                        )
                    );
                }
            }

            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            if (
                $exception instanceof AuthenticationException
                && $exception->reason()
                    === AuthenticationException::RATE_LIMITED
            ) {
                Logger::event(
                    'auth.login.rate_limited',
                    [
                        'operation' => $operation,
                        'scope' => $limitedScope,
                        'email_id' => Email::identifier($email),
                        'ip' => $ip,
                    ],
                    'warning'
                );
            }

            throw $exception;
        }
    }

    public static function prepareEmailSerialization(
        string $email
    ): void {
        self::ensureRow(
            Doctrine::em()->getConnection(),
            'request_code',
            'email',
            self::subjectHash('email', $email),
            self::now()
        );
    }

    public static function serializeEmail(
        Connection $connection,
        string $email
    ): void {
        $connection->executeStatement(
            'UPDATE auth__rate_limits '
            . 'SET updated_at = updated_at '
            . 'WHERE action = ? AND scope = ? AND subject_hash = ?',
            [
                'request_code',
                'email',
                self::subjectHash('email', $email),
            ]
        );
    }

    private static function ensureRow(
        Connection $connection,
        string $operation,
        string $scope,
        string $subjectHash,
        DateTimeImmutable $now
    ): void {
        try {
            $connection->insert(
                'auth__rate_limits',
                [
                    'action' => $operation,
                    'scope' => $scope,
                    'subject_hash' => $subjectHash,
                    'window_started_at' =>
                        $now->format('Y-m-d H:i:s'),
                    'attempts' => 0,
                    'updated_at' =>
                        $now->format('Y-m-d H:i:s'),
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the permanent counter row.
        }
    }

    private static function retryAfter(
        Connection $connection,
        string $operation,
        string $scope,
        string $subjectHash,
        int $windowSeconds,
        DateTimeImmutable $now
    ): int {
        $value = $connection->fetchOne(
            'SELECT window_started_at '
            . 'FROM auth__rate_limits '
            . 'WHERE action = ? AND scope = ? AND subject_hash = ?',
            [
                $operation,
                $scope,
                $subjectHash,
            ]
        );

        if (!is_string($value) || $value === '') {
            return $windowSeconds;
        }

        try {
            $started = new DateTimeImmutable(
                $value,
                new DateTimeZone('UTC')
            );

            return max(
                1,
                $started->getTimestamp()
                + $windowSeconds
                - $now->getTimestamp()
            );
        } catch (Throwable) {
            return $windowSeconds;
        }
    }

    private static function subjectHash(
        string $scope,
        string $subject
    ): string {
        return hash(
            'sha256',
            $scope . "\0" . $subject
        );
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );
    }
}

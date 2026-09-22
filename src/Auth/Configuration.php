<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth;

use Blendhtml\Core\Auth\Entity\AuthConfig;
use Blendhtml\Doctrine\Doctrine;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use JsonException;

final class Configuration
{
    public static function get(string $key): mixed
    {
        self::assertKnown($key);

        $row = Doctrine::em()
            ->getRepository(AuthConfig::class)
            ->findOneBy([
                'configKey' => $key,
            ]);

        if (!$row instanceof AuthConfig) {
            return AuthDefaults::value($key);
        }

        try {
            return json_decode(
                $row->getValue(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new \UnexpectedValueException(
                'Stored authentication configuration is invalid.',
                0,
                $exception
            );
        }
    }

    public static function set(
        string $key,
        mixed $value
    ): void {
        self::assertKnown($key);

        $encoded = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_THROW_ON_ERROR
        );

        $connection = Doctrine::em()->getConnection();
        $now = self::now()->format('Y-m-d H:i:s');

        $updated = $connection->update(
            'auth__config',
            [
                'value' => $encoded,
                'updated_at' => $now,
            ],
            [
                'config_key' => $key,
            ]
        );

        if ($updated > 0) {
            return;
        }

        try {
            $connection->insert(
                'auth__config',
                [
                    'config_key' => $key,
                    'value' => $encoded,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            $connection->update(
                'auth__config',
                [
                    'value' => $encoded,
                    'updated_at' => $now,
                ],
                [
                    'config_key' => $key,
                ]
            );
        }
    }

    public static function remove(string $key): void
    {
        self::assertKnown($key);

        Doctrine::em()
            ->getConnection()
            ->delete(
                'auth__config',
                [
                    'config_key' => $key,
                ]
            );
    }

    private static function assertKnown(string $key): void
    {
        if (!AuthDefaults::has($key)) {
            throw new \InvalidArgumentException(
                'Unknown authentication configuration key.'
            );
        }
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );
    }
}

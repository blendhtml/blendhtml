<?php

declare(strict_types=1);

namespace Blendhtml\Core\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260920130000CreateBlendhtmlAdminUser extends AbstractMigration
{
    private const EMAIL = 'blendhtml@nikolajev.ee';

    private const ROLE = 'admin';

    public function getDescription(): string
    {
        return 'Create the Blendhtml admin user.';
    }

    public function up(Schema $schema): void
    {
        $now = new \DateTimeImmutable();

        $userId = $this->connection->fetchOne(
            'SELECT id FROM auth__users WHERE email = ?',
            [self::EMAIL]
        );

        if ($userId === false) {
            $this->connection->insert(
                'auth__users',
                [
                    'email' => self::EMAIL,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'email' => Types::STRING,
                    'created_at' => Types::DATETIME_IMMUTABLE,
                    'updated_at' => Types::DATETIME_IMMUTABLE,
                ]
            );

            $userId = $this->connection->lastInsertId();
        }

        $roleId = $this->connection->fetchOne(
            'SELECT id FROM auth__roles WHERE name = ?',
            [self::ROLE]
        );

        if ($roleId === false) {
            throw new \RuntimeException(
                'The admin role does not exist. Ensure the role seed migration runs first.'
            );
        }

        $assignmentExists = $this->connection->fetchOne(
            <<<'SQL'
                SELECT 1
                FROM auth__user_roles
                WHERE user_id = ? AND role_id = ?
                SQL,
            [$userId, $roleId]
        );

        if ($assignmentExists === false) {
            $this->connection->insert(
                'auth__user_roles',
                [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ],
                [
                    'user_id' => Types::INTEGER,
                    'role_id' => Types::INTEGER,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $userId = $this->connection->fetchOne(
            'SELECT id FROM auth__users WHERE email = ?',
            [self::EMAIL]
        );

        if ($userId === false) {
            return;
        }

        $roleId = $this->connection->fetchOne(
            'SELECT id FROM auth__roles WHERE name = ?',
            [self::ROLE]
        );

        if ($roleId !== false) {
            $this->connection->delete(
                'auth__user_roles',
                [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ]
            );
        }

        $this->connection->delete(
            'auth__users',
            ['id' => $userId]
        );
    }
}
<?php

declare(strict_types=1);

namespace Blendhtml\Core\Migrations;

use Blendhtml\Core\Auth\BlendhtmlRoles;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260920120000SeedBlendhtmlRoles extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed protected Blendhtml roles.';
    }

    public function up(Schema $schema): void
    {
        $now = new \DateTimeImmutable();

        foreach (BlendhtmlRoles::all() as $role) {
            $this->connection->insert(
                'auth__roles',
                [
                    'name' => $role,
                    'created_at' => $now,
                ],
                [
                    'name' => Types::STRING,
                    'created_at' => Types::DATETIME_IMMUTABLE,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (BlendhtmlRoles::all() as $role) {
            $this->addSql(
                'DELETE FROM auth__roles WHERE name = ?',
                [$role]
            );
        }
    }
}
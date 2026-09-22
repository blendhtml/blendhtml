<?php

declare(strict_types=1);

namespace Blendhtml\Core\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916160000CreateAuthTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Blendhtml passwordless authentication tables.';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->createTable('auth__users');
        $users->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $users->addColumn(
            'email',
            Types::STRING,
            [
                'length' => 254,
            ]
        );
        $users->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $users->addColumn(
            'updated_at',
            Types::DATETIME_IMMUTABLE
        );
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(
            ['email'],
            'uniq_auth_users_email'
        );

        $roles = $schema->createTable('auth__roles');
        $roles->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $roles->addColumn(
            'name',
            Types::STRING,
            [
                'length' => 64,
            ]
        );
        $roles->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $roles->setPrimaryKey(['id']);
        $roles->addUniqueIndex(
            ['name'],
            'uniq_auth_roles_name'
        );

        $userRoles =
            $schema->createTable('auth__user_roles');
        $userRoles->addColumn(
            'user_id',
            Types::INTEGER
        );
        $userRoles->addColumn(
            'role_id',
            Types::INTEGER
        );
        $userRoles->setPrimaryKey([
            'user_id',
            'role_id',
        ]);
        $userRoles->addIndex(
            ['role_id'],
            'idx_auth_user_roles_role'
        );
        $userRoles->addForeignKeyConstraint(
            'auth__users',
            ['user_id'],
            ['id'],
            [
                'onDelete' => 'CASCADE',
            ],
            'fk_auth_user_roles_user'
        );
        $userRoles->addForeignKeyConstraint(
            'auth__roles',
            ['role_id'],
            ['id'],
            [
                'onDelete' => 'CASCADE',
            ],
            'fk_auth_user_roles_role'
        );

        $sessions =
            $schema->createTable('auth__sessions');
        $sessions->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $sessions->addColumn(
            'user_id',
            Types::INTEGER
        );
        $sessions->addColumn(
            'secret_lookup',
            Types::STRING,
            [
                'length' => 64,
            ]
        );
        $sessions->addColumn(
            'secret_hash',
            Types::STRING,
            [
                'length' => 255,
            ]
        );
        $sessions->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $sessions->addColumn(
            'expires_at',
            Types::DATETIME_IMMUTABLE
        );
        $sessions->addColumn(
            'last_used_at',
            Types::DATETIME_IMMUTABLE
        );
        $sessions->addColumn(
            'revoked_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => false,
            ]
        );
        $sessions->setPrimaryKey(['id']);
        $sessions->addUniqueIndex(
            ['secret_lookup'],
            'uniq_auth_sessions_lookup'
        );
        $sessions->addIndex(
            ['user_id', 'revoked_at'],
            'idx_auth_sessions_user_revoked'
        );
        $sessions->addIndex(
            ['expires_at'],
            'idx_auth_sessions_expires'
        );
        $sessions->addForeignKeyConstraint(
            'auth__users',
            ['user_id'],
            ['id'],
            [
                'onDelete' => 'CASCADE',
            ],
            'fk_auth_sessions_user'
        );

        $loginCodes =
            $schema->createTable('auth__login_codes');
        $loginCodes->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $loginCodes->addColumn(
            'email',
            Types::STRING,
            [
                'length' => 254,
            ]
        );
        $loginCodes->addColumn(
            'active_email',
            Types::STRING,
            [
                'length' => 254,
                'notnull' => false,
            ]
        );
        $loginCodes->addColumn(
            'code_hash',
            Types::STRING,
            [
                'length' => 255,
            ]
        );
        $loginCodes->addColumn(
            'code_length',
            Types::SMALLINT
        );
        $loginCodes->addColumn(
            'max_attempts',
            Types::SMALLINT
        );
        $loginCodes->addColumn(
            'attempts',
            Types::SMALLINT,
            [
                'default' => 0,
            ]
        );
        $loginCodes->addColumn(
            'flow_lookup',
            Types::STRING,
            [
                'length' => 64,
            ]
        );
        $loginCodes->addColumn(
            'redirect_path',
            Types::STRING,
            [
                'length' => 2048,
            ]
        );
        $loginCodes->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $loginCodes->addColumn(
            'expires_at',
            Types::DATETIME_IMMUTABLE
        );
        $loginCodes->addColumn(
            'consumed_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => false,
            ]
        );
        $loginCodes->addColumn(
            'invalidated_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => false,
            ]
        );
        $loginCodes->setPrimaryKey(['id']);
        $loginCodes->addUniqueIndex(
            ['active_email'],
            'uniq_auth_login_codes_active'
        );
        $loginCodes->addUniqueIndex(
            ['flow_lookup'],
            'uniq_auth_login_codes_flow'
        );
        $loginCodes->addIndex(
            ['email', 'created_at'],
            'idx_auth_login_codes_email_created'
        );
        $loginCodes->addIndex(
            ['expires_at'],
            'idx_auth_login_codes_expires'
        );

        $rateLimits =
            $schema->createTable('auth__rate_limits');
        $rateLimits->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $rateLimits->addColumn(
            'action',
            Types::STRING,
            [
                'length' => 32,
            ]
        );
        $rateLimits->addColumn(
            'scope',
            Types::STRING,
            [
                'length' => 16,
            ]
        );
        $rateLimits->addColumn(
            'subject_hash',
            Types::STRING,
            [
                'length' => 64,
            ]
        );
        $rateLimits->addColumn(
            'window_started_at',
            Types::DATETIME_IMMUTABLE
        );
        $rateLimits->addColumn(
            'attempts',
            Types::INTEGER,
            [
                'default' => 0,
            ]
        );
        $rateLimits->addColumn(
            'updated_at',
            Types::DATETIME_IMMUTABLE
        );
        $rateLimits->setPrimaryKey(['id']);
        $rateLimits->addUniqueIndex(
            [
                'action',
                'scope',
                'subject_hash',
            ],
            'uniq_auth_rate_limit_subject'
        );
        $rateLimits->addIndex(
            ['updated_at'],
            'idx_auth_rate_limits_updated'
        );

        $config = $schema->createTable('auth__config');
        $config->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $config->addColumn(
            'config_key',
            Types::STRING,
            [
                'length' => 64,
            ]
        );
        $config->addColumn(
            'value',
            Types::TEXT
        );
        $config->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $config->addColumn(
            'updated_at',
            Types::DATETIME_IMMUTABLE
        );
        $config->setPrimaryKey(['id']);
        $config->addUniqueIndex(
            ['config_key'],
            'uniq_auth_config_key'
        );

        $whitelist =
            $schema->createTable('auth__email_whitelist');
        $whitelist->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $whitelist->addColumn(
            'email',
            Types::STRING,
            [
                'length' => 254,
            ]
        );
        $whitelist->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $whitelist->setPrimaryKey(['id']);
        $whitelist->addUniqueIndex(
            ['email'],
            'uniq_auth_email_whitelist_email'
        );

        $csrfTokens =
            $schema->createTable('auth__csrf_tokens');
        $csrfTokens->addColumn(
            'id',
            Types::INTEGER,
            [
                'autoincrement' => true,
            ]
        );
        $csrfTokens->addColumn(
            'token_hash',
            Types::STRING,
            [
                'length' => 64,
            ]
        );
        $csrfTokens->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE
        );
        $csrfTokens->addColumn(
            'expires_at',
            Types::DATETIME_IMMUTABLE
        );
        $csrfTokens->addColumn(
            'last_used_at',
            Types::DATETIME_IMMUTABLE
        );
        $csrfTokens->setPrimaryKey(['id']);
        $csrfTokens->addUniqueIndex(
            ['token_hash'],
            'uniq_auth_csrf_token_hash'
        );
        $csrfTokens->addIndex(
            ['expires_at'],
            'idx_auth_csrf_tokens_expires'
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('auth__csrf_tokens');
        $schema->dropTable('auth__email_whitelist');
        $schema->dropTable('auth__config');
        $schema->dropTable('auth__rate_limits');
        $schema->dropTable('auth__login_codes');
        $schema->dropTable('auth__sessions');
        $schema->dropTable('auth__user_roles');
        $schema->dropTable('auth__roles');
        $schema->dropTable('auth__users');
    }
}

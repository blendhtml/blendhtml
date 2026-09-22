<?php

declare(strict_types=1);

namespace Blendhtml\Core\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907130000CreateVisitMetricsV1Tables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create visit metrics v1 tables';
    }

    public function up(Schema $schema): void
    {
        # visit_metrics_v1

        $table = $schema->createTable('visit_metrics_v1');

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('action', 'string', [
            'length' => 32,
        ]);

        $table->addColumn('event', 'string', [
            'length' => 255,
            'notnull' => false,
        ]);

        $table->addColumn('seconds', 'integer', [
            'notnull' => false,
        ]);

        $table->addColumn('ref', 'string', [
            'length' => 255,
            'notnull' => false,
        ]);

        $table->addColumn('event_id', 'string', [
            'length' => 255,
        ]);

        $table->addColumn('client_timestamp', 'datetime_immutable');

        $table->addColumn('referrer', 'string', [
            'length' => 2048,
            'notnull' => false,
        ]);

        $table->addColumn('visitor_token', 'string', [
            'length' => 36,
            'notnull' => false,
        ]);

        $table->addColumn('meta', 'json', [
            'notnull' => false,
        ]);

        $table->addColumn('timestamp', 'datetime_immutable');

        $table->setPrimaryKey(['id']);


        # visit_metrics_consent_v1

        $table = $schema->createTable('visit_metrics_consent_v1');

        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('action', 'string', [
            'length' => 16,
        ]);

        $table->addColumn('referrer', 'string', [
            'length' => 2048,
            'notnull' => false,
        ]);

        $table->addColumn('visitor_token', 'string', [
            'length' => 36,
            'notnull' => false,
        ]);

        $table->addColumn('location', 'json', [
            'notnull' => false,
        ]);

        $table->addColumn('errors', 'json');

        $table->addColumn('timestamp', 'datetime_immutable');

        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('visit_metrics_v1');
        $schema->dropTable('visit_metrics_consent_v1');
    }
}
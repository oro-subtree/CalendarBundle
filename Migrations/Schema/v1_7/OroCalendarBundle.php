<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_system_calendar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addcolumn('is_public', 'boolean', ['default' => false]);

        $table->addIndex(['organization_id'], 'IDX_1DE3E2F032C8A3DE', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->setPrimaryKey(['id']);

        $table = $schema->getTable('oro_calendar_event');
        $table->changeColumn('calendar_id', ['integer', 'notnull' => false]);
        $table->addColumn('system_calendar_id', 'integer', ['notnull' => false]);
        $table->addIndex(['system_calendar_id', 'start_at', 'end_at'], 'oro_sys_calendar_event_idx', []);
        $table->addIndex(['system_calendar_id'], 'IDX_2DDC40DD55F0F9D0', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_system_calendar'),
            ['system_calendar_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}

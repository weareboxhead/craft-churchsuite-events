<?php

namespace boxhead\craftchurchsuiteevents\migrations;

use Craft;
use craft\db\Migration;

/**
 * m231018_133000_create_table migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%churchsuite_events}}')) {
            // Create the ChurchSuite Events table:
            $this->createTable('{{%churchsuite_events}}', [
                'id' => $this->primaryKey(),
                'churchSuiteId' => $this->integer()->notNull(),
                'identifier' => $this->string(255)->notNull(),
                'publicVisible' => $this->boolean()->notNull()->defaultValue(false),
                'publicFeatured' => $this->boolean()->notNull()->defaultValue(false),
                'embedVisible' => $this->boolean()->notNull()->defaultValue(false),
                'startDate' => $this->dateTime()->notNull(),
                'endDate' => $this->dateTime()->notNull(),
                'name' => $this->string(255)->notNull(),
                'description' => $this->text(),
                'capacity' => $this->integer(),
                'locationName' => $this->string(255),
                'locationAddress' => $this->text(),
                'locationLatitude' => $this->string(255),
                'locationLongitude' => $this->string(255),
                'locationType' => $this->string(255),
                'locationUrl' => $this->string(255),
                'image' => $this->string(255),
                'ticketsEnabled' => $this->boolean()->notNull()->defaultValue(false),
                'ticketUrl' => $this->string(255),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            // Give it a foreign key to the elements table:
            $this->addForeignKey(
                null,
                '{{%churchsuite_events}}',
                'id',
                '{{%elements}}',
                'id',
                'CASCADE',
                null
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Drop the table?
        $this->dropTableIfExists('{{%churchsuite_events}}');
        return true;
    }
}

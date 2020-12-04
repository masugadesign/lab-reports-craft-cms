<?php

namespace Masuga\LabReports\migrations;

use Craft;
use craft\db\Migration;

class Install extends Migration
{

	public function safeUp()
	{
		if (!$this->db->tableExists('{{%labreports_configured_reports}}')) {
			$this->createTable('{{%labreports_configured_reports}}', [
				'id' => $this->primaryKey(),
				'title' => $this->string(255),
				'reportDescription' => $this->string(255),
				'template' => $this->string(255),
				'type' => $this->string(25),
				'formatFunction' => $this->string(100),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]);
		}

		if (!$this->db->tableExists('{{%labreports_reports}}')) {
			$this->createTable('{{%labreports_reports}}', [
				'id' => $this->primaryKey(),
				'reportConfiguredId' => $this->integer(),
				'filename' => $this->string(255),
				'totalRows' => $this->integer(),
				'userId' => $this->integer(),
				'dateGenerated' => $this->dateTime(),
				'dateCreated' => $this->dateTime()->notNull(),
				'dateUpdated' => $this->dateTime()->notNull(),
				'uid' => $this->uid()
			]);
		}

	}

	public function safeDown()
	{
		if ( $this->db->tableExists('{{%labreports_reports}}') ) {
			$this->dropTable('{{%labreports_reports}}');
		}
		if ( $this->db->tableExists('{{%labreports_configured_reports}}') ) {
			$this->dropTable('{{%labreports_configured_reports}}');
		}
	}
}

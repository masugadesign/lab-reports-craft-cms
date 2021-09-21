<?php

namespace Masuga\LabReports\records;

use craft\db\ActiveRecord;
use craft\base\Element;
use yii\db\ActiveQueryInterface;
use Masuga\LabReports\elements\Report;

class ConfiguredReportRecord extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return '{{%labreports_configured_reports}}';
	}

	/**
	 * Returns the download record's element.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getElement(): ActiveQueryInterface
	{
		return $this->hasOne(Element::class, ['id' => 'id']);
	}

	/**
	 * Returns all the reports ran for this configured report.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getGeneratedReports(): ActiveQueryInterface
	{
		return $this->hasMany(Report::class, ['configuredReportId' => 'id']);
	}

}

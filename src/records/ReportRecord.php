<?php

namespace Masuga\LabReports\records;

use craft\db\ActiveRecord;
use craft\base\Element;
use yii\db\ActiveQueryInterface;
use Masuga\LabReports\elements\ConfiguredReportReport;

class ReportRecord extends ActiveRecord
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
	public function getConfiguredReports(): ActiveQueryInterface
	{
		return $this->hasOne(ConfiguredReport::class, ['id' => 'reportId']);
	}

	/**
	 * Returns the download record's related User.
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getUser(): ActiveQueryInterface
	{
		return $this->hasOne(User::class, ['id' => 'userId']);
	}

}

<?php

namespace Masuga\LabReports\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\Report;

class ReportQuery extends ElementQuery
{

	public $dateGenerated = null;
	public $configuredReportId = null;
	public $after = null;
	public $before = null;

	/**
	 * The instance of the Lab Reports plugin.
	 * @var LabReports
	 */
	private $plugin = null;

	/**
 	* @inheritdoc
 	*/
	protected $defaultOrderBy = ['labreports_configured_reports.dateCreated' => SORT_DESC];

	public function init()
	{
		parent::init();
		$this->plugin = LabReports::getInstance();
	}

	/**
	 * @inheritdoc
	 */
	protected function beforePrepare(): bool
	{
		$this->joinElementTable('labreports_reports');

		$selectsArray = [
			'labreports_reports.dateGenerated'
		];
		$this->query->select($selectsArray);

		if ($this->after) {
			$this->subQuery->andWhere(Db::parseDateParam('labreports_reports.dateGenerated', $this->after, '>'));
		}
		if ($this->before) {
			$this->subQuery->andWhere(Db::parseDateParam('labreports_reports.dateGenerated', $this->before, '<'));
		}
		if ($this->dateGenerated) {
			$this->subQuery->andWhere(Db::parseDateParam('labreports_reports.dateGenerated', $this->dateGenerated));
		}
		if ($this->configuredReportId) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.reportConfiguredId', $this->configuredReportId));
		}
		return parent::beforePrepare();
	}

	/**
	 * Set the reportConfiguredId query parameter.
	 * @param mixed $value
	 * @return static self
	 */
	public function configuredReportId($value): ReportQuery
	{
		$this->configuredReportId = $value;
		return $this;
	}

}

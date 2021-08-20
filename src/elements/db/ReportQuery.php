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
	public $reportConfiguredId = null;
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
	protected $defaultOrderBy = ['labreports_reports.dateGenerated' => SORT_DESC];

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
			'labreports_reports.dateCreated',
			'labreports_reports.dateUpdated',
			'labreports_reports.dateGenerated',
			'labreports_reports.reportConfiguredId',
			'labreports_reports.reportStatus',
			'labreports_reports.filename',
			'labreports_reports.totalRows',
			'labreports_reports.userId',
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
		if ($this->reportConfiguredId) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.reportConfiguredId', $this->reportConfiguredId));
		}
		return parent::beforePrepare();
	}

	/**
	 * Set the reportConfiguredId query parameter.
	 * @param mixed $value
	 * @return static self
	 */
	public function reportConfiguredId($value): ReportQuery
	{
		$this->reportConfiguredId = $value;
		return $this;
	}

}

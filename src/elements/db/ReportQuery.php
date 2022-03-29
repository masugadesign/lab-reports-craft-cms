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
	public $reportStatus = null;
	public $statusMessage = null;
	public $filename = null;
	public $totalRows = null;
	public $userId = null;
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
	protected array $defaultOrderBy = ['labreports_reports.dateGenerated' => SORT_DESC];

	public function init(): void
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
			'labreports_reports.configuredReportId',
			'labreports_reports.reportStatus',
			'labreports_reports.statusMessage',
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
		if ($this->configuredReportId) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.configuredReportId', $this->configuredReportId));
		}
		if ($this->userId) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.userId', $this->userId));
		}
		if ($this->filename) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.filename', $this->filename));
		}
		if ($this->reportStatus) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.reportStatus', $this->reportStatus));
		}
		if ($this->totalRows) {
			$this->subQuery->andWhere(Db::parseParam('labreports_reports.totalRows', $this->totalRows));
		}
		return parent::beforePrepare();
	}

	/**
	 * Set the configuredReportId query parameter.
	 * @param mixed $value
	 * @return static self
	 */
	public function configuredReportId($value): ReportQuery
	{
		$this->configuredReportId = $value;
		return $this;
	}

	/**
	 * This method assigns the `userId` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function userId($value): ReportQuery
	{
		$this->userId = $value;
		return $this;
	}

	/**
	 * This method assigns the `dateGenerated` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function dateGenerated($value): ReportQuery
	{
		$this->dateGenerated = $value;
		return $this;
	}

	/**
	 * This method assigns the `reportStatus` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function reportStatus($value): ReportQuery
	{
		$this->reportStatus = $value;
		return $this;
	}

	/**
	 * This method assigns the `filename` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function filename($value): ReportQuery
	{
		$this->filename = $value;
		return $this;
	}

	/**
	 * This method assigns the `totalRows` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function totalRows($value): ReportQuery
	{
		$this->totalRows = $value;
		return $this;
	}

}

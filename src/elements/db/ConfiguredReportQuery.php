<?php

namespace Masuga\LabReports\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\ConfiguredReport;

class ConfiguredReportQuery extends ElementQuery
{

	public $reportType = null;
	public $reportTitle = null;
	public $reportDescription = null;
	public $template = null;
	public $formatFunction = null;
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
		$this->joinElementTable('labreports_configured_reports');

		$selectsArray = [
			"labreports_configured_reports.reportType",
			"labreports_configured_reports.reportTitle",
			"labreports_configured_reports.reportDescription",
			"labreports_configured_reports.template",
			"labreports_configured_reports.formatFunction",
			"labreports_configured_reports.dateCreated",
			"labreports_configured_reports.dateUpdated"
		];
		$this->query->select($selectsArray);

		if ($this->reportType) {
			$this->subQuery->andWhere(Db::parseParam('labreports_configured_reports.reportType', $this->reportType));
		}
		if ($this->reportTitle) {
			$this->subQuery->andWhere(Db::parseParam('labreports_configured_reports.reportTitle', $this->reportTitle));
		}
		if ($this->template) {
			$this->subQuery->andWhere(Db::parseParam('labreports_configured_reports.template', $this->template));
		}
		if ($this->formatFunction) {
			$this->subQuery->andWhere(Db::parseParam('labreports_configured_reports.formatFunction', $this->formatFunction));
		}
		if ($this->after) {
			$this->subQuery->andWhere(Db::parseDateParam('labreports_configured_reports.dateCreated', $this->after, '>'));
		}
		if ($this->before) {
			$this->subQuery->andWhere(Db::parseDateParam('labreports_configured_reports.dateCreated', $this->before, '<'));
		}
		if ($this->dateCreated) {
			$this->subQuery->andWhere(Db::parseDateParam('labreports_configured_reports.dateCreated', $this->dateCreated));
		}
		return parent::beforePrepare();
	}

	/**
	 * This method assigns the `reportType` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function reportType($value): ConfiguredReportQuery
	{
		$this->reportType = $value;
		return $this;
	}

	/**
	 * This method assigns the `reportTitle` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function reportTitle($value): ConfiguredReportQuery
	{
		$this->reportTitle = $value;
		return $this;
	}

	/**
	 * This method assigns the `reportDescription` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function reportDescription($value): ConfiguredReportQuery
	{
		$this->reportDescription = $value;
		return $this;
	}

	/**
	 * This method assigns the `template` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function template($value): ConfiguredReportQuery
	{
		$this->template = $value;
		return $this;
	}

	/**
	 * This method assigns the `formatFunction` query parameter value.
	 * @param mixed $value
	 * @return self
	 */
	public function formatFunction($value): ConfiguredReportQuery
	{
		$this->formatFunction = $value;
		return $this;
	}

}

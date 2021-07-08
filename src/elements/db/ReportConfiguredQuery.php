<?php

namespace Masuga\LabReports\elements\db;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\ReportConfigured;

class ReportConfiguredQuery extends ElementQuery
{

	public $after = null;
	public $before = null;
	public $reportTitle = null;
	public $reportDescription = null;
	public $reportType = null;
	public $template = null;
	public $formatFunction = null;

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

		//$selectsArray = [];
		//$this->query->select($selectsArray);

		//if ($this->title) {
		//	$this->subQuery->andWhere(Db::parseParam('labreports_configured_reports.title', $this->title));
		//}
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
	 * Set the reportConfiguredId query parameter.
	 * @param mixed $value
	 * @return static self
	 */
	public function configuredReportId($value): ReportConfiguredQuery
	{
		$this->configuredReportId = $value;
		return $this;
	}

}

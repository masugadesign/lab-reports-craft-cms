<?php

namespace Masuga\LabReports\variables;

use Craft;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\ReportConfiguredQuery;
use Masuga\LabReports\ReportQuery;

class LabReportsVariable
{

	/**
	 * The instance of the LabReports plugin class.
	 * @var LabReports
	 */
	private $plugin = null;

	public function __construct()
	{
		$this->plugin = LabReports::getInstance();
	}

	/**
	 * This template variable returns a query for fetching configured reports.
	 * @param array $criteria
	 * @return ReportConfiguredQuery
	 */
	public function configuredReports($criteria=[]): ReportConfiguredQuery
	{
		return $this->plugin->reports->configuredReportsQuery($criteria);
	}

	/**
	 * This template variable returns a query for fetching generated reports.
	 * @param array $criteria
	 * @return ReportQuery
	 */
	public function generatedReports($criteria=[]): ReportQuery
	{
		return $this->plugin->reports->generatedReportsQuery($criteria);
	}

}

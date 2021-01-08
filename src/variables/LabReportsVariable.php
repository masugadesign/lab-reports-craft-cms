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

	/**
	 * This template variable returns an array of the available format functions
	 * defined in the plugin config file.
	 * @return array
	 */
	public function formatFunctions()
	{
		return $this->plugin->reports->formatFunctions();
	}

	public function formatFunctionOptions()
	{
		$opts = array_keys($this->plugin->reports->formatFunctions());
		$options = ['' => 'Select Function...']+array_combine($opts, $opts);
		return $options;
	}

}

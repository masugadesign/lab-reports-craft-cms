<?php

namespace Masuga\LabReports\console\controllers;

use Craft;
//use Masuga\LabReports\elements\Report;
//use Masuga\LabReports\elements\ReportConfigured;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\queue\jobs\GenerateReport;
use yii\console\Controller;
use yii\console\ExitCode;

class ReportsController extends Controller
{

	/**
	 * The Lab Reports Configured Report (ReportConfigured element) ID.
	 * @var int
	 */
	public $reportId;

	/**
	 * @inheritdoc
	 */
	public function options($actionID)
	{
		$options = parent::options($actionID);
		$options[] = 'reportId';
		return $options;
	}

	/**
	 * Generate a report by its Configured Report ID.
	 * php craft labreports/reports/build --id=1
	 */
	public function actionBuild(): int
	{
		$plugin = LabReports::getInstance();
		if ( ! $this->reportId ) {
			$plugin->reports->log("labreports/reports/build called without the `reportId` option.");
			$this->stderr("`reportId` is a required option.".PHP_EOL);
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$queue = Craft::$app->getQueue();
		$rc = $plugin->reports->getReportConfiguredById($this->reportId);
		if ( ! $rc ) {
			$plugin->reports->log("labreports/reports/build called with invalid `reportId` option.");
			$this->stderr("Invalid ReportConfigured ID `{$this->reportId}`.".PHP_EOL);
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$job = new GenerateReport(['reportConfiguredId' => $this->reportId]);
		$queue->delay(0)->push($job);
		return ExitCode::OK;
	}
}

<?php

namespace Masuga\LabReports\console\controllers;

use Craft;
//use Masuga\LabReports\elements\Report;
//use Masuga\LabReports\elements\ReportConfigured;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\queue\jobs\GenerateReport;
use yii\console\Controller;

class ReportsController extends Controller
{

	/**
	 * @inheritdoc
	 */
	public function options($actionID)
	{
		$options = parent::options($actionID);
		$options[] = 'id';
		return $options;
	}

	/**
	 * Generate a report by its Configured Report ID.
	 * php craft labreports/reports/build --id=1
	 */
	public function actionBuild()
	{
		if ( ! $this->id ) {
			$this->stderr("`id` is a required option.");
		}
		$queue = Craft::$app->getQueue();
		$rc = LabReports::getInstance()->reports->getReportConfiguredById($this->id);
		if ( ! $rc ) {
			$this->stderr("Invalid ReportConfigured ID `{$this->id}`.");
		}
		$job = new GenerateReport(['reportConfiguredId' => $this->id]);
		$queue->delay(0)->push($job);
	}
}

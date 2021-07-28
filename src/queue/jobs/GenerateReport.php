<?php

namespace Masuga\LabReports\queue\jobs;

use craft\elements\Entry;
use craft\queue\BaseJob;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\LabReports;

class GenerateReport extends BaseJob
{

	/**
	 * The instance of the Craft Queue
	 *
	 */
	protected $queue = null;

	/**
	 * The ReportConfigured element ID used to generate a report.
	 * @var int
	 */
	public $reportConfiguredId = null;

	private $_reportTitle = null;

	public function init()
	{
		$plugin = LabReports::getInstance();
		$rc = $plugin->reports->getReportConfiguredById($this->reportConfiguredId);
		$this->_reportTitle = $rc->reportTitle;
	}

	/**
	 * @inheritdoc
	 */
	public function execute($queue)
	{
		$this->queue =& $queue;
		$plugin = LabReports::getInstance();
		$rc = $plugin->reports->getReportConfiguredById($this->reportConfiguredId);
		$plugin->reports->run($rc, $this);
	}

	/**
	 * A public version of the setProgress method so we can update the job progress
	 * from another class.
	 * @param float $progress
	 * @param string $message
	 */
	public function updateProgress($progress, $message=null)
	{
		$this->setProgress($this->queue, $progress, $message);
	}

	/**
	 * The description that gets stored in the queue record.
	 * @return string
	 */
	protected function defaultDescription(): string
	{
		return "Generating '{$this->_reportTitle}' report.";
	}

}

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
	 * The *generated* Report element.
	 * @var Report
	 */
	protected $report = null;

	/**
	 * @inheritdoc
	 */
	public function execute($queue)
	{
		$this->queue =& $queue;
		$plugin = LabReports::getInstance();

	}

	/**
	 * A public version of the setProgress method so we can update the job progress
	 * from another class.
	 * @param float $progress
	 */
	public function updateProgress($progress)
	{
		$this->setProgress($this->queue, $progress);
	}

	/**
	 * The description that gets stored in the queue record.
	 * @return string
	 */
	protected function defaultDescription(): string
	{
		return "Generating {$report->filename} report.";
	}

}

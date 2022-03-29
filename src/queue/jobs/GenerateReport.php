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
	 * The ConfiguredReport element ID used to generate a report.
	 * @var int
	 */
	public $configuredReportId = null;

	private $_reportTitle = null;

	public function init(): void
	{
		parent::init();
		$plugin = LabReports::getInstance();
		$cr = $plugin->reports->getConfiguredReportById($this->configuredReportId);
		$this->_reportTitle = $cr->reportTitle;
	}

	/**
	 * @inheritdoc
	 */
	public function execute($queue): void
	{
		$this->queue =& $queue;
		$plugin = LabReports::getInstance();
		if ( $plugin->getConfigItem('debug') ) {
			$plugin->reports->log("[DEBUG] - GenerateReport queue job STARTED.");
		}
		$cr = $plugin->reports->getConfiguredReportById($this->configuredReportId);
		$plugin->reports->run($cr, $this);
		if ( $plugin->getConfigItem('debug') ) {
			$plugin->reports->log("[DEBUG] - GenerateReport queue job FINISHED.");
		}
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
	protected function defaultDescription(): ?string
	{
		return "Generating '{$this->_reportTitle}' report.";
	}

}

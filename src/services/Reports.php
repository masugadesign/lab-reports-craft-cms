<?php

namespace Masuga\LabReports\services;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use Masuga\LabReports\base\Service;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\elements\ConfiguredReport;
use Masuga\LabReports\elements\db\ConfiguredReportQuery;
use Masuga\LabReports\elements\db\ReportQuery;

class Reports extends Service
{

	/**
	 * The batch size used when paginating through query results.
	 * @var int
	 */
	private const BATCH_SIZE = 50;

	public function init()
	{
		parent::init();
	}

	/**
	 * This method returns the system path to the report file storage folder.
	 * @return string
	 */
	public function storagePath()
	{
		$folderPath = $this->plugin->getSettings()->fileStorageFolder;
		if ( ! file_exists($folderPath) ) {
			$made = mkdir($folderPath, 0777, true);
			if ( ! $made ) {
				$this->log("Error creating Lab Reports folder at `{$folderPath}`.");
				die("Error creating Lab Reports report folder. See labreports.log for details.");
			}
		}
		return $folderPath;
	}

	/**
	 * This method executes a particular configured report.
	 * @param ConfiguredReport $rc
	 */
	public function run(ConfiguredReport $rc, $queueJob=null)
	{
		$report = (new Report())->setConfiguredReport($rc);
		$view = Craft::$app->getView();
		/*
		Craft throws craft\errors\UnsupportedSiteException when siteId is null.
		No idea why it is null or has to be set manually as that isn't the norm.
		*/
		$report->siteId = Craft::$app->getSites()->currentSite->id;
		$user = Craft::$app->getUser()->getIdentity();
		$report->userId = $user ? $user->id : null;
		$saved = Craft::$app->getElements()->saveElement($report);
		$report->setQueueJob($queueJob);
		$report->updateStatus('in_progress');
		// When run in the queue, Craft has *not* set the site templates path.
		$view->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());
		$parsedReportTemplate = $view->renderTemplate($rc->template, [
			'report' => $report
		]);
		$report->updateStatus('finished');
		return $report->fileExists() ? $report : null;
	}

	/**
	 * This method converts an array of elements to a CSV file stored in the Craft
	 * temp path.
	 */
	public function generateCsvFile($entries, $basename)
	{
		// Items in the array might be objects, convert the object(s) to an array.
		$arrayContent = ArrayHelper::toArray($entries);
		foreach($arrayContent as $rowIndex => &$record) {
			// Let's add the column names as a row to the CSV array content.
			if ( $rowIndex === 0 ) {
				array_unshift($arrayContent, array_keys($record));
			}
			// There may be array values in each item array. We need to flatten those.
			foreach($record as $fieldName => &$fieldValue) {
				if ( is_array($fieldValue) ) {
					$fieldValue = json_encode($fieldValue);
				}
			}
		}
		$csvContent = $this->arrayToCsv($arrayContent);
		$filePath = Craft::$app->path->getTempPath().DIRECTORY_SEPARATOR.$basename.'.csv';
		FileHelper::writeToFile($filePath, $csvContent);
		return file_exists($filePath) ? $filePath : null;
	}

	/**
	 * This method converts an array of arrays content to a CSV string.
	 * @param array
	 * @return string
	 */
	public function arrayToCsv($arr=[]): string
	{
		ob_start();
		$f = fopen('php://output', 'w') or show_error("Can't open php://output");
		foreach ($arr as &$line) {
			fputcsv($f, $line, ',');
		}
		fclose($f) or show_error("Can't close php://output");
		$csvContent = ob_get_contents();
		ob_end_clean();
		return (string) $csvContent;
	}

	/**
	 * This methods returns the batch size value.
	 * @return int
	 */
	public function batchSize(): int
	{
		return self::BATCH_SIZE;
	}

	/**
	 * This method creates/updates a RecordConfigured element depending on whether
	 * or not an existing ID was supplied.
	 * @param array $data
	 * @param int $id
	 * @return ConfiguredReport|null
	 */
	public function saveConfiguredReport($data, $id=null): ?ConfiguredReport
	{
		$rc = $id ? $this->getConfiguredReportById($id) : new ConfiguredReport;
		$saved = false;
		// Check it is populated in case someone supplied a bad ID.
		if ( $rc ) {
			$rc->reportType = $data['reportType'] ?? $rc->reportType;
			$rc->reportTitle = $data['reportTitle'] ?? $rc->reportTitle;
			$rc->reportDescription = $data['reportDescription'] ?? $rc->reportDescription;
			$rc->template = $data['template'] ?? $rc->template;
			$rc->formatFunction = $data['formatFunction'] ?? $rc->formatFunction;
			$saved = Craft::$app->getElements()->saveElement($rc);
		} elseif ( $this->plugin->getConfigItem('debug')) {
			$this->log("ConfiguredReport with ID `{$id}` not found.");
		}
		return $rc;
	}

	/**
	 * This method fetches a single ConfiguredReport element by ID or returns `null`
	 * if it does not exist.
	 * @param int $id
	 * @return ConfiguredReport|null
	 */
	public function getConfiguredReportById($id): ?ConfiguredReport
	{
		return ConfiguredReport::find()->id($id)->one();
	}

	/**
	 * This method returns a ConfiguredReportQuery with the supplied criteria
	 * applied to the query.
	 * @param array $criteria
	 * @return ConfiguredReportQuery
	 */
	public function configuredReportsQuery($criteria): ConfiguredReportQuery
	{
		$query = ConfiguredReport::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	/**
	 * This method returns a ReportQuery with the supplied criteria applied
	 * to the query.
	 * @param array $criteria
	 * @return ReportQuery
	 */
	public function generatedReportsQuery($criteria): ReportQuery
	{
		$query = Report::find();
		if ($criteria) {
			Craft::configure($query, $criteria);
		}
		return $query;
	}

	/**
	 * This method returns a format function by name if it is found in the config.
	 * Otherwise, null is returned.
	 * @param string $name
	 * @return function|null
	 */
	public function formatFunction($name)
	{
		return $this->plugin->getConfigItem('functions')[$name] ?? null;
	}

	/**
	 * This method returns the array of format function names from the plugin config.
	 * @return array
	 */
	public function formatFunctionNames(): array
	{
		return array_keys($this->plugin->getConfigItem('functions'));
	}

	/**
	 * This method returns the array of format functions from the plugin config.
	 * @return array
	 */
	public function formatFunctions(): array
	{
		return $this->plugin->getConfigItem('functions');
	}

	/**
	 * This method deletes a Report element and its associated file. It returns
	 * a boolean `true` on success, `false` on failure.
	 * @param Report $report
	 * @return bool
	 */
	public function deleteReport(Report $report): bool
	{
		if ( $report->fileExists() ) {
			unlink( $report->filePath() );
		} else {
			$this->log("Report file `".$report->filePath()."` does not exist and cannot be deleted.");
		}
		$deleted = Craft::$app->getElements()->deleteElement($report);
		if ( ! $deleted ) {
			$this->log("Failed to delete Report element with filename `{$report->filename}`.");
		}
		return $deleted;
	}

}

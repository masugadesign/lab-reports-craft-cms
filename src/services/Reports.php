<?php

namespace Masuga\LabReports\services;

use Craft;
use Exception;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use Masuga\LabReports\base\Service;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\elements\ReportConfigured;
use Masuga\LabReports\elements\db\ReportConfiguredQuery;
use Masuga\LabReports\elements\db\ReportQuery;

class Reports extends Service
{

	private const BATCH_SIZE = 50;

	public function init()
	{
		parent::init();
	}

	public function run(ReportConfigured $rc)
	{
		$rc->run();
	}

	public function rows($array)
	{
		foreach($rows as &$row) {
			$this->row($row);
		}
	}

	public function row(array $row)
	{

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
	 * This method returns a ReportConfiguredQuery with the supplied criteria
	 * applied to the query.
	 * @param array $criteria
	 * @return ReportConfiguredQuery
	 */
	public function configuredReportsQuery($criteria): ReportConfiguredQuery
	{
		$query = ReportConfigured::find();
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

}

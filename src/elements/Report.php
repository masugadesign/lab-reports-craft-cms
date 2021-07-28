<?php

namespace Masuga\LabReports\elements;

use Craft;
use DateTime;
use DateTimeZone;
use Exception;
use craft\base\Element;
use craft\elements\User;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\ReportConfigured;
use Masuga\LabReports\elements\db\ReportQuery;
use Masuga\LabReports\exceptions\InvalidReportConfiguredException;
use Masuga\LabReports\queue\jobs\GenerateReport;

class Report extends Element
{

	public $reportConfiguredId = null;
	public $filename = null;
	public $totalRows = 0;
	public $dateGenerated = null;
	public $reportStatus = null;
	public $userId = null;

	public const BATCH_LIMIT = 25;

	/**
	 * A way to reference the queue job that spawned this report.
	 * @var GenerateReport
	 */
	private $_queueJob = null;

	/**
	 * A place to store the related ReportConfigured element.
	 * @var ReportConfigured
	 */
	private $_reportConfigured = null;

	/**
	 * The element query used to generate *advanced* reports.
	 * @var ElementQuery
	 */
	private $query = null;

	/**
	 * A Craft User element that represents who triggered this report.
	 * @var User
	 */
	private $_user = null;

	/**
	 * Instance of the LabReports plugin.
	 * @var LabReports
	 */
	private $plugin = null;

	public function __construct($reportConfigured=null)
	{
		$this->plugin = LabReports::getInstance();
		if ( $reportConfigured instanceof ReportConfigured ) {
			$this->setReportConfigured($reportConfigured);
		} elseif ( $reportConfigured ) {
			throw new InvalidReportConfiguredException("Report::reportConfigured must be an instance of ReportConfigured.");
		}
	}

	/**
     * @inheritdoc
     * @return EntryQuery The newly created [[EntryQuery]] instance.
     */
	public static function find(): ElementQueryInterface
	{
		return new ReportQuery(static::class);
	}

	/**
	 * This method generates a report filename based on the related ReportConfigured
	 * and current LOCAL date.
	 * @param string $ext
	 * @return string
	 */
	private function generateFilename($ext='csv'): string
	{
		$localDate = $this->currentLocalDate()->format('YmdHis');
		$reportConfigured = $this->getReportConfigured();
		$title = $reportConfigured ? StringHelper::slugify($reportConfigured->reportTitle) : '';
		$filename = "{$title}-{$localDate}.{$ext}";
		return $filename;
	}

	/**
	 * This method returns the current local date/time as a DateTime object.
	 * @return DateTime
	 */
	public function currentLocalDate(): DateTime
	{
		$date = DateTimeHelper::currentUTCDateTime();
		$date->setTimeZone( new DateTimeZone(Craft::$app->getTimeZone()) );
		return $date;
	}

	/**
	 * This methods builds the report file based on an array of column headers
	 * and an element query.
	 * @param array $headers
	 * @param ElementQuery $query
	 * @return int
	 */
	public function build($headers, ElementQuery $query): int
	{
		$this->dateGenerated = DateTimeHelper::currentUTCDateTime()->format(DATE_ATOM);
		$rowsWritten = 0;
		$this->addColumnHeaders($headers);
		$currentTotalRows = $offset = 0;
		$grandTotalRows = $query->count();
		$formatFunction = $this->plugin->reports->formatFunction($this->_reportConfigured->formatFunction);
		do {
			if ( $this->_queueJob ) {
				$this->_queueJob->updateProgress(($currentTotalRows / $grandTotalRows), "{$currentTotalRows} of {$grandTotalRows} rows");
			}
			$elements = $query->limit(self::BATCH_LIMIT)->offset($offset)->all();
			$batchCount = count($elements);
			foreach($elements as &$element) {
				$row = $formatFunction($element);
				$written = $this->addRow($row);
				// The count of successfully written rows.
				$rowsWritten += $written ? 1 : 0;
				// The count of every row from every loop regardless of success.
				$currentTotalRows ++;
			}
			$offset += self::BATCH_LIMIT;
		} while ( $batchCount === self::BATCH_LIMIT );
		return $rowsWritten;
	}

	/**
	 * This method writes a report row to a designated filename. The system path
	 * is determined by the plugin config.
	 * @param string $filename
	 * @param array $row
	 * @return bool
	 */
	private function writeRow($filename, $row): bool
	{
		$filePath = $this->plugin->reports->storagePath().DIRECTORY_SEPARATOR.$filename;
		$lengthWritten = 0;
		$fp = fopen($filePath, 'a+');
		if ( $fp !== false ) {
			$lengthWritten = fputcsv($fp, $row, ',');
		}
		fclose($fp);
		return $lengthWritten > 0;
	}

	/**
	 * This method adds the row of column names to the report file. It is essentially
	 * the same as addRow except that it does not include the column names row in
	 * the totalRows tally.
	 * @param array $columnNames
	 * @return bool
	 */
	public function addColumnHeaders(array $columnNames): bool
	{
		return $this->addRow($columnNames);
	}

	/**
	 * This method adds a batch of rows to a generated report and returns the total
	 * number of rows added.
	 * @param array $rows
	 * @return int
	 */
	public function addRows($rows): int
	{
		$total = 0;
		foreach($rows as &$row) {
			$success = $this->addRow($this->filename, $row);
			$total += $success ? 1 : 0;
		}
		$this->totalRows += $total;
		return $total;
	}

	/**
	 * This method adds a single row to a generated report and returns a boolean
	 * value for success/failure.
	 * @param array $row
	 * @return bool
	 */
	public function addRow($row): bool
	{
		$success = $this->writeRow($this->filename, $row);
		$this->totalRows += $success ? 1 : 0;
		return $success;
	}

	/**
	 * This method returns the full system path to the report file whether or not
	 * the file exists. If the filename has not been set, it returns null.
	 * @return string
	 */
	public function filePath(): string
	{
		return $this->filename ? $this->plugin->reports->storagePath().DIRECTORY_SEPARATOR.$this->filename : null;
	}

	/**
	 * This method checks whether or not the report file exists. If no filename
	 * has been set, it automatically returns false.
	 * @return bool
	 */
	public function fileExists(): bool
	{
		$filePath = $this->filePath();
		return $filePath ? file_exists( $filePath ) : false;
	}

	/**
	 * This method sets the related _user property.
	 * @param User $user
	 * @return $this
	 */
	public function setUser($user)
	{
		$this->_user = $user;
		return $this;
	}

	/**
	 * This method returns the User element associated with this record.
	 * @return User
	 */
	public function getUser()
	{
		$user = null;
		if ( $this->_user !== null ) {
			$user = $this->_user;
		} elseif ( $this->userId ) {
			$user = Craft::$app->users->getUserById($this->userId);
		}
		return $user;
	}

	/**
	 * This method sets the related _reportConfigured property.
	 * @param ReportConfigured $rc
	 * @return $this
	 */
	public function setReportConfigured(ReportConfigured $rc)
	{
		if ( ! $rc instanceof ReportConfigured ) {
			throw new InvalidReportConfiguredException("Report::reportConfigured must be an instance of ReportConfigured.");
		}
		$this->_reportConfigured = $rc; // private
		$this->reportConfiguredId = $rc->id; // public
		// A change to the ReportConfigured results in a change to the filename.
		$this->filename = $this->generateFilename();
		return $this;
	}

	/**
	 * This method returns the ReportConfigured element associated with this record.
	 * @return ReportConfigured|null
	 */
	public function getReportConfigured()
	{
		$rc = null;
		if ( $this->_reportConfigured !== null ) {
			$rc = $this->_reportConfigured;
		} elseif ( $this->_reportConfigured ) {
			$rc = ReportConfigured::find()->id($this->reportConfiguredId)->one();
		}
		return $rc;
	}

	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 * @param string|null $source
	 * @return array
	 */
	public static function defineTableAttributes($source = null): array
	{
		return [
			'id' => Craft::t('labreports', 'ID'),
			'filename' => Craft::t('labreports', 'Filename'),
			'reportConfigured' => Craft::t('labreports', 'Configured Report'),
			'totalRows' => Craft::t('labreports', 'Generated Reports')
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return ['id', 'filename', 'reportConfigured', 'totalRows'];
	}

	/**
 	* @inheritdoc
 	*/
	protected static function defineSortOptions(): array
	{
		return [
			'dateGenerated' => Craft::t('app', 'Date Generated'),
			'filename' => Craft::t('labreports', 'Filename'),
		];
	}

	/**
	 * @inheritdoc
	 */
	protected function tableAttributeHtml(string $attribute): string
	{
		$displayValue = '';
		switch ($attribute) {
			case 'id':
				$displayValue = $this->$attribute;
				break;
			case 'reportConfigured':
				$rc = $this->getReportConfigured();
				if ( $rc ) {
					$displayValue = "<a href='".$rc->getCpEditUrl()."' >{$rc->reportTitle}</a>";
				} else {
					$displayValue = 'Unknown';
				}
				break;
			default:
				$displayValue = parent::tableAttributeHtml($attribute);
				break;
		}
		return (string) $displayValue;
	}

	/**
	 * @inheritdoc
	 */
	public static function eagerLoadingMap(array $sourceElements, string $handle)
	{
		$sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');
		if ($handle === 'user') {
			$map = (new Query())
				->select(['id as source', 'userId as target'])
				->from(['{{%labreports_reports}}'])
				->where(['and', ['id' => $sourceElementIds], ['not', ['userId' => null]]])
				->all();
			return [
				'elementType' => User::class,
				'map' => $map
			];
		} elseif ($handle === 'configuredReport') {
			$map = (new Query())
				->select(['id as source', 'reportConfiguredId as target'])
				->from(['{{%labreports_reports}}'])
				->where(['and', ['id' => $sourceElementIds], ['not', ['reportConfiguredId' => null]]])
				->all();
			return [
				'elementType' => ReportConfigured::class,
				'map' => $map
			];
		}
		return parent::eagerLoadingMap($sourceElements, $handle);
	}

	/**
	 * @inheritdoc
	 */
	public function setEagerLoadedElements(string $handle, array $elements)
	{
		if ($handle === 'user') {
			$user = $elements[0] ?? null;
			$this->setUser($user);
		} elseif ($handle === 'configuredReport') {
			$rc = $elements[0] ?? null;
			$this->setReportConfigured($rc);
		} else {
			parent::setEagerLoadedElements($handle, $elements);
		}
	}

	/**
	 * This method assigns a GenerateReport queue job to this report instance.
	 * @param GenerateReport $job
	 * @return self
	 */
	public function setQueueJob(GenerateReport $job)
	{
		$this->_queueJob = $job;
		return $this;
	}

	/**
	 * This method updates the status of a report.
	 * @param string $status
	 */
	public function updateStatus($status): bool
	{
		$this->reportStatus = $status;
		return Craft::$app->getElements()->saveElement($this);
	}

}

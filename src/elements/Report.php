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
use craft\helpers\UrlHelper;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\actions\DeleteReport;
use Masuga\LabReports\elements\ConfiguredReport;
use Masuga\LabReports\elements\db\ReportQuery;
use Masuga\LabReports\exceptions\InvalidConfiguredReportException;
use Masuga\LabReports\queue\jobs\GenerateReport;
use Masuga\LabReports\records\ReportRecord;

class Report extends Element
{

	public $configuredReportId = null;
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
	 * A place to store the related ConfiguredReport element.
	 * @var ConfiguredReport
	 */
	private $_configuredReport = null;

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

	public function init()
	{
		parent::init();
		$this->plugin = LabReports::getInstance();
	}

	/**
	 * Returns the element type name.
	 * @return string
	 */
	public static function displayName(): string
	{
		return Craft::t('labreports', 'Generated Report');
	}

	/**
	 * Returns whether this element type has content.
	 * @return bool
	 */
	public static function hasContent(): bool
	{
		return false;
	}

	/**
	 * Returns whether this element type has titles. Though reports have "titles",
	 * we are not using Craft's `content` table with these elements thus we are
	 * using our own `reportTitle` column.
	 * @return bool
	 */
	public static function hasTitles(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public static function isLocalized(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 * @param string|null $source
	 * @return array|null
	 */
	protected static function defineActions(string $source = null): array
	{
		return [
			DeleteReport::class
		];
	}

	/**
	 * Returns this element type's sources.
	 * @param string|null $context
	 * @return array|false
	 */
	protected static function defineSources(string $context = null): array
	{
		$sources = [
			[
				'key' => 'allReports',
				'label' => Craft::t('labreports', 'All Reports'),
				'defaultSort' => ['labreports_reports.dateGenerated', 'desc']
			]
		];
		// Fetch all ConfiguredReport elements and add a source for each one.
		$rcs = ConfiguredReport::find()->orderBy('reportTitle')->all();
		foreach($rcs as &$rc) {
			$sources[] = [
				'key' => str_replace(' ', '', $rc->reportTitle),
				'label' => $rc->reportTitle,
				'criteria' => ['configuredReportId' => $rc->id],
				'defaultSort' => ['labreports_reports.dateGenerated', 'desc']
			];
		}
		//exit("<pre>".print_r($sources,true)."</pre>");
		return $sources;
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
			'configuredReport' => Craft::t('labreports', 'Configured Report'),
			'reportStatus' => Craft::t('labreports', 'Status'),
			'dateGenerated' => Craft::t('labreports', 'Date Generated'),
			'totalRows' => Craft::t('labreports', 'Total Rows')
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return ['id', 'filename', 'configuredReport', 'reportStatus', 'dateGenerated', 'totalRows'];
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
				$displayValue = (string) $this->id;
				break;
			case 'configuredReport':
				$rc = $this->getConfiguredReport();
				if ( $rc ) {
					$displayValue = "<a href='".$rc->getCpEditUrl()."' >{$rc->reportTitle}</a>";
				} else {
					$displayValue = (string) 'Unknown';
				}
				break;
			case 'dateGenerated':
				$timezone = new DateTimeZone(Craft::$app->getTimeZone());
				$date = $this->dateGenerated ? new DateTime($this->dateGenerated, new DateTimeZone('UTC')) : null;
				$displayValue = $date ? $date->setTimezone($timezone)->format('F j, Y g:i a') : '--';
				break;
			case 'totalRows':
				$displayValue = (string) number_format($this->totalRows, 0);
				break;
			case 'filename':
				$url = $this->getDownloadUrl();
				$displayValue = "<a href='{$url}' ><span data-icon='download' aria-hidden='true'></span> {$this->filename}</a>";
				break;
			default:
				$displayValue = parent::tableAttributeHtml($attribute);
				break;
		}
		return (string) $displayValue;
	}

	/**
 	* @inheritdoc
 	* @throws Exception if existing record is not found.
 	*/
	public function afterSave(bool $isNew)
	{
		if ( $isNew ) {
			$record = new ReportRecord;
			$record->id = $this->id;
		} else {
			$record = ReportRecord::findOne($this->id);
			if (!$record) {
				throw new Exception('Invalid generated report ID: '.$this->id);
			}
		}
		$record->configuredReportId = $this->configuredReportId;
		$record->reportStatus = $this->reportStatus;
		$record->dateGenerated = $this->dateGenerated;
		$record->filename = $this->filename;
		$record->totalRows = $this->totalRows;
		$record->userId = $this->userId;
		$status = $record->save();
		parent::afterSave($isNew);
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
				->select(['id as source', 'configuredReportId as target'])
				->from(['{{%labreports_reports}}'])
				->where(['and', ['id' => $sourceElementIds], ['not', ['configuredReportId' => null]]])
				->all();
			return [
				'elementType' => ConfiguredReport::class,
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
			$this->setConfiguredReport($rc);
		} else {
			parent::setEagerLoadedElements($handle, $elements);
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
	 * This method generates a report filename based on the related ConfiguredReport
	 * and current LOCAL date.
	 * @param string $ext
	 * @return string
	 */
	private function generateFilename($ext='csv'): string
	{
		$localDate = $this->currentLocalDate()->format('YmdHis');
		$configuredReport = $this->getConfiguredReport();
		$title = $configuredReport ? StringHelper::slugify($configuredReport->reportTitle) : '';
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
	 * This method is a public-facing method for generating the report file
	 * whether it is a Basic report or an Advanced report.
	 * @param mixed $param1
	 * @param mixed $param2
	 */
	public function build($param1, $param2=null): int
	{
		$rowsWritten = 0;
		$rc = $this->getConfiguredReport();
		if ( ! $rc ) {
			// @TODO : Throw an exeception because the report is not configured correctly. Log it!
		}
		if ( $rc->reportType == 'advanced' ) {
			$rowsWritten = $this->buildAdvancedReport($param1, $param2);
		} else {
			$rowsWritten = $this->buildBasicReport($param1);
		}
		return $rowsWritten;
	}

	/**
	 * This methods builds the report file based on an array of report rows, including
	 * the column headers.
	 * @param array $headers
	 * @param ElementQuery $query
	 * @return int
	 */
	private function buildBasicReport(array $rows): int
	{
		$this->dateGenerated = DateTimeHelper::currentUTCDateTime()->format(DATE_ATOM);
		$rc = $this->getConfiguredReport();
		$rowsWritten = 0;
		$currentTotalRows = $offset = 0;
		$grandTotalRows = count($rows);
		if ( $this->plugin->getConfigItem('debug')) {
			$this->plugin->reports->log("Grand Total Rows : {$grandTotalRows}");
		}
		foreach($rows as &$row) {
			$written = $this->addRow($row);
			// The count of successfully written rows.
			$rowsWritten += $written ? 1 : 0;
			// The count of every row from every loop regardless of success.
			$currentTotalRows ++;
			if ( $this->_queueJob ) {
				$this->_queueJob->updateProgress(($currentTotalRows / $grandTotalRows), "{$currentTotalRows} of {$grandTotalRows} rows");
			}
		}
		if ( $this->plugin->getConfigItem('debug') ) {
			$this->plugin->reports->log("Total Rows : {$rowsWritten} written of {$currentTotalRows} total rows.");
		}
		return $rowsWritten;
	}

	/**
	 * This methods builds the report file based on an array of column headers
	 * and an element query.
	 * @param array $headers
	 * @param ElementQuery $query
	 * @return int
	 */
	private function buildAdvancedReport(array $headers, ElementQuery $query): int
	{
		$this->dateGenerated = DateTimeHelper::currentUTCDateTime()->format(DATE_ATOM);
		$rc = $this->getConfiguredReport();
		$rowsWritten = 0;
		$this->addRow($headers);
		$currentTotalRows = $offset = 0;
		$grandTotalRows = $query->count();
		if ( $this->plugin->getConfigItem('debug')) {
			$this->plugin->reports->log("Grand Total Rows : {$grandTotalRows}");
		}
		$formatFunction = $this->plugin->reports->formatFunction($rc->formatFunction);
		if ( ! $formatFunction ) {
			$this->plugin->reports->log("Advanced Report `{$rc->reportTitle}` has Formatting Function Name `{$rc->formatFunction}`.");
			throw new InvalidConfiguredReportException("Invalid Formatting Function Name `{$rc->formatFunction}`.");
		}
		do {
			if ( $this->_queueJob ) {
				$this->_queueJob->updateProgress(($currentTotalRows / $grandTotalRows), "{$currentTotalRows} of {$grandTotalRows} rows");
			}
			$elements = $query->limit(self::BATCH_LIMIT)->offset($offset)->all();
			$batchCount = count($elements);
			if ( $this->plugin->getConfigItem('debug')) {
				$this->plugin->reports->log("Current batch count : {$batchCount}");
			}
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
		if ( $this->plugin->getConfigItem('debug') ) {
			$this->plugin->reports->log("Total Rows : {$rowsWritten} written of {$currentTotalRows} total rows.");
		}
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
	 * This method sets the related _configuredReport property.
	 * @param ConfiguredReport $rc
	 * @return $this
	 */
	public function setConfiguredReport(ConfiguredReport $rc)
	{
		if ( $rc !== null && ! $rc instanceof ConfiguredReport ) {
			$varType = gettype($rc);
			throw new InvalidConfiguredReportException("Report::configuredReport must be an instance of ConfiguredReport. `{$varType}` given.");
		}
		$this->_configuredReport = $rc; // private
		$this->configuredReportId = $rc->id; // public
		// A change to the ConfiguredReport results in a change to the filename.
		$this->filename = $this->generateFilename();
		return $this;
	}

	/**
	 * This method returns the ConfiguredReport element associated with this record.
	 * @return ConfiguredReport|null
	 */
	public function getConfiguredReport()
	{
		$rc = null;
		if ( $this->_configuredReport !== null ) {
			$rc = $this->_configuredReport;
		} elseif ( $this->configuredReportId ) {
			$rc = ConfiguredReport::find()->id($this->configuredReportId)->one();
			$this->_configuredReport = $rc;
		}
		return $rc;
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

	/**
	 * This method returns the download URL
	 * @return string
	 */
	public function getDownloadUrl(): string
	{
		return UrlHelper::cpUrl('labreports/download', ['id' => $this->id]);
	}

}

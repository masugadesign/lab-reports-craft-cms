<?php

namespace Masuga\LabReports\elements;

use Craft;
use Exception;
use craft\base\Element;
use craft\elements\User;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\StringHelper;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\ReportConfigured;

class Report extends Element
{

	public $reportConfiguredId = null;
	public $filename = null;
	public $totalRows = 0;
	public $dateGenerated = null;
	public $userId = null;

	private $_user = null;

	public function __construct($reportConfiguredId=null)
	{
		$this->reportConfiguredId = $reportConfiguredId;
	}

	/**
	 * The instance of the Lab Reports plugin.
	 * @var LabReports
	 */
	private $plugin = null;

	public function init()
	{
		parent::init();
		$this->dateGenerated = DateTimeHelper::currentUTCDateTime()->format(DATE_ATOM);
		$this->filename = $this->generateFilename();
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
		$title = StringHelper::slugify($reportConfigured->title);
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
		$date->setTimeZone( Craft::$app->getTimeZone() );
		return $date;
	}

	public function columns($columnNames): bool
	{
		return $this->plugin->reports->writeRow($columnNames)
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
			$success = $this->plugin->reports->writeRow($this->filename, $row);
			$total += $success ? 1 : 0;
		}
		return $total;
	}

	/**
	 * This method
	 *
	 */
	public function addRow($row): bool
	{
		return $this->plugin->reports->writeRow($this->filename, $row);
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
	 * @param ReportConfigured $asset
	 * @return $this
	 */
	public function setReportConfigured($cr)
	{
		$this->_reportConfigured = $cr;
		return $this;
	}

	/**
	 * This method returns the ReportConfigured element associated with this record.
	 * @return ReportConfigured|null
	 */
	public function getReportConfigured()
	{
		$cr = null;
		if ( $this->_reportConfigured !== null ) {
			$cr = $this->_reportConfigured;
		} elseif ( $this->_reportConfigured ) {
			$cr = ReportConfigured::find()->id($this->assetId)->one();
		}
		return $cr;
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
				->where(['and', ['id' => $sourceElementIds], ['not', ['assetId' => null]]])
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
			$asset = $elements[0] ?? null;
			$this->setConfiguredReport($asset);
		} else {
			parent::setEagerLoadedElements($handle, $elements);
		}
	}

}

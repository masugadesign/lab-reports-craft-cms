<?php

namespace Masuga\LabReports\elements;

use Craft;
use Exception;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\elements\db\ConfiguredReportQuery;
use Masuga\LabReports\elements\actions\ConfiguredReportDelete;
use Masuga\LabReports\records\ConfiguredReportRecord;

class ConfiguredReport extends Element
{
	public $reportType = null;
	public $reportTitle = null;
	public $reportDescription = null;
	public $template = null;
	public $formatFunction = null;

	private $_totalRan = null;

	/**
	 * Instance of the Lab Reports plugin.
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
		return Craft::t('labreports', 'Configured Report');
	}

	/**
	 * @inheritdoc
	 */
	public static function find(): ElementQueryInterface
	{
		return new ConfiguredReportQuery(static::class);
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
	 * Returns this element type's sources.
	 * @param string|null $context
	 * @return array|false
	 */
	protected static function defineSources(string $context = null): array
	{
		$sources = [
			[
				'key'      => 'allReports',
				'label'    => Craft::t('labreports', 'All Reports'),
				'defaultSort' => ['labreports_configured_reports.reportTitle', 'asc']
			],
			[
				'key'      => 'basicReports',
				'label'    => Craft::t('labreports', 'Basic Reports'),
				'criteria' => ['reportType' => 'basic'],
				'defaultSort' => ['labreports_configured_reports.reportTitle', 'asc']
			],
			[
				'key'      => 'advancedReports',
				'label'    => Craft::t('labreports', 'Advanced Reports'),
				'criteria' => ['reportType' => 'advanced'],
				'defaultSort' => ['labreports_configured_reports.reportTitle', 'asc']
			]
		];
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
			'reportTitle' => Craft::t('labreports', 'Title'),
			'reportType' => Craft::t('labreports', 'Type'),
			'reportDescription' => Craft::t('labreports', 'Description'),
			'totalRan' => Craft::t('labreports', 'Generated Reports'),
			'runUrl' => Craft::t('labreports', 'Run Report')
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return ['id', 'reportTitle', 'reportType', 'reportDescription', 'totalRan', 'runUrl'];
	}

	/**
 	* @inheritdoc
 	*/
	protected static function defineSortOptions(): array
	{
		return [
			'reportTitle' => Craft::t('labreports', 'File'),
			'elements.dateCreated' => Craft::t('app', 'Date Created'),
		];
	}

	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 * @return array
	 */
	protected static function defineSearchableAttributes(): array
	{
		// Let's not put these in the search index.
		return [];
	}

	/**
	 * This method returns the full CP URL that generates an instance of this
	 * ConfiguredReport.
	 * @return string
	 */
	public function getRunUrl()
	{
		return UrlHelper::cpUrl('labreports/run', ['id' => $this->id]);
	}

	/**
	 * This method returns the single report CP edit form URL.
	 * @return string
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::cpUrl('labreports/configure', ['id' => $this->id]);
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
			case 'reportTitle':
				$cpEditUrl = $this->getCpEditUrl();
				$displayValue = "<a href='{$cpEditUrl}' >{$this->reportTitle}</a>";
				break;
			case 'reportType':
				$displayValue = ucwords($this->$attribute);
				break;
			case 'totalRan':
				$displayValue = (string) $this->getTotalRan();
				break;
			case 'runUrl':
				$url = $this->getRunUrl();
				$displayValue = "<a href='{$url}' >Run</a>";
				break;
			default:
				$displayValue = parent::tableAttributeHtml($attribute);
				break;
		}
		return (string) $displayValue;
	}

	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 * @param string|null $source
	 * @return array|null
	 */
	protected static function defineActions(string $source = null): array
	{
		return [
			Delete::class
		];
	}

	/**
	 * @inheritdoc
	 */
	protected function defineRules(): array
	{
		$rules = parent::defineRules();
		$requiredFields = ['reportType', 'reportTitle', 'template'];
		if ( $this->reportType == 'advanced' ) {
			$requiredFields[] = 'formatFunction';
		}
		$rules[] = [$requiredFields, 'required'];
		return $rules;
	}

	/**
 	* @inheritdoc
 	* @throws Exception if existing record is not found.
 	*/
	public function afterSave(bool $isNew)
	{
		if ( $isNew ) {
			$record = new ConfiguredReportRecord;
			$record->id = $this->id;
		} else {
			$record = ConfiguredReportRecord::findOne($this->id);
			if (!$record) {
				throw new Exception('Invalid configured report ID: '.$this->id);
			}
		}
		$record->reportType = $this->reportType;
		$record->reportTitle = $this->reportTitle;
		$record->reportDescription = $this->reportDescription;
		$record->template = $this->template;
		$record->formatFunction = $this->formatFunction;
		$status = $record->save();
		parent::afterSave($isNew);
	}

	/**
	 * This method returns the total number of times this report has been run.
	 * @return int
	 */
	public function getTotalRan(): int
	{
		$total = 0;
		if ( is_numeric($this->_totalRan) ) {
			$total = $this->_totalRan;
		} else {
			if ( $this->id ) {
				$total = Report::find()->configuredReportId($this->id)->count();
				$this->_totalRan = $total;
			}
		}
		return $total;
	}

}

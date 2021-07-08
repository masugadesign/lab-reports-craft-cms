<?php

namespace Masuga\LabReports\elements;

use Craft;
use Exception;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\elements\db\ReportConfiguredQuery;
use Masuga\LabReports\elements\actions\ReportConfiguredDelete;
use Masuga\LabReports\records\ReportConfiguredRecord;

class ReportConfigured extends Element
{
	public $title = null;
	public $reportDescription = null;
	public $template = null;
	public $formatFunction = null;
	public $type = null;

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
		return new ReportConfiguredQuery(static::class);
	}

	/**
	 * This method generates a Report element from this ReportConfigured element.
	 * @return Report
	 */
	public function generate(): Report
	{
		$report = new Report($this->id);
		$renderedTemplate = $this->renderTemplate($this->template);
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
	 * Returns whether this element type has titles.
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
				'key'      => 'basicReports',
				'label'    => Craft::t('labreports', 'Basic Reports'),
				'criteria' => ['type' => 'basic'],
				'defaultSort' => ['labreports_configured_reports.title', 'asc']
			],
			[
				'key'      => 'advancedReports',
				'label'    => Craft::t('labreports', 'Advanced Reports'),
				'criteria' => ['type' => 'advanced'],
				'defaultSort' => ['labreports_configured_reports.dateCreated', 'desc']
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
			'reportDescription' => Craft::t('labreports', 'Description'),
			'totalRan' => Craft::t('labreports', 'Generated Reports')
		];
	}

	/**
	 * @inheritDoc
	 */
	protected static function defineDefaultTableAttributes(string $source): array
	{
		return ['id', 'reportTitle', 'reportDescription', 'totalRan'];
	}

	/**
 	* @inheritdoc
 	*/
	protected static function defineSortOptions(): array
	{
		return [
			'title' => Craft::t('labreports', 'File'),
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
	 * @inheritdoc
	 */
	protected function tableAttributeHtml(string $attribute): string
	{
		$displayValue = '';
		switch ($attribute) {
			case 'id':
				$displayValue = $this->$attribute;
			case 'totalRan':
				$displayValue = Report::find()->configuredReportId($this->id)->count();
			default:
				$displayValue = parent::tableAttributeHtml($attribute);
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
		$rules[] = [['reportTitle', 'template'], 'required'];
		return $rules;
	}

	/**
 	* @inheritdoc
 	* @throws Exception if existing record is not found.
 	*/
	public function afterSave(bool $isNew)
	{
		if ( $isNew ) {
			$record = new ReportConfiguredRecord;
			$record->id = $this->id;
		} else {
			$record = ReportConfiguredRecord::findOne($this->id);
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
	 * This method executes the configured report and returns the generated report
	 * if successful.
	 * @return Report|null
	 */
	public function run()
	{
		$report = new Report($this->id);
		$parsedReportTemplate = Craft::$app->getView()->renderTemplate($this->template, [
			'report' => $report
		]);
		return $report->fileExists() ? Report : null;
	}

}

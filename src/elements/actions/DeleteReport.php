<?php

namespace Masuga\LabReports\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\base\ElementActionInterface;
use craft\elements\db\ElementQueryInterface;
use Masuga\LabReports\LabReports;

class DeleteReport extends ElementAction
{

	/**
	 * @var string|null The confirmation message that should be shown before the elements get deleted
	 */
	public $confirmationMessage;

	/**
	 * @var string|null The message that should be shown after the elements get deleted
	 */
	public $successMessage;

	public function init()
	{
		$this->confirmationMessage = Craft::t('labreports', 'Are you sure you want to delete the selected Lab Reports elements and files?');
		$this->successMessage = Craft::t('labreports', 'Reports deleted.');
	}

	/**
	 * @inheritDoc IComponentType::getName()
	 * @return string
	 */
	public function getTriggerLabel(): string
	{
		return Craft::t('labreports', 'Delete Report(s)');
	}

	/**
	 * @inheritDoc IElementAction::isDestructive()
	 * @return bool
	 */
	public static function isDestructive(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc IElementAction::getConfirmationMessage()
	 * @return string|null
	 */
	public function getConfirmationMessage()
	{
		return $this->confirmationMessage;
	}

	/**
	 * @inheritDoc
	 */
	public function performAction(ElementQueryInterface $query): bool
	{
		$total = 0;
		foreach($query->all() as $report) {
			$deleted = LabReports::getInstance()->reports->deleteReport($report);
			$total += $deleted ? 1 : 0;
		}
		$this->setMessage("{$total} Lab Report elements/files deleted.");
		return true;
	}

}

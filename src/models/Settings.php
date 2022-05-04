<?php

namespace Masuga\LabReports\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{
	public $fileStorageFolder = null;

	public $functions = [];

	public function init(): void
	{
		parent::init();
		$this->fileStorageFolder = Craft::$app->getPath()->getStoragePath().DIRECTORY_SEPARATOR.'labreports';
	}

}

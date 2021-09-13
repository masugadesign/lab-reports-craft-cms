<?php
namespace Masuga\LabReports\resources;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class LabReportsAsset extends AssetBundle
{
	public function init()
	{
		$this->sourcePath = '@Masuga/LabReports/resources';

		$this->depends = [
			CpAsset::class,
		];

		$this->js = [
			'script.js',
		];

		parent::init();
	}
}

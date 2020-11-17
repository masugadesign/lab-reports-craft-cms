<?php

namespace Masuga\LabReports\variables;

use Craft;
use Masuga\LabReports\LabReports;

class LabReportsVariable
{

	/**
	 * The instance of the LabReports plugin class.
	 * @var LabReports
	 */
	private $plugin = null;

	public function __construct()
	{
		$this->plugin = LabReports::getInstance();
	}

}

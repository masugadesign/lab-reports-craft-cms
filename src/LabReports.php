<?php

namespace Masuga\LabReports;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Dashboard;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use Masuga\LabReports\elements\ConfiguredReport;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\models\Settings;
use Masuga\LabReports\services\Reports;
use Masuga\LabReports\variables\LabReportsVariable;
use yii\base\Event;

class LabReports extends Plugin
{

	/**
	 * Enables the CP sidebar nav link for this plugin. Craft loads the plugin's
	 * index template by default.
	 * @var boolean
	 */
	public bool $hasCpSection = true;

	/**
	 * Enables the plugin settings form.
	 * @var boolean
	 */
	public bool $hasCpSettings = true;

	/**
	 * The default config file array.
	 * @var array
	 */
	public $defaultConfig = null;

	/**
	 * The name of the plugin as it appears in the Craft control panel and
	 * plugin store.
	 * @return string
	 */
	public function getName()
	{
		 return Craft::t('labreports', 'Lab Reports');
	}

	/**
	 * The brief description of the plugin that appears in the control panel
	 * on the plugin settings page.
	 * @return string
	 */
	public function getDescription(): string
	{
		return Craft::t('labreports', 'Custom content/data reporting for Craft CMS.');
	}

	/**
	 * This method returns the settings form HTML content.
	 * @return string
	 */
	protected function settingsHtml(): ?string
	{
		return Craft::$app->getView()->renderTemplate('labreports/_settings', []);
	}

	/**
	 * This method returns the plugin's Settings model instance.
	 * @return Settings
	 */
	protected function createSettingsModel(): ?\craft\base\Model
	{
		return new Settings();
	}

	/**
	 * The plugin's initialization function is responsible for registering event
	 * handlers, routes and other plugin components.
	 */
	public function init()
	{
		parent::init();
		// Load the default config.
		$this->defaultConfig = require $this->getBasePath().DIRECTORY_SEPARATOR.'config.php';
		// Initialize each of the services used by this plugin.
		$this->setComponents([
			'reports' => Reports::class
		]);
		// Load the template variables class.
		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
			$variable = $event->sender;
			$variable->set('labreports', LabReportsVariable::class);
		});
		// Register CP routes.
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
			$event->rules['labreports'] = 'labreports/cp/index';
			$event->rules['labreports/configure'] = 'labreports/cp/configure';
			$event->rules['labreports/run'] = 'labreports/cp/run';
			$event->rules['labreports/generated-reports'] = 'labreports/cp/generated-reports';
			$event->rules['labreports/download'] = 'labreports/cp/download';
			$event->rules['labreports/detail'] = 'labreports/cp/detail';
		});
		Event::on(ConfiguredReport::class, ConfiguredReport::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
			$source = $event->source;
			$actions = $event->actions;
			// Remove the default `Edit` action. Totally uncalled for. Craft 4 adds it. Why?
			unset($event->actions[0]);
		});
		Event::on(Report::class, Report::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
			$source = $event->source;
			$actions = $event->actions;
			// Remove the default `Edit` and `Duplicate` actions. Totally uncalled for. Craft 4 adds them. Why?
			unset($event->actions[0], $event->actions[1]);
		});
	}

	/**
	 * This returns the entire plugin config array with any defined overrides.
	 * @return array
	 */
	public function getConfig(): array
	{
		return array_merge($this->defaultConfig, Craft::$app->getConfig()->getConfigFromFile('labreports'));
	}

	/**
	 * This method returns a plugin config value by key. It returns `null` if the
	 * item is not defined.
	 * @param string $key
	 * @return mixed
	 */
	public function getConfigItem($key)
	{
		$config = $this->getConfig();
		return $config[$key] ?? null;
	}

}

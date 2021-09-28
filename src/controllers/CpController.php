<?php

namespace Masuga\LabReports\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\elements\ConfiguredReport;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\queue\jobs\GenerateReport;
use Masuga\LabReports\resources\LabReportsAsset;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class CpController extends Controller
{

	/**
	 * The instance of the LabReports plugin.
	 * @var LabReports
	 */
	private $plugin = null;

	public function init()
	{
		parent::init();
		$this->plugin = LabReports::getInstance();
	}

	/**
	 * The "ConfiguredReport" element index.
	 * @return Response
	 */
	public function actionIndex(): Response
	{
		return $this->renderTemplate('labreports/reports-configured/index');
	}

	/**
	 * The "Report" element index.
	 * @return Response
	 */
	public function actionGeneratedReports(): Response
	{
		return $this->renderTemplate('labreports/reports-generated/index');
	}

	/**
	 * This method presents the user with a form to create/update a ConfiguredReport
	 * element.
	 * @return Response
	 */
	public function actionConfigure(ConfiguredReport $report=null): Response
	{
		$id = Craft::$app->getRequest()->getParam('id');
		if ( ! $report ) {
			$report = $id ? ConfiguredReport::find()->id($id)->one() : new ConfiguredReport;
		}
		$this->view->registerAssetBundle(LabReportsAsset::class);
		return $this->renderTemplate('labreports/reports-configured/configure', [
			'report' => $report
		]);
	}

	/**
	 * This method handles configuring a new or existing
	 *
	 */
	public function actionConfigureSubmit()
	{
		$this->requirePostRequest();
		$request = Craft::$app->getRequest();
		$data = [
			'reportType' => $request->getParam('reportType'),
			'reportTitle' => $request->getParam('reportTitle'),
			'reportDescription' => $request->getParam('reportDescription'),
			'template' => $request->getParam('template'),
			'formatFunction' => $request->getParam('formatFunction'),
		];
		$crId = $request->getParam('configuredReportId');
		$cr = $this->plugin->reports->saveConfiguredReport($data, $crId);
		if ( ! $cr->getErrors() ) {
			$this->setSuccessFlash(Craft::t('labreports', 'Report configured successfully.'));
			$response = Craft::$app->getResponse()->redirect($cr->getCpEditUrl());
		} else {
			$this->setFailFlash(Craft::t('labreports', 'Error configuring report.'));
			Craft::$app->getUrlManager()->setRouteParams([
				'report' => $cr
			]);
			$response = null;
		}
		return $response;
	}

	/**
	 * This controller action executes a ConfiguredReport as a queue job.
	 * @return Response
	 */
	public function actionRun(): Response
	{
		$queue = Craft::$app->getQueue();
		$request = Craft::$app->getRequest();
		$crId = $request->getParam('id');
		$cr = $this->plugin->reports->getConfiguredReportById($crId);
		if ( ! $cr ) {
			$this->plugin->reports->log("Invalid ConfiguredReport ID `{$crId}`.");
			throw new NotFoundHttpException("Invalid ConfiguredReport ID `{$crId}`.");
		}
		$job = new GenerateReport(['configuredReportId' => $cr->id]);
		$queue->delay(0)->push($job);
		return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('labreports'));
	}

	/**
	 * View a single generated report page with some basic info and a download
	 * link.
	 * @return Response
	 */
	public function actionView(): Response
	{
		$id = Craft::$app->getRequest()->getParam('id');
		$report = Report::find()->id($id)->one();
		if ( ! $report ) {
			$this->plugin->reports->log("Invalid Report ID: `{$id}`");
			throw new NotFoundHttpException("Invalid Report ID: `{$id}`");
		}
		return $this->renderTemplate('labreports/reports-configured/view', ['report' => $report]);
	}

	/**
	 * Download a report file based on a supplied report ID.
	 * @return Response
	 */
	public function actionDownload(): Response
	{
		$id = Craft::$app->getRequest()->getParam('id');
		$report = Report::find()->id($id)->one();
		// Check that the Report element actually exists.
		if ( ! $report ) {
			$this->plugin->reports->log("Invalid Generated Report ID: `{$id}`");
			throw new NotFoundHttpException("Invalid Generated Report ID: `{$id}`");
		}
		// Make sure the file exists.
		if ( ! $report->fileExists() ) {
			$this->plugin->reports->log("Report file `{$report->filePath()}` not found.");
			throw new NotFoundHttpException("Report file `{$report->filePath()}` not found.");
		}
		return Craft::$app->getResponse()->sendFile($report->filePath());
	}

}

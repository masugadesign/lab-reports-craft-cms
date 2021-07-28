<?php

namespace Masuga\LabReports\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use Masuga\LabReports\elements\Report;
use Masuga\LabReports\elements\ReportConfigured;
use Masuga\LabReports\LabReports;
use Masuga\LabReports\queue\jobs\GenerateReport;
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
	 * The "ReportConfigured" element index.
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
	 * This method presents the user with a form to create/update a ReportConfigured
	 * element.
	 * @param ReportConfigured $report
	 * @return Response
	 */
	public function actionConfigure(ReportConfigured $report=null): Response
	{
		$id = Craft::$app->getRequest()->getParam('id');
		if ( ! $report ) {
			$report = $id ? ReportConfigured::find()->id($id)->one() : new ReportConfigured;
		}
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
		$rcId = $request->getParam('reportConfiguredId');
		$rc = $this->plugin->reports->saveReportConfigured($data, $rcId);
		if ( ! $rc->getErrors() ) {
			$this->setSuccessFlash(Craft::t('labreports', 'Report configured successfully.'));
			$response = Craft::$app->getResponse()->redirect($rc->getCpEditUrl());
		} else {
			$this->setFailFlash(Craft::t('labreports', 'Error configuring report.'));
			Craft::$app->getUrlManager()->setRouteParams([
				'report' => $rc
			]);
			$response = null;
		}
		return $response;
	}

	/**
	 * This controller action executes a ReportConfigured as a queue job.
	 * @return Response
	 */
	public function actionRun(): Response
	{
		$queue = Craft::$app->getQueue();
		$request = Craft::$app->getRequest();
		$rcId = $request->getParam('reportConfiguredId');
		$rc = $this->plugin->reports->getReportConfiguredById($rcId);
		if ( ! $rc ) {
			throw new NotFoundHttpException("Invalid ReportConfigured ID `{$rcId}`.");
		}
		$job = new GenerateReport(['reportConfiguredId' => $rc->id]);
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
			throw new NotFoundHttpException("Invalid Generated Report ID: `{$id}`");
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
		if ( ! $report ) {
			throw new NotFoundHttpException("Invalid Generated Report ID: `{$id}`");
		}
	}

}

<?php

namespace Masuga\LabReports\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use Masuga\LabReports\Report;
use Masuga\LabReports\ReportConfigured;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class CpController extends Controller
{
	public function actionIndex(): Response
	{
		return $this->renderTemplate('labreports/index');
	}

	/**
	 * This method presents the user with a form to create/update a ReportConfigured
	 * element.
	 * @return Response
	 */
	public function actionConfigure(): Response
	{
		$id = Craft::$app->getRequest()->getParam('id');
		$report = $id ? Report::find()->id($id)->one() : null;
		return $this->renderTemplate('labreports/reports/configure', [
			'report' => $report
		]);
	}

	public function actionConfigureSubmit(): Response
	{

	}

	public function actionRun(): Response
	{

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
		return $this->renderTemplate('labreports/reports/view', ['report' => $report]);
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

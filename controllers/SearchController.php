<?php


namespace app\controllers;


use app\models\utils\GlobalPatientSearch;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SearchController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('/error', 404);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'global-search'
                        ],
                        'roles' => ['manager'],
                    ],
                ],
            ],
        ];
    }

    public function actionGlobalSearch(?string $page = null)
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $model = new GlobalPatientSearch();
            if (Yii::$app->request->isPost) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                $model->load(Yii::$app->request->post());
                if ($page !== null) {
                    $model->page = $page;
                }
                if ($model->validate()) {
                    $results = $model->makeSearch();
                    return ['status' => true, 'results' => $results, 'page' => $model->getPage(), 'resultsCount' => $model->totalResults];
                }
            }
        }
        throw new NotFoundHttpException();
    }
}
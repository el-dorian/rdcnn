<?php


namespace app\controllers;


use app\models\Api;
use app\models\ExternalApi;
use app\models\Telegram;
use app\priv\Info;
use Throwable;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'external' || $action->id === 'do' || $action->id === 'get-file' || $action->id === 'get-schedule') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionDo(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            return Api::handleRequest();
        } catch (Throwable $e) {
            $request = Yii::$app->getRequest();
            Telegram::sendDebug("error on api:{$e->getMessage()}");
            Telegram::sendDebug("error on api:{$e->getTraceAsString()}");
            Telegram::sendDebug($request->rawBody);
            return ['status' => 'failed' . $e->getTraceAsString()];
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionExternal(): ?array
    {
        $request = Yii::$app->getRequest();
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            Telegram::sendDebug($request->rawBody);
            return ExternalApi::handleRequest();
        } catch (Throwable $e) {
            if($e instanceof NotFoundHttpException){
                throw new NotFoundHttpException($e->getMessage());
            }
            Telegram::sendDebug("error on api:{$e->getMessage()}");
            Telegram::sendDebug("error on api:{$e->getTraceAsString()}");
            Telegram::sendDebug($request->rawBody);
            return ['status' => false, 'payload' => $e->getTraceAsString()];
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionFile(): void
    {
        Api::handleFileRequest();
    }

    /**
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionGetSchedule($key): void
    {
        if ($key === Info::SCHEDULE_KEY) {
            $file = Yii::$app->getBasePath() . '/schedule/schedule.xlsx';
            Yii::$app->response->sendFile($file, 'schedule.xlsx', ['inline' => true]);
        } else {
            Telegram::sendDebug("Try to download schedule with wrong key $key");
            throw new NotFoundHttpException();
        }
    }
}
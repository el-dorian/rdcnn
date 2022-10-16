<?php


namespace app\controllers;


use app\models\database\Archive_complex_execution_info;
use app\models\database\Conclusion;
use app\models\FileUtils;
use app\models\handlers\AccessHandler;
use app\priv\Info;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PrintController extends Controller
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
                            'conclusion-print'
                        ],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'archive-conclusion-print',
                        ],
                        'roles' => [
                            'manager'
                        ],
                        //'ips' => Info::ACCEPTED_IPS,
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionConclusionPrint(string $id): void
    {
        $conclusion = Conclusion::findOne($id);
        if (($conclusion !== null) && AccessHandler::conclusionCanBeDownload($conclusion)) {
            $path = Info::CONC_FOLDER . $conclusion->path_to_file;
            if (is_file($path)) {
                $tempFile = tempnam(sys_get_temp_dir(), $conclusion->hash);
                FileUtils::addBackground($path, $tempFile, $conclusion->execution_number);
                rename($tempFile, "$tempFile.pdf");
                Yii::$app->response->sendFile("$tempFile.pdf", "Заключение МРТ №$conclusion->execution_number $conclusion->execution_area.pdf", ['inline' => true])->on(Response::EVENT_AFTER_SEND, function ($event) {
                    unlink($event->data);
                }, "$tempFile.pdf");
            } else {
                throw new NotFoundHttpException();
            }
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionArchiveConclusionPrint(string $id): void
    {
        $conclusion = Archive_complex_execution_info::findOne(['execution_identifier' => $id]);
        if (($conclusion !== null)) {
            $path = Info::PDF_ARCHIVE_PATH . $conclusion->pdf_path;
            if (is_file($path)) {
                Yii::$app->response->sendFile($path, "Заключение МРТ №$conclusion->execution_number $conclusion->execution_area.pdf", ['inline' => true]);
            } else {
                throw new NotFoundHttpException();
            }
        } else {
            throw new NotFoundHttpException();
        }
    }

}
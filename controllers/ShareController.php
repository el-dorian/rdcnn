<?php


namespace app\controllers;


use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\database\TempDownloadLinks;
use app\models\FileUtils;
use app\models\handlers\AccessHandler;
use app\models\User;
use app\models\utils\GrammarHandler;
use app\priv\Info;
use Exception;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ShareController extends Controller
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
                            'check-share-link'
                        ],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'handle-link'
                        ],
                        'roles' => ['@', '?'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function actionCheckShareLink(string $type, int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ($type === 'conclusion') {
            $conclusion = Conclusion::findOne($id);
            if (($conclusion !== null) && AccessHandler::conclusionCanBeDownload($conclusion)) {
                $link = TempDownloadLinks::findOne(['file_type' => 'conclusion', 'file_name' => $id]);
                if ($link === null) {
                    $link = new TempDownloadLinks([
                        'file_type' => 'conclusion',
                        'file_name' => $id,
                        'execution_id' => User::findByUsername($conclusion->execution_number)->id,
                        'link' => Yii::$app->security->generateRandomString(64)
                    ]);
                    $link->save();
                }
                return ['status' => true, 'link' => $link->link];
            }
        } else if ($type === 'execution') {
            $dicom = DicomArchive::findOne($id);
            if (($dicom !== null) && AccessHandler::dicomCanBeDownload($dicom)) {
                $link = TempDownloadLinks::findOne(['file_type' => 'execution', 'file_name' => $id]);
                if ($link === null) {
                    $link = new TempDownloadLinks([
                        'file_type' => 'execution',
                        'file_name' => $id,
                        'execution_id' => User::findByUsername($dicom->execution_number)->id,
                        'link' => Yii::$app->security->generateRandomString(64)
                    ]);
                    $link->save();
                }
                return ['status' => true, 'link' => $link->link];
            }
        }
        return ['status' => false];
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionHandleLink($link)
    {
        $linkItem = TempDownloadLinks::findOne(['link' => $link]);
        if ($linkItem !== null) {
            if ($linkItem->file_type === 'conclusion') {
                $conclusion = Conclusion::findOne(['id' => $linkItem->file_name]);
                if ($conclusion !== null) {
                    $path = Info::CONC_FOLDER . $conclusion->path_to_file;
                    if (is_file($path)) {
                        // add background and download file
                        $tempFile = tempnam(sys_get_temp_dir(), $conclusion->hash);
                        FileUtils::addBackground($path, $tempFile, $conclusion->execution_number);
                        Yii::$app->response->sendFile($tempFile, "Заключение МРТ №$conclusion->execution_number $conclusion->execution_area.pdf")->on(Response::EVENT_AFTER_SEND, function ($event) {
                            unlink($event->data);
                        }, $tempFile);
                        return;
                    }
                }
            } else if ($linkItem->file_type === 'execution') {
                $dicom = DicomArchive::findOne(['id' => $linkItem->file_name]);
                if ($dicom !== null) {
                    $path = Info::EXEC_FOLDER . $dicom->path_to_file;
                    if (is_file($path)) {
                        Yii::$app->response->sendFile($path, "МРТ архив изображений DICOM по обследованию $dicom->execution_number.zip");
                        return;
                    }
                }
            }
        }
        throw new NotFoundHttpException();
    }

}
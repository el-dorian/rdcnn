<?php


namespace app\controllers;


use app\models\database\Archive_complex_execution_info;
use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\FileUtils;
use app\models\handlers\AccessHandler;
use app\models\utils\DownloadHandler;
use app\models\utils\FilesHandler;
use app\models\Viber;
use app\priv\Info;
use Yii;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class DownloadController extends Controller
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
                            'execution',
                            'conclusion',
                            'conclusion-download',
                            'dicom-download',
                            'print-conclusion',
                            'download-from-archive'
                        ],
                        'roles' => ['@'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'download-temp',
                            'download-once'],
                        'roles' => ['@', '?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [
                            'archive-conclusion-download',
                            'drop',
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
     * @inheritdoc
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'download-temp' || $action->id === 'drop' || $action->id === 'download-once') {
            // отключу csrf для возможности запроса
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Скачивание заключения
     * @throws NotFoundHttpException
     */
    public function actionExecution(): void
    {
        DownloadHandler::handleExecution();
    }

    /**
     * Скачивание заключения
     * @param $href string <p>Имя файла в виде 1.pdf</p>
     * @throws NotFoundHttpException <p>В случае отсутствия прав доступа или файла- ошибка</p>
     */
    public function actionConclusion(string $href): void
    {
        DownloadHandler::handleConclusion($href);
    }

    /**
     * Распечатывание заключения
     * @param $href
     * @throws NotFoundHttpException
     */
    public function actionPrintConclusion($href): void
    {
        DownloadHandler::handleConclusion($href, true);
    }

    /**
     * @param $link
     * @throws NotFoundHttpException
     */
    public function actionDownloadTemp($link): void
    {
        Viber::downloadTempFile($link);
    }

    /**
     * @param $link
     * @throws NotFoundHttpException
     */
    public function actionDownloadOnce($link): void
    {
        Viber::downloadOnceFile($link);
    }

    public function actionDrop(): void
    {
        FilesHandler::handleDroppedFile(UploadedFile::getInstanceByName('file'));
    }

    public function actionDownloadFromArchive($id)
    {
        DownloadHandler::downloadArchiveConclusion($id);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionConclusionDownload($id): void
    {
        $conclusion = Conclusion::findOne($id);
        if (($conclusion !== null) && AccessHandler::conclusionCanBeDownload($conclusion)) {
            DownloadHandler::uploadConclusionFile($conclusion);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionArchiveConclusionDownload($id): void
    {
        $conclusion = Archive_complex_execution_info::findOne(['execution_identifier' => $id]);
        if (($conclusion !== null)) {
            $path = Info::PDF_ARCHIVE_PATH . $conclusion->pdf_path;
            if (is_file($path)) {
                // add background and download file
                $tempFile = tempnam(sys_get_temp_dir(), $conclusion->execution_identifier);
                FileUtils::addBackground($path, $tempFile, $conclusion->execution_number);
                Yii::$app->response->sendFile($tempFile, "Заключение МРТ №$conclusion->execution_number $conclusion->execution_area.pdf")->on(Response::EVENT_AFTER_SEND, function ($event) {
                    unlink($event->data);
                }, $tempFile);
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
    public function actionDicomDownload($id): void
    {
        $dicom = DicomArchive::findOne($id);
        if (($dicom !== null) && AccessHandler::dicomCanBeDownload($dicom)) {
            $path = Info::EXEC_FOLDER . $dicom->path_to_file;
            if (is_file($path)) {
                // add background and download file
                Yii::$app->response->sendFile($path, "МРТ архив изображений DICOM по обследованию $dicom->execution_number.zip");
            } else {
                throw new NotFoundHttpException();
            }
        } else {
            throw new NotFoundHttpException();
        }
    }
}
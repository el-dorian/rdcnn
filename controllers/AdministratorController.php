<?php


namespace app\controllers;


use app\models\AdministratorActions;
use app\models\database\Archive_complex_execution_info;
use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\ExecutionHandler;
use app\models\FileUtils;
use app\models\Table_availability;
use app\models\Telegram;
use app\models\User;
use app\models\utils\GlobalPatientSearch;
use app\models\utils\MailHandler;
use app\models\utils\Management;
use app\models\utils\PatientSearch;
use app\models\utils\TimeHandler;
use app\priv\Info;
use JetBrains\PhpStorm\ArrayShape;
use Throwable;
use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdministratorController extends Controller
{
    #[ArrayShape(['access' => "array"])] public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function () {
                    return $this->redirect('error', 404);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'add-execution',
                            'check-examination-id',
                            'change-password',
                            'delete-item',
                            'add-conclusion',
                            'add-execution-data',
                            'patients-check',
                            'files-check',
                            'delete-unhandled-folder',
                            'rename-unhandled-folder',
                            'print-missed-conclusions-list',
                            'register-next-patient',
                            'send-info-mail',
                            'auto-print',
                            'show-notifications',
                            'delete-conclusion-file',
                            'print-conclusions',
                            'print-conclusion',
                            'download-dicom',
                            'archive-print',
                            'account-enable',
                            'account-create',
                            'patient-search',
                            'test',
                        ],
                        'roles' => [
                            'manager'
                        ],
                        'ips' => Info::ACCEPTED_IPS,
                    ],
                ],
            ],
        ];
    }

    /**
     * ?????????????????????? ????????????????
     * @return array
     * @throws Exception
     */
    public function actionAddExecution(): array
    {
        if (Yii::$app->request->isAjax && Yii::$app->request->isGet) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            return ['status' => 1, 'header' => '???????????????????? ????????????????????????', 'view' => $this->renderAjax('add-execution-form', ['model' => $model])];
        }

        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExecutionHandler(['scenario' => ExecutionHandler::SCENARIO_ADD]);
            $model->load(Yii::$app->request->post());
            return $model->register();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionChangePassword(): array
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_CHANGE_PASSWORD]);
            $model->load(Yii::$app->request->post());
            return $model->changePassword();
        }
        throw new NotFoundHttpException();
    }

    /**
     * @return array
     * @throws Exception
     * @throws NotFoundHttpException|Throwable
     */
    public function actionDeleteItem(): array
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new AdministratorActions(['scenario' => AdministratorActions::SCENARIO_DELETE_ITEM]);
            $model->load(Yii::$app->request->post());
            return $model->deleteItem();
        }
        throw new NotFoundHttpException();
    }


    /**
     * @return array
     * @throws Exception
     */
    public function actionPatientsCheck(): array
    {
        // here send time from last success check to TG
        $now = time();
        $handleTime = FileUtils::getLastUpdateTime();
        if ($now - $handleTime > 6000) {
            Telegram::sendDebug("???????????? ?????????? ???? ???????????????? ????????????, ?????????????????? ??????: " . TimeHandler::timestampToDateTime($handleTime));
        }
        try {
            $isCheckStarted = Management::handleChanges();
        } catch (\Exception $e) {
            $isCheckStarted = $e->getMessage();
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return AdministratorActions::checkPatients($isCheckStarted);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function actionCheckExaminationId(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $value = Yii::$app->request->post('input');
        if (!empty($value)) {
            $registeredExamination = User::findByUsername($value);
            if ($registeredExamination === null) {
                return ['status' => true, 'state' => 0];
            }
            return ['status' => true, 'state' => $registeredExamination->status];
        }
        return ['status' => false];
    }

    /**
     * @throws \yii\base\Exception
     */
    #[ArrayShape(['status' => "int", 'header' => "string", 'message' => "string"])] public function actionFilesCheck($executionNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::checkFiles($executionNumber);
    }

    #[ArrayShape(['status' => "int"])] public function actionDeleteUnhandledFolder(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        FileUtils::deleteUnhandledFolder();
        return ['status' => 1];
    }

    #[ArrayShape(['status' => "int"])] public function actionRenameUnhandledFolder(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        FileUtils::renameUnhandledFolder();
        return ['status' => 1];
    }

    public function actionPrintMissedConclusionsList(): string
    {
        return $this->render('missed-conclusions-list');
    }

    /**
     */
    public function actionTest(): void
    {
        User::findIdentityByAccessToken("test");
    }

    /**
     * @throws \yii\base\Exception
     */
    public function actionSendInfoMail($id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        // ???????????????? ???????????? ?? ??????????????????????, ???????? ???????? ??????????
        return MailHandler::sendInfoMail($id);
    }

    /**
     * @param $center
     * @return array
     * @throws Exception
     */
    #[ArrayShape(['status' => "int", 'message' => "string"])] public function actionRegisterNextPatient($center): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ExecutionHandler::registerNext($center);
    }

    public function actionAutoPrint($fileName): void
    {
        $file = Yii::getAlias('@conclusionsDirectory') . '\\' . 'nb_' . $fileName;
        if (!is_file($file)) {
            $file = Yii::getAlias('@conclusionsDirectory') . '\\' . $fileName;
        }
        Yii::$app->response->sendFile($file, '????????????????????', ['inline' => true]);
    }

    #[ArrayShape(['status' => "string"])] public function actionShowNotifications(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $state = Yii::$app->request->post('state');
        $cookies = Yii::$app->response->cookies;
        if ($state === 'true') {

// ???????????????????? ?????????? ???????? ?? HTTP-??????????
            $cookies->add(new Cookie([
                'name' => 'show_notifications',
                'value' => $state,
                'httpOnly' => false,
            ]));
        } else {
            $cookies->remove('show_notifications');
        }
        return ['status' => 'success'];
    }

    #[ArrayShape(['status' => "string"])] public function actionDeleteConclusionFile($filename): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $path = Info::CONC_FOLDER . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
        return ['status' => 'success'];
    }

    public function actionPrintConclusions($executionNumber): string
    {
        // ?????????? ?????????? ???????????????????? ???? ?????????????? ??????????????
        $conclusions = Archive_complex_execution_info::find()->where(['execution_number' => $executionNumber])->all();
        $conclusionsInPersonalArea = Table_availability::find()->where(['userId' => $executionNumber, 'is_conclusion' => 1])->all();
        return $this->render('show-conclusions', ['archiveList' => $conclusions, 'personalList' => $conclusionsInPersonalArea]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionPrintConclusion(int $conclusionId): void
    {
        $conclusion = Conclusion::findOne($conclusionId);
        if ($conclusion !== null) {
            $file = Info::CONC_FOLDER . $conclusion->path_to_file;
            if (is_file($file)) {
                Yii::$app->response->sendFile($file, '????????????????????.pdf', ['inline' => true]);
            } else {
                throw new NotFoundHttpException("???? ???????????? ???????? ????????????????????");
            }
        }
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionDownloadDicom(int $dicomId): void
    {
        $archive = DicomArchive::findOne($dicomId);
        if ($archive !== null) {
            $file = Info::EXEC_FOLDER . $archive->path_to_file;
            if (is_file($file)) {
                Yii::$app->response->sendFile($file, "$archive->execution_number.zip");
            } else {
                throw new NotFoundHttpException("???? ???????????? ???????? ????????????????????");
            }
        }
    }

    /**
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionAccountEnable($executionNumber, $changePass = false): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax) {
            $enableResult = User::enableAccount($executionNumber);
            if ($enableResult) {
                Table_availability::deleteUserData($executionNumber);
                Archive_complex_execution_info::restoreConclusions($executionNumber);
                if ($changePass) {
                    $newPass = User::changePass($executionNumber);
                    return ['status' => 1, 'header' => '?????????????? ???????????? ????????????????????????', 'message' => "???????????? ???????????????????????? ???????????????????????? ???? 5 ????????. ?????????? ???????????? ????????????????????????- $newPass", 'reload' => 1];
                }
                return ['status' => 1, 'header' => '?????????????? ???????????? ????????????????????????', 'message' => '???????????? ???????????????????????? ???????????????????????? ???? 5 ????????', 'reload' => 1];
            }
            return ['status' => 1, 'header' => '?????????????? ???????????? ???? ??????????????', 'message' => '???? ?????????????? ???????????????????????? ?????????????? ????????????, ???????????????????? ???????????????????????????????? ???? ????????????!'];
        }
        throw new NotFoundHttpException();
    }

    /**
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\base\Exception
     */
    public function actionAccountCreate($executionNumber): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (Yii::$app->request->isAjax) {
            $existentUser = User::findByUsername($executionNumber);
            if ($existentUser !== null) {
                return ['status' => 1, 'header' => '?????????????? ???????????? ?????? ????????????????????', 'message' => '?? ?????????? ???????????????? ?????? ???????? ???????????????? ?????????????? ????????????.'];
            }
            $newPass = ExecutionHandler::createUser($executionNumber);
            Table_availability::deleteUserData($executionNumber);
            Archive_complex_execution_info::restoreConclusions($executionNumber);
            return ['status' => 1, '?????????????? ???????????? ??????????????', 'message' => "?????????????? ???????????? ??????????????. ?????????? ???????????? ????????????????????????- $newPass", 'reload' => 1];
        }
        throw new NotFoundHttpException();
    }

    /**
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionArchivePrint($identifier): void
    {
        $archiveItem = Archive_complex_execution_info::findOne(['execution_identifier' => $identifier]);
        if ($archiveItem !== null) {
            $file = Info::ARCHIVE_PATH . DIRECTORY_SEPARATOR . $archiveItem->pdf_path;
            if (is_file($file)) {
                Yii::$app->response->sendFile($file, '????????????????????.pdf', ['inline' => true]);
                return;
            }
        }
        throw new NotFoundHttpException();
    }

    public function actionPatientSearch(){
        $this->layout = 'administrate';
        $model = new GlobalPatientSearch();
        return $this->render('patient-search', ['model' => $model]);
    }
}
<?php


namespace app\models;


use app\models\database\Archive_complex_execution_info;
use app\models\database\Archive_conclusion_text;
use app\models\database\Archive_doctor;
use app\models\database\Archive_execution;
use app\models\database\Archive_execution_area;
use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\utils\ConclusionsSearch;
use app\models\utils\DownloadHandler;
use app\models\utils\GrammarHandler;
use app\priv\Info;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Request;

class ExternalApi
{

    /**
     * Обработка запроса
     * @return array|null
     * @throws \yii\web\NotFoundHttpException
     */

    public static function handleRequest(): ?array
    {
        $request = Yii::$app->getRequest();
        $token = $request->bodyParams['token'];
        if (!self::token_valid($token)) {
            return ['status' => false, 'payload' => 'Неверный токен доступа'];
        }
        if (!empty($request->bodyParams['cmd'])) {
            $cmd = $request->bodyParams['cmd'];
            switch ($cmd) {
                case 'register_execution':
                    Telegram::sendDebug("register execution!");
                    $examinationNumber = $request->bodyParams['execution_number'];
                    return self::registerExecution($examinationNumber);
                case 'register_next_execution':
                    $center = $request->bodyParams['center'];
                    Telegram::sendDebug("register next $center execution!");
                    return self::registerNextExecution($center);
                case 'change_patient_password':
                    Telegram::sendDebug("change user password!");
                    $examinationNumber = $request->bodyParams['execution_number'];
                    return self::changePatientPassword($examinationNumber);
                case 'check_archive_conclusions':
                    $examinationNumber = $request->bodyParams['execution_number'];
                    return self::checkArchiveConclusions($examinationNumber);
                case 'get_daily_info':
                    return self::getDailyInfo($request);
                case 'get_conclusion':
                    $conclusionId = $request->bodyParams['id'];
                    self::uploadConclusion($conclusionId);
                    return null;
                case 'get_conclusion_without_background':
                    $conclusionId = $request->bodyParams['id'];
                    $isArchive = $request->getBodyParam('isArchive');
                    self::uploadConclusionWithoutBackground($conclusionId, $isArchive);
                    return null;
                case 'get_dicom':
                    // here handle file
                    $dicomId = $request->bodyParams['id'];
                    self::uploadDicom($dicomId);
                    return null;
                case 'get_diagnosticians':
                    return self::getArchiveDiagnosticians();
                case 'get_examination_areas':
                    return self::getArchiveExecutionAreas();
                case 'search_conclusions':
                    return self::searchConclusions($request);
                case 'get_conclusion_text':
                    return self::getConclusionText($request);
            }
            return ['status' => false, 'payload' => 'unknown action'];
        }
        return ['status' => false];
    }

    private static function token_valid($token): bool
    {
        $user = User::findIdentityByAccessToken($token);
        return $user !== null && $user->username === User::ADMIN_NAME;
    }

    private static function registerExecution(string $examinationNumber): array
    {
        $examinationNumber = GrammarHandler::toLatin($examinationNumber);
        // check what execution not registered
        if (User::findByUsername($examinationNumber) !== null) {
            return ['status' => false, 'payload' => 'Обследование уже зарегистрировано'];
        }
        // register
        try {
            $password = ExecutionHandler::createUser($examinationNumber);
            $user = User::findByUsername($examinationNumber);
            if ($user !== null) {
                return ['status' => true, 'payload' => json_encode(['password' => $password, 'accessToken' => $user->access_token, 'examinationId' => $examinationNumber, 'patientId' => $user->id, 'lifetimeEnd' => $user->updated_at + Info::DATA_SAVING_TIME], JSON_THROW_ON_ERROR)];
            }
            return ['status' => false, 'payload' => 'Не удалось создать учётную запись пользователя'];
        } catch (Exception $e) {
            return ['status' => false, 'payload' => $e->getMessage()];
        }
    }

    private static function changePatientPassword(string $examinationNumber): array
    {
        $user = User::findByUsername($examinationNumber);
        if ($user !== null) {
            try {
                $password = User::generateNumericPassword();
                $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
                $user->password_hash = $hash;
                $user->failed_try = 0;
                $user->status = 1;
                $user->save();
                return ['status' => true, 'payload' => json_encode(['password' => $password, 'examinationId' => $examinationNumber, 'patientId' => $user->id, 'lifetimeEnd' => $user->updated_at + Info::DATA_SAVING_TIME], JSON_THROW_ON_ERROR)];
            } catch (Exception $e) {
                return ['status' => false, 'payload' => 'Не удалось обновить пароль'];
            }
        }
        return ['status' => false, 'payload' => 'Пациент не найден'];
    }

    private static function registerNextExecution(string $center): array
    {
        // register
        try {
            $previousRegged = User::getLast($center);
            // найду первый свободный номер после последнего зарегистрированного
            $examinationNumber = $previousRegged;
            while (true) {
                $examinationNumber = User::getNext($examinationNumber);
                if (User::findByUsername($examinationNumber) === null) {
                    break;
                }
            }
            $password = ExecutionHandler::createUser($examinationNumber);
            $user = User::findByUsername($examinationNumber);
            if ($user !== null) {
                return ['status' => true, 'payload' => json_encode(['password' => $password, 'examinationId' => $examinationNumber, 'patientId' => $user->id, 'lifetimeEnd' => $user->updated_at + Info::DATA_SAVING_TIME], JSON_THROW_ON_ERROR)];
            }
            return ['status' => false, 'payload' => 'Не удалось создать учётную запись пользователя'];
        } catch (Exception $e) {
            return ['status' => false, 'payload' => $e->getMessage()];
        }
    }

    #[ArrayShape(['status' => "bool", 'payload' => "array"])] private static function getDailyInfo($request): array
    {
        $date = $request->bodyParams['date'];
        $center = (int)$request->bodyParams['center'];
        return ['status' => true, 'payload' => ExecutionHandler::getDailyInfo($date, $center)];
    }

    #[ArrayShape(['status' => "bool", 'conclusions' => "Archive_complex_execution_info[]"])] private static function checkArchiveConclusions(mixed $examinationNumber): array
    {
        return ['status' => true, 'conclusions' => Archive_complex_execution_info::findAll(['execution_number' => $examinationNumber])];
    }

    /**
     * @throws \yii\web\NotFoundHttpException
     */
    private static function uploadConclusion(int $conclusionId): void
    {
        $conclusion = Conclusion::findOne($conclusionId);
        if ($conclusion !== null) {
            DownloadHandler::uploadConclusionFile($conclusion);
            return;
        }
        throw new NotFoundHttpException("Заключение не найдено");
    }

    /**
     * @throws NotFoundHttpException
     */
    private static function uploadConclusionWithoutBackground(mixed $conclusionId, $isArchive = false): void
    {
        if($isArchive){
            $conclusion = Archive_execution::findOne($conclusionId);
            if($conclusion !== null){
                DownloadHandler::uploadArchiveConclusionFileWithoutBackground($conclusion);
                return;
            }
        }
        else{
            $conclusion = Conclusion::findOne($conclusionId);
            if ($conclusion !== null) {
                DownloadHandler::uploadConclusionFileWithoutBackground($conclusion);
                return;
            }
        }
        throw new NotFoundHttpException("Заключение не найдено");
    }

    private static function uploadDicom(mixed $dicomId)
    {
        $dicomArchive = DicomArchive::findOne($dicomId);
        if ($dicomArchive !== null) {
            DownloadHandler::uploadDicom($dicomArchive);
            return;
        }
        throw new NotFoundHttpException("Архив DICOM не найден");
    }

    private static function getArchiveDiagnosticians(): array
    {
        $diagnosticians = Archive_doctor::find()->all();
        $names = [];
        foreach ($diagnosticians as $diagnostician) {
            $names[] = $diagnostician->doc_name;
        }
        return ['status' => true, 'list' => $names];
    }

    private static function getArchiveExecutionAreas(): array
    {
        $items = Archive_execution_area::find()->orderBy('area_name')->all();
        $names = [];
        foreach ($items as $item) {
            $names[] = $item->area_name;
        }
        return ['status' => true, 'list' => $names];
    }

    private static function searchConclusions(Request $request): array
    {
        return (new ConclusionsSearch())->makeGlobalSearch($request);
    }

    private static function getConclusionText(Request $request): array
    {
        $id = $request->getBodyParam("id");
        $isArchive = $request->getBodyParam("is_archive");
        if ($isArchive) {
            $conclusion = Archive_execution::findOne($id);
            if ($conclusion !== null) {
                $text = Archive_conclusion_text::findOne($conclusion->text);
                if ($text !== null) {
                    return ['status' => true, 'payload' => $text->conclusion_text];
                }

            }
        } else {
            $conclusion = Conclusion::findOne($id);
            if ($conclusion !== null) {
                return ['status' => true, 'payload' => $conclusion->conclusion_text];
            }
        }
        return ['status' => false, 'message' => 'Заключение не найдено'];
    }

}
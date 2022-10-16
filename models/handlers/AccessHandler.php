<?php

namespace app\models\handlers;

use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use Yii;

class AccessHandler
{

    public static function conclusionCanBeDownload(Conclusion $conclusion): bool
    {
        if (Yii::$app->user->can('manage')) {
            return true;
        }
        if (Yii::$app->user->can('read')) {
            $executionNumber = Yii::$app->user->identity->username;
            return $conclusion->execution_number === $executionNumber;
        }
        return false;
    }

    public static function dicomCanBeDownload(DicomArchive $dicom): bool
    {
        if (Yii::$app->user->can('manage')) {
            return true;
        }
        if (Yii::$app->user->can('read')) {
            $executionNumber = Yii::$app->user->identity->username;
            return $dicom->execution_number === $executionNumber;
        }
        return false;
    }
}
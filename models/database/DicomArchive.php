<?php


namespace app\models\database;


use app\models\Table_availability;
use app\models\User;
use app\models\utils\GrammarHandler;
use app\models\utils\MailSettings;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * @property int $id [int(10) unsigned]
 * @property string $execution_number [varchar(100)]
 * @property string $path_to_file [varchar(512)]
 * @property string $hash [varchar(255)]
 */
class DicomArchive extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'execution';
    }
}
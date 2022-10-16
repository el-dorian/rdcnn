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
 * @property string $execution_number [varchar(255)]
 * @property string $execution_date [date]
 * @property string $diagnostician [varchar(255)]
 * @property string $conclusion_text
 * @property string $patient_name [varchar(255)]
 * @property string $patient_sex [enum('м', 'ж', '-')]
 * @property string $execution_area [varchar(255)]
 * @property string $contrast_info [varchar(255)]
 * @property string $path_to_file [varchar(512)]
 * @property string $hash [varchar(255)]
 * @property string $patient_birthdate [date]
 */
class Conclusion extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'conclusion';
    }

    public function __toString(): string
    {

    }
}
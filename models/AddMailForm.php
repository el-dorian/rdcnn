<?php

namespace app\models;

use yii\base\Model;

class AddMailForm extends Model
{
    public string $patientId;
    public array $addresses = [];
}
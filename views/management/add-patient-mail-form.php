<?php


/* @var $this View */

/* @var $model Emails */

use app\models\database\Emails;
use app\models\Table_availability;
use app\models\User;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

$patientInfo = User::findIdentity($model->patient_id);

if ($patientInfo !== null) {
    echo "<h2 class='text-center'>Номер обследования: {$patientInfo->username}</h2>";
    $name = Table_availability::getPatientName($patientInfo->username);
    if ($name !== null) {
        echo "<h3 class='text-center'>{$name}</h3>";
    }
}

$form = ActiveForm::begin(['id' => 'addMailForm', 'options' => ['class' => 'form-horizontal bg-default', 'enctype' => 'multipart/form-data'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/mail/add']]);

echo $form->field($model, 'patient_id', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'emails')->widget(MultipleInput::className(), [
    'max'               => 6,
    'min'               => 2, // should be at least 2 rows
    'allowEmptyList'    => false,
    'enableGuessTitle'  => true,
    'addButtonPosition' => MultipleInput::POS_HEADER, // show add button in the header
])
    ->label(false);

echo Html::submitButton('Сохранить', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();
?>
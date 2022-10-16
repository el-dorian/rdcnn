<?php

use app\assets\SearchAsset;
use app\widgets\ContrastColorWidget;
use app\widgets\ModalityColorWidget;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\widgets\ActiveForm;


/* @var $this \yii\web\View */

/* @var $model \app\models\utils\GlobalPatientSearch */

/* @var $results \app\models\selections\SearchResult[] */

SearchAsset::register($this);
ShowLoadingAsset::register($this);

echo '<div class="row">';
$form = ActiveForm::begin(['id' => 'Search', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'action' => ['/administrator/patient-search']]);
echo $form->field($model, 'page', ['template' => '{input}'])->hiddenInput()->label(false);
echo $form->field($model, 'executionNumber', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->input('text')
    ->label('Номер обследования');
echo $form->field($model, 'patientPersonals', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->input('text')
    ->label('ФИО пациента');
echo $form->field($model, 'executionDateStart', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->input('date')
    ->label('С');
echo $form->field($model, 'executionDateFinish', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->input('date')
    ->label('По');
echo $form->field($model, 'sortBy', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->dropDownList([
        0 => 'По времени добавления ↧ ',
        1 => 'По времени добавления ↥ ',
        2 => 'По имени пациента ↧ ',
        3 => 'По имени пациента ↥ ',
        4 => 'По центру ↧ ',
        5 => 'По центру ↥ ',
        6 => 'По доктору ↧ ',
        7 => 'По доктору ↥ ',
        8 => 'По контрасту ↧ ',
        9 => 'По контрасту ↥ ',
        10 => 'По области обследования ↧ ',
        11 => 'По области обследования ↥ ',
    ],
        ['encode' => false])
    ->label('Сортировка');
echo $form->field($model, 'center', ['template' =>
    '<div class="col-lg-6 col-sm-5 text-right">{label}</div><div class="col-lg-6 col-sm-7"> {input}{error}{hint}</div>', 'options' => ['class' => 'form-group col-sm-6 col-lg-5']])
    ->dropDownList([
        0 => 'Все',
        1 => 'Аврора',
        2 => 'НВН',
        3 => 'КТ',
    ],
        ['encode' => false]);


echo "<div class='col-sm-12 text-center margin'>";
echo Html::submitButton('Найти', ['class' => 'btn btn-success btn-sm margin', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
echo '<a href="/administrator/patient-search" class="btn btn-warning btn-sm">Новый поиск</a>';
echo '</div>';
ActiveForm::end();
echo '</div>';

echo "<div id='resultsContainer'>
<div class='text-center'><b id='resultsTotalCount'></b></div>
<table class='table table-condensed table-hover' id='resultsTable'>
<thead>
<tr><th>Тип</th><th>ID</th><th>Дата</th><th>Пациент</th><th>Область</th><th>Контраст</th><th>Статус</th><th>Действия</th></tr>
</thead>
<tbody id='resultsBody'>

</tbody>
</table>
<div class='text-center'><button id='loadMoreBtn' class='btn btn-default'><b class='text-success'>Загрузить ещё</b></button></div>
</div>";

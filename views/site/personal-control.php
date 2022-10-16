<?php

/* @var $this View */

use app\assets\PersonalControlAsset;
use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\database\Emails;
use app\models\ExecutionHandler;
use app\models\Table_availability;
use app\models\User;
use app\models\Utils;
use app\models\utils\TimeHandler;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

PersonalControlAsset::register($this);
ShowLoadingAsset::register($this);

$this->title = 'Администрирование';

/* @var $this View */
/* @var $executions User[] */
/* @var $model ExecutionHandler */

$centers = ['all' => '', 'nv' => '', 'aurora' => '', 'ct' => ''];

$days = ['all' => '', 'today' => '', 'yesterday' => ''];

$sort = ['byTime' => '', 'byNumber' => '', 'byExecutions' => '', 'byConclusion' => ''];

if (Utils::isCenterFiltered()) {
    $center = Yii::$app->session['center'];
    foreach ($centers as $key => $value) {
        if ($key === $center) {
            $centers[$key] = 'selected';
        } else {
            $centers[$key] = '';
        }
    }
}
if (Utils::isTimeFiltered()) {
    $time = Yii::$app->session['timeInterval'];
    foreach ($days as $key => $value) {
        if ($key === $time) {
            $days[$key] = 'selected';
        } else {
            $days[$key] = '';
        }
    }
}
$sortBy = Yii::$app->session['sortBy'];
foreach ($sort as $key => $value) {
    if ($key === $sortBy) {
        $sort[$key] = 'selected';
    } else {
        $sort[$key] = '';
    }
}

echo "<div class='header fixed-header'>";
echo '<div class="col-sm-3"><div id="addExaminationHintView" style="display: none;"><b><span id="examinationIdState"></span></b><div><div class="btn-group-vertical" style="display: none;" id="addExaminationOptions"><button class="btn btn-success">Активировать</button><button class="btn btn-info">Активировать и сменить пароль</button></div></div></div><div class="input-group"><div class="input-group-btn"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></button><ul class="dropdown-menu"><li><a href="#" class="add-next" data-center="aurora"><span class="glyphicon glyphicon-plus"></span> Добавить следующего пациента Аврора</a></li><li><a href="#" class="add-next" data-center="nv"><span class="glyphicon glyphicon-plus"></span> Добавить следующего пациента НВН</a></li><li><a href="#" class="add-next" data-center="ct"><span class="glyphicon glyphicon-plus"></span> Добавить следующего пациента КТ</a></li></ul></div><input type="text" id="add-execution" placeholder="Зарегистрировать" class="form-control" tabindex="1" autocomplete="off"><span class="input-group-btn"><button class="btn btn-success" id="addPatientBtn"><span class="glyphicon glyphicon-plus"></span></button></span></div></div>';
echo '<div class="col-sm-3"><div class="input-group"><div class="input-group-btn"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></button><ul class="dropdown-menu"><li><a href="#" id="filterByConclusions"><span class="glyphicon glyphicon-ok filter-conclusion filter-glyphicon" style="display: none"></span> Без заключений</li><li><a href="#" id="filterByDicom"><span class="glyphicon glyphicon-ok filter-dicom filter-glyphicon" style="display: none"></span> Без снимков</a></li><li><a href="#"  id="filterByErrors"><span class="glyphicon glyphicon-ok filter-errors filter-glyphicon" style="display: none"></span> С ошибками</a></li><li><a href="#"  id="filterNone"><span class="glyphicon glyphicon-ok filter-none filter-glyphicon"></span> Все</a></li></ul></div><input type="text" id="filter-executions-list" placeholder="Фильтр обследования" class="form-control" tabindex="1" autocomplete="off"><span class="input-group-btn"><button class="btn btn-info"><span class="glyphicon glyphicon-search"></span></button></span></div></div>';
echo '<div class="col-sm-6"><div class="btn-group"><button id="serverStateBtn" class="btn"><span id="serverStateGlyphicon"></span> <span id="serverStateText"></span></button><a target="_blank" class="btn btn-info" href="/administrator/patient-search"><span class="glyphicon glyphicon-search"></span> Поиск</a></div></div>';
echo "</div>";

// добавлю кнопку для создания нового обследования
echo "<div class='col-xs-12 text-center'>";

echo "</div><div class='col-xs-12 navigation-center'>";

echo Html::beginForm(['/site/personal-control']);

echo "<div class='col-sm-4 col-xs-12'><label class='control-label' for='#centerSelect'>Центр</label><select id='centerSelect' name='center' onchange='this.form.submit();' class='form-control'><option value='all'>Все</option><option value='nv' {$centers['nv']}>Нижневолжская набережная</option><option value='aurora' {$centers['aurora']}>Аврора</option><option value='ct' {$centers['ct']}>КТ</option></select></div>";

echo "<div class='col-sm-4 col-xs-12'><label class='control-label' for='#centerSelect'>Время</label><select name='timeInterval' onchange='this.form.submit();' class='form-control'><option value='all'>Всё время</option><option value='today' {$days['today']}>Сегодня</option><option value='yesterday' {$days['yesterday']}>Вчера</option></select></div>";

echo "<div class='col-sm-4 col-xs-12'><label class='control-label' for='#sortBy'>Сортировать по</label><select name='sortBy' onchange='this.form.submit();' class='form-control'><option value='byTime' {$sort['byTime']}>Времени добавления</option><option value='byNumber' {$sort['byNumber']}>Номеру обследования</option><option value='byConclusion' {$sort['byConclusion']}>Наличию заключения</option><option value='byExecutions' {$sort['byExecutions']}>Наличию файлов</option></select></div>";

echo Html::endForm();

echo '</div>';

echo "
    <div class='col-xs-12 margin'> <div class='col-xs-4 text-center'>Всего обследований: <b class='text-info'><span id='patientsCount'>0</span></b></div><div class='col-xs-4 text-center'>Без заключений: <b class='text-danger'><span id='withoutConclusions'>0</span></b></div><div class='col-xs-4 text-danger text-center'>Без файлов: <b class='text-danger'><span id='withoutExecutions'>0</span></b></div></div>
";

$executionsCounter = 0;

if (!empty($executions)) {
    echo "<table class='table-hover table patients-table'>
<thead>
<tr>
<th><span class='visible-md visible-lg'>Номер обследования</span><span class='visible-xs visible-sm'>№</span></th>
<th><span class='visible-md visible-lg'>Действия</span><span class='visible-xs visible-sm'></span></th>
<th><span class='visible-md visible-lg'>Загружено заключение</span><span class='visible-xs visible-sm glyphicon glyphicon-file'></span></th>
<th><span class='visible-md visible-lg'>Загружены изображения</span><span class='visible-xs visible-sm glyphicon glyphicon-folder-close'></span></th>
</tr></thead><tbody id='executionsBody'>";
    foreach ($executions as $execution) {
        // проверю, если включена фильтрация по центру-выведу только те обследования, которые проведены в этом центре
        if (Utils::isCenterFiltered() && Utils::isFiltered($execution)) {
            continue;
        }
        ++$executionsCounter;

        $registeredConclusions = Conclusion::findAll(['execution_number' => $execution->username]);
        if (!empty($registeredConclusions)) {
            $patientName = $registeredConclusions[0]->patient_name;
        } else {
            $patientName = null;
        }

        $registeredExecution = DicomArchive::findOne(['execution_number' => $execution->username]);

        $itemText = "<tr class='patient' data-id='$execution->id' data-username='$execution->username'>";
        if ($patientName !== null) {
            $itemText .= "<td class='execution-id'>
                            <a class='btn-link execution-id tooltip-enabled' href='/patient/$execution->access_token' data-toggle='tooltip' data-placement='auto' title='{$patientName}'>$execution->username <span class='text-black registration-date'>от " . TimeHandler::timestampToDateTime($execution->created_at) . "</span><br/><span class='patient-name'>$patientName</span></a>
                        </td>";
        } else {
            $itemText .= "<td class='execution-id'>
                            <a class='btn-link execution-id' href='/patient/$execution->access_token' >$execution->username <span class='text-black registration-date'>от " . TimeHandler::timestampToDateTime($execution->created_at) . "</span></a>
                        </td>";
        }

        if (Emails::checkExistent($execution->id)) {
            $mailInfo = Emails::findOne(['patient_id' => $execution->id]);
            $hint = $mailInfo->mailed_yet ? 'Отправить письмо с данными пациенту<br/>(уже отправлялось)' : 'Отправить письмо с данными пациенту';
            $color = $mailInfo->mailed_yet ? ' text-danger' : 'text-info';
            $itemText .= "<td class='actions-td'><div class='btn-group'><button class='btn btn-default custom-activator mail-send-btn'  data-id='$execution->id' data-action='mail-send' data-toggle='tooltip' data-placement='auto' data-html='true' title='$hint'><span class='glyphicon glyphicon-circle-arrow-right $color'></span></button><button class='btn btn-default custom-activator mail-change-btn' data-action='mail-change' data-id='$execution->id' data-toggle='tooltip' data-placement='auto' title='Изменить электронную почту'><span class='glyphicon glyphicon-envelope text-info'></span></button><button class='btn btn-default custom-activator' data-action='change-password'
                   data-id='{$execution->username}' data-toggle='tooltip' data-placement='auto'
                   title='Сменить пароль'><span class='text-info glyphicon glyphicon-retweet'></span></button>
                <button class='btn btn-default custom-activator' data-action='delete' data-id='$execution->id'
                   data-toggle='tooltip' data-placement='auto' title='Удалить запись'><span
                            class='text-danger glyphicon glyphicon-trash'></span></button></div></td>";
        } else {
            $itemText .= "<td class='actions-td'><div class='btn-group'><button class='btn btn-default custom-activator mail-add-btn' data-action='mail-add'  data-id='$execution->id' data-toggle='tooltip' data-placement='auto' title='Добавить электронную почту'><span class='glyphicon glyphicon-envelope text-success'></span></button><button class='btn btn-default custom-activator' data-action='change-password' data-id='$execution->username' data-toggle='tooltip' data-placement='auto' title='Сменить пароль'><span class='text-info glyphicon glyphicon-retweet'></span></button> <button class='btn btn-default custom-activator' data-action='delete' data-id='$execution->id' data-toggle='tooltip' data-placement='auto' title='Удалить запись'><span class='text-danger glyphicon glyphicon-trash'></span></button></div></div></td>";
        }
        if (!empty($registeredConclusions)) {
            $conclusionsText = '';
            foreach ($registeredConclusions as $registeredConclusion) {
                $conclusionsText .= "<div class='conclusion-container' data-id='$registeredConclusion->id'><span>$registeredConclusion->execution_area</span> <div class='btn-group pull-right'><button data-toggle='tooltip' data-action='delete-conclusion' data-placement='auto' title='Удалить это заключение(ФИО пациента: $registeredConclusion->patient_name,область обследования: $registeredConclusion->execution_area)' data-id='$registeredConclusion->id' class='btn btn-danger btn-sm custom-activator tooltip-enabled'><span class='glyphicon glyphicon-trash'></span></button><button data-toggle='tooltip' data-action='print-conclusion' data-placement='auto' title='Распечатать это заключение(ФИО пациента: $registeredConclusion->patient_name,область обследования: $registeredConclusion->execution_area)' data-id='$registeredConclusion->id' class='btn btn-info btn-sm custom-activator tooltip-enabled'><span class='glyphicon glyphicon-print'></span></button></div></div>";
            }
            $itemText .= "<td data-conclusion='$execution->username' class='field-success conclusion-field'>$conclusionsText</td>";
        } else {
            $itemText .= "<td data-conclusion='$execution->username' class='field-danger conclusion-field'><span class='glyphicon glyphicon-remove text-danger status-icon'></span></td>";
        }
        if ($registeredExecution !== null) {
            $itemText .= "<td data-execution='$execution->username' class='field-success dicom-field'><div class='dicom-container' data-id='$registeredExecution->execution_number'><span class='glyphicon glyphicon-ok'></span> <div class='btn-group'><button data-toggle='tooltip' data-action='delete-dicom' data-placement='auto' title='Удалить снимки' data-id='$registeredExecution->id' class='btn btn-danger btn-sm custom-activator tooltip-enabled'><span class='glyphicon glyphicon-trash'></span></button><button data-toggle='tooltip' data-action='download-dicom' data-placement='auto' title='Скачать архив' data-id='$registeredExecution->id' class='btn btn-info btn-sm custom-activator tooltip-enabled'><span class='glyphicon glyphicon-download'></span></button></div></div></td>";
        } else {
            $itemText .= "<td data-execution='$execution->username' class='field-danger dicom-field'><span class='glyphicon glyphicon-remove text-danger status-icon'></span></td>";
        }
        $itemText .= "</tr>";
        echo $itemText;
    }
    echo '</tbody></table>';
}
echo "<div id='noExecutionsRegistered' class='col-xs-12' " . ($executionsCounter === 0 ? '' : "style='display: none;'") . "><h2 class='text-center'>Обследований не зарегистрировано</div>";

echo "<div class='col-xs-12 text-center'>";

echo '</div>';


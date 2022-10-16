<?php

use app\assets\PatientAsset;
use app\models\database\Archive_complex_execution_info;
use app\models\database\Archive_execution;
use app\models\database\Conclusion;
use app\models\database\DicomArchive;
use app\models\database\Reviews;
use app\models\User;
use app\priv\Info;
use chillerlan\QRCode\QRCode;
use nirvana\showloading\ShowLoadingAsset;
use yii\helpers\Html;
use yii\web\View;


PatientAsset::register($this);
ShowLoadingAsset::register($this);

/* @var $this View */
/* @var $patient User */


$this->title = 'Обследование ' . $patient->username;

?>

    <div id="ourLogo" class="visible-sm visible-md visible-lg "></div>
    <div id="ourSmallLogo" class="visible-xs"></div>

    <h1 class="text-center">Обследование № <?= $patient->username ?></h1>

<?php

$registeredConclusions = Conclusion::findAll(['execution_number' => $patient->username]);
if (!empty($registeredConclusions)) {
    echo "<h2 class='text-center'>{$registeredConclusions[0]->patient_name}</h2>";
    $patientName = $registeredConclusions[0]->patient_name;
}
?>

    <div class="col-sm-12 col-md-6 col-md-offset-3">
        <div id="conclusionsContainer">
            <?php
            if (empty($registeredConclusions)) {
                echo '<div class="alert alert-info text-center conclusion-waiter"><span class="glyphicon glyphicon-hourglass"></span> <b>Доктор ещё пишет заключение</b></div>';
            } else {
                foreach ($registeredConclusions as $registeredConclusion) {
                    echo '
            <div class="panel panel-default conclusion-container" data-id="' . $registeredConclusion->id . '">
                <div class="panel-heading">
                    <h3 class="panel-title">Заключение по обследованию</h3>
                </div>
                <div class="panel-body">
                    <p class="text-center"><b>Заключение врача: ' . $registeredConclusion->execution_area . '</b></p>
                    <div class="btn-group btn-group-justified"><div class="btn-group"><a target="_blank" class="btn btn-default tooltip-enabled" data-toggle="tooltip" title="Скачать заключение врача в формате PDF" href="/download/c/' . $registeredConclusion->id . '"><b class="text-success"><span class="glyphicon glyphicon-download-alt"></span> Скачать</b></a></div><div class="btn-group"><a target="_blank" class="btn btn-default tooltip-enabled"  data-toggle="tooltip" title="Отправить заключение на печать" href="/print/c/' . $registeredConclusion->id . '"><b class="text-info"><span class="glyphicon glyphicon-print"></span> Распечатать</b></a></div><div class="btn-group"><button class="btn btn-default tooltip-enabled share-btn" data-type="conclusion" data-id="' . $registeredConclusion->id . '"  data-toggle="tooltip" title="Поделиться ссылкой общего доступа на заключение. Любой, у кого есть ссылка, сможет его скачать"><b class="text-info"><span class="glyphicon glyphicon-share-alt"></span> Поделиться</b></button></div></div>
                </div>
            </div>';
                }
            }
            ?>
        </div>
        <div id="executionContainer">
            <?php
            // если доступно заключение-дам ссылку на него
            $dicomArchive = DicomArchive::findOne(['execution_number' => $patient->username]);
            if ($dicomArchive === null) {
                echo '<div class="alert alert-info text-center dicom-waiter"><span class="glyphicon glyphicon-hourglass"></span> <b>Мы подготавливаем архив изображений</b></div>';
            } else {
                echo '
            <div class="panel panel-default dicom-container">
                <div class="panel-heading">
                    <h3 class="panel-title">Архив снимков</h3>
                </div>
                <div class="panel-body">
                    <p class="text-center"><b>Архив снимков в формате DICOM</b></p>
                    <div class="btn-group btn-group-justified"><div class="btn-group"><a target="_blank" class="btn btn-default tooltip-enabled" data-toggle="tooltip" title="Скачать снимки в zip архиве" href="/download/d/' . $dicomArchive->id . '"><b class="text-success"><span class="glyphicon glyphicon-download-alt"></span> Скачать</b></a></div><div class="btn-group"><a target="_blank" class="btn btn-default tooltip-enabled"  data-toggle="tooltip" title="Просмотреть инструкции по просмотру изображений" href="/dicom/how-to"><b class="text-info"><span class="glyphicon glyphicon glyphicon-question-signe"></span> Как смотреть?</b></a></div><div class="btn-group"><button class="btn btn-default tooltip-enabled share-btn" data-type="execution" data-id="' . $dicomArchive->id . '"  data-toggle="tooltip" title="Поделиться ссылкой общего доступа на архив. Любой, у кого есть ссылка, сможет его скачать"><b class="text-info"><span class="glyphicon glyphicon-share-alt"></span> Поделиться</b></button></div></div>
                </div>
            </div>';
            }
            ?>
        </div>


        <?php

        echo "<a id='clearDataBtn' class='btn btn-default btn-block margin with-wrap' role='button'><span class='text-danger'><span class='glyphicon glyphicon-trash'></span> Удалить данные</span></a>";
        echo Html::beginForm(['/auth/logout'])
            . Html::submitButton(
                '<span class="text-warning"><span class="glyphicon glyphicon-log-out"></span> Выйти из учётной записи</span>',
                ['class' => 'btn btn-default btn btn-block margin with-wrap logout']
            )
            . Html::endForm();
        ?>
    </div>


    <div class="col-sm-12 col-md-6 col-md-offset-3">

        <?php
        echo "<div id='availabilityTimeContainer' class='alert alert-success text-center' style='display: none;'><span class='glyphicon glyphicon-info-sign'></span> Данные обследования будут доступны в течение<br/> <span id='availabilityTime'></span><br/><button id='lifetimeExtendActivator' class='btn btn-default'><span class='text-success'>Продлить время хранения</span></button></div>";
        echo "<div id='removeReasonContainer' class='alert alert-info text-center'><span class='glyphicon glyphicon-info-sign'></span> Ограничение доступа к данным исследования по времени необходимо в целях обеспечения безопасности Ваших персональных данных</div>";
        ?>
    </div>

    <div class="col-sm-12 col-md-6 col-md-offset-3 text-center margin">
        <div class="alert alert-success"><span class='glyphicon glyphicon-info-sign'></span> Если Вам необходима печать
            на
            заключение, обратитесь в центр, где Вы проходили
            исследование
        </div>
        <?php
        if (!Reviews::rateContains($patient)) {
            ?>
            <div class="panel panel-default rate-container">
                <div class="panel-heading">
                    <h3 class="panel-title">Тут вы можете оценить нашу работу</h3>
                </div>
                <div class="panel-body">
                    <div id="rateBlock" class="text-center">
                        <b>Поставить оценку</b>
                        <?php
                        $cookies = Yii::$app->request->cookies;
                        if (!$cookies->has("rate_received") || Reviews::haveNoRate($patient->username)) {
                            ?>
                            <div id="rateList">
                                <span class="glyphicon glyphicon-star-empty star" data-rate="1"></span>
                                <span class="glyphicon glyphicon-star-empty star" data-rate="2"></span>
                                <span class="glyphicon glyphicon-star-empty star" data-rate="3"></span>
                                <span class="glyphicon glyphicon-star-empty star" data-rate="4"></span>
                                <span class="glyphicon glyphicon-star-empty star" data-rate="5"></span>
                            </div>
                            <?php
                        }
                        if (!$cookies->has("reviewed") || Reviews::haveNoReview($patient->username)) {
                            ?>
                            <form id="reviewForm">
                                <div class="form-group">
                                    <label for="review">и/или написать отзыв</label>
                                    <textarea name="reviewArea" class="form-control" id="review" rows="3"></textarea>
                                </div>
                                <button class="btn btn-default"><span class="text-success">Отправить</span></button>
                            </form>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <a href="tel:+78312020200" class="btn btn-default margin"><span
                    class="glyphicon glyphicon-earphone text-success"></span><span
                    class="text-success"> +7(831)20-20-200</span></a><br/>
        <a target="_blank" href="https://мрт-кт.рф" class="btn btn-default"><span
                    class="glyphicon glyphicon-globe text-success"></span><span
                    class="text-success"> мрт-кт.рф</span></a>
    </div>

    <div class="col-sm-12 text-center">

        <a class="btn btn-primary" role="button" data-toggle="collapse" href="#collapseExample" aria-expanded="false"
           aria-controls="collapseExample">
            Нажмите, чтобы увидеть актуальные предложения
        </a>
        <div class="collapse" id="collapseExample">
            <div class="well">
                <a target="_blank" href="https://xn----ttbeqkc.xn--p1ai/nn/actions">
                    <img class="advice" alt="advice image" src="https://xn----ttbeqkc.xn--p1ai/actions.png"/>
                </a>
            </div>
        </div>
    </div>
<?php

if (Yii::$app->user->can("manage")) {
    $data = 'https://rdcnn.ru/enter/' . $patient->access_token;

// quick and simple:
    echo '<div class="text-center"><img src="' . (new QRCode)->render($data) . '" alt="QR Code" /></div>';

    // show previous executions list
    $previousExecutions = Archive_execution::getPreviousForUser($patient);
    if (!empty($previousExecutions)) {
        echo "<table class='table table-condensed'><thead><tr><th>Вид</th><th>Номер обследования</th><th>Дата</th><th>Зона</th><th>Действие</th></tr></thead><tbody>";
        foreach ($previousExecutions as $patient) {
            // check existing pdf conclusion file
            $fullInfo = Archive_complex_execution_info::findOne(['execution_identifier' => $patient->id]);
            if (!empty($patient->pdfPath)) {
                $pdfPath = Info::PDF_ARCHIVE_PATH . $patient->pdfPath;
                if ($fullInfo !== null && is_file($pdfPath)) {
                    echo "<tr><td>МРТ</td><td>$fullInfo->execution_number</td><td>$fullInfo->execution_date</td><td>$fullInfo->execution_area</td><td><a href='/archive-dl/$patient->id' target='_blank'>Скачать</a></td></tr>";
                }
            }
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='col-sm-4 offset-4'>Предыдущих обследований не найдено</div>";
    }
}




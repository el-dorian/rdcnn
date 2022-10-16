<?php

use yii\helpers\Html;
use yii\web\IdentityInterface;
use yii\web\View;



/* @var $this View */
/* @var $patient IdentityInterface */

$this->title = 'Обследование ' . $patient->username;

?>

<div id="ourLogo" class="visible-sm visible-md visible-lg "></div>
<div id="ourSmallLogo" class="visible-xs"></div>

<h1 class="text-center">Обследование № <?= $patient->username ?></h1>


<div class="panel panel-default col-sm-12 col-md-6 col-md-offset-3">
    <div class="panel-heading">
        <h3 class="panel-title">Закончилось время хранения данных на сервере</h3>
    </div>
    <div class="panel-body">
        <p class="text-center"><b>Данные хранятся на сервере в течение 5 дней после прохождения обследования. Чтобы вновь получить доступ к данным-обратитесь к нам.</b></p>
    </div>
</div>


<div class="col-sm-12 col-md-6 col-md-offset-3">
    <?php
    echo Html::beginForm(['/auth/logout'])
        . Html::submitButton(
            '<span class="text-warning"><span class="glyphicon glyphicon-log-out"></span> Выйти из учётной записи</span>',
            ['class' => 'btn btn-default btn btn-block margin with-wrap logout']
        )
        . Html::endForm();
    ?>
</div>

<div class="col-sm-12 col-md-6 col-md-offset-3 text-center margin">
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

<?php

namespace app\models\utils;

use app\models\database\Archive_complex_execution_info;
use app\models\database\Conclusion;
use app\models\selections\FoundConclusion;
use app\models\Telegram;
use yii\web\Request;

class ConclusionsSearch
{

    public function makeGlobalSearch(Request $request): array
    {
        // разберу параметры поиска
        $examinationId = $request->getBodyParam("executionId");
        $patientName = $request->getBodyParam("patientName");
        $patientBirthdate = $request->getBodyParam("patientBirthdate");
        $timePeriodStart = $request->getBodyParam("timePeriodStart");
        $timePeriodEnd = $request->getBodyParam("timePeriodEnd");
        $searchIn = $request->getBodyParam("searchIn");
        $diagnostician = $request->getBodyParam("diagnostician");
        $examinationArea = $request->getBodyParam("examinationArea");
        $center = $request->getBodyParam("center");
        $fulltextSearch = $request->getBodyParam("fulltextSearch");
        $list = [];
        if ($searchIn === 'Везде' || $searchIn === "В архиве") {
            $request = Archive_complex_execution_info::find()->limit(1000);
            /*if (!empty($examinationId)) {
                Telegram::sendDebug("search " . GrammarHandler::toLatin($examinationId));
                $request->andWhere(['execution_number' => GrammarHandler::toLatin($examinationId)]);
            }
            if (!empty($patientName)) {
                $request->andWhere(['like', 'patient_name', "%$patientName%", false]);
            }
            if (!empty($timePeriodStart) && !empty($timePeriodEnd)) {
                $request->andWhere(['>=', 'execution_date', $timePeriodStart]);
                $request->andWhere(['<=', 'execution_date', $timePeriodEnd]);
            } else if (!empty($timePeriodStart)) {
                $request->andWhere(['execution_date' => $timePeriodStart]);
            }
            if(!empty($patientBirthdate)){
                $request->andWhere(['patient_birthdate' => $patientBirthdate]);
            }*/
            if(!empty($diagnostician)){
                $request->andWhere(['doctor' => $diagnostician]);
            }
            $results = $request->all();
            Telegram::sendDebug(count($results));
            if (!empty($results)) {
                foreach ($results as $result) {
                    $conclusion = new FoundConclusion();
                    $conclusion->patientName = $result->patient_name;
                    $conclusion->examinationId = $result->execution_number;
                    $conclusion->conclusionId = $result->execution_identifier;
                    $conclusion->archive = true;
                    $conclusion->examinationArea = $result->execution_area;
                    $conclusion->diagnostician = $result->doctor;
                    $conclusion->executionDate = $result->execution_date;
                    $conclusion->contrastInfo = $result->contrast_info;
                    $conclusion->patientBirthdate = $result->patient_birthdate;
                    $conclusion->center = GrammarHandler::getCenter($conclusion->examinationId);
                    $list[] = $conclusion;
                }
            }
        }
        if ($searchIn === 'Везде' || $searchIn === "В ЛК") {
            $request = Conclusion::find()->limit(1000);
            if ($examinationId !== null) {
                $request->andWhere(['execution_number' => $examinationId]);
            }
            if ($patientName !== null) {
                $request->andWhere(['like', 'patient_name', "%$patientName%", false]);
            }
            $results = $request->all();
            if (!empty($results)) {
                foreach ($results as $result) {
                    $conclusion = new FoundConclusion();
                    $conclusion->patientName = $result->patient_name;
                    $conclusion->examinationId = $result->execution_number;
                    $conclusion->conclusionId = $result->id;
                    $conclusion->archive = false;
                    $conclusion->examinationArea = $result->execution_area;
                    $conclusion->diagnostician = $result->diagnostician;
                    $conclusion->executionDate = $result->execution_date;
                    $conclusion->contrastInfo = $result->contrast_info;
                    $conclusion->patientBirthdate = $result->patient_birthdate;
                    $conclusion->center = GrammarHandler::getCenter($conclusion->examinationId);
                    $list[] = $conclusion;
                }
            }
        }
        return ['status' => true, 'list' => $list];
    }
}
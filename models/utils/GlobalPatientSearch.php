<?php


namespace app\models\utils;


use app\models\database\Archive_complex_execution_info;
use app\models\database\Conclusion;
use app\models\selections\FoundPatient;
use app\models\selections\SearchResult;
use app\models\Table_availability;
use app\models\User;
use app\priv\Info;
use DateTime;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use yii\base\Model;

class GlobalPatientSearch extends Model
{
    public ?string $executionNumber = null;
    public ?string $patientPersonals = null;
    public ?string $executionDateStart = null;
    public ?string $executionDateFinish = null;
    public int $sortBy = 0;
    public int $center = 0;
    public ?int $page = 1;
    public int $totalResults = 0;


    #[ArrayShape(['executionNumber' => "string", 'patientPersonals' => "string", 'executionDateStart' => "string", 'executionDateFinish' => "string", 'center' => "string"])] public function attributeLabels(): array
    {
        return [
            'executionNumber' => 'Номер обследования',
            'patientPersonals' => 'ФИО пациента',
            'executionDateStart' => 'Дата обследования (с)',
            'executionDateFinish' => 'Дата обследования (по)',
            'center' => 'Центр',
        ];
    }

    public function rules(): array
    {
        return [
            [['executionNumber', 'patientPersonals', 'executionDateStart', 'executionDateFinish', 'page', 'sortBy', 'center'], 'safe'],
            [['executionDateStart', 'executionDateFinish'], 'date', 'format' => 'y-M-d'],
        ];
    }

    /**
     * @throws Exception
     */
    public function makeSearch(): array
    {
        $answer = [];
        $query = Conclusion::find();
        // first, search in conclusions
        if (!empty($this->patientPersonals)) {
            $query->where(['like', 'patient_name', "%$this->patientPersonals%", false]);
            // add execution number if exists
        }
        if (!empty($this->executionNumber)) {
            $query->andWhere(['execution_number' => $this->executionNumber]);
        }
        if (!empty($this->executionDateStart) && !empty($this->executionDateFinish)) {
            $query->andWhere([">=", 'execution_date', $this->executionDateStart]);
            $query->andWhere(["<=", 'execution_date', $this->executionDateFinish]);
        } else if (!empty($this->executionDateStart)) {
            $query->andWhere(['execution_date' => $this->executionDateStart]);
        }
        // center
        if ($this->center > 0) {
            switch ($this->center) {
                case 1:
                    $query->andWhere(['like', 'execution_number', "A%", false]);
                    break;
                case 2:
                    $query->andWhere(['not like', 'execution_number', "A%", false]);
                    $query->andWhere(['not like', 'execution_number', "T%", false]);
                    break;
                case 3:
                    $query->andWhere(['like', 'execution_number', "T%", false]);
                    break;
            }
        }
        $count = $query->count();
        $this->totalResults += $count;

        switch ($this->sortBy) {
            case 0:
                $query->orderBy('execution_date');
                break;
            case 1:
                $query->orderBy('execution_date DESC');
                break;
            case 2:
                $query->orderBy('patient_name');
                break;
            case 3:
                $query->orderBy('patient_name DESC');
                break;
            case 4:
                $query->orderBy('execution_number');
                break;
            case 5:
                $query->orderBy('execution_number DESC');
                break;
            case 6:
                $query->orderBy('diagnostician');
                break;
            case 7:
                $query->orderBy('diagnostician DESC');
                break;
            case 8:
                $query->orderBy('contrast_info');
                break;
            case 9:
                $query->orderBy('contrast_info DESC');
                break;
            case 10:
                $query->orderBy('execution_area');
                break;
            case 11:
                $query->orderBy('execution_area DESC');
                break;
        }
        $query
            ->limit(50)
            ->offset($this->page - 1 * 50);
        $results = $query->all();
        if (!empty($results)) {
            foreach ($results as $item) {
                $patient = new FoundPatient();
                $patient->executionId = $item->execution_number;
                $patient->patientName = $item->patient_name;
                $patient->executionDate = $item->execution_date;
                $patient->patientBirthdate = $item->patient_birthdate;
                $patient->conclusionId = $item->id;
                $patient->executionArea = $item->execution_area;
                $patient->contrastAgent = $item->contrast_info;
                $patient->diagnostician = $item->diagnostician;
                // check modality
                if (str_starts_with($item->execution_number, 'A')) {
                    $patient->modality = "Аврора";
                } else if (str_starts_with($item->execution_number, 'T')) {
                    $patient->modality = "КТ";
                } else {
                    $patient->modality = "НВН";
                }
                $user = User::findByUsername($item->execution_number);
                if ($user !== null) {
                    $patient->state = $user->status;
                }
                $answer[] = $patient;
            }
        }
        // if there is less than maximum results-find in archive
        if (count($answer) < 50) {
            $archiveRequest = Archive_complex_execution_info::find();
            if (!empty($this->patientPersonals)) {
                $archiveRequest->where(['like', 'patient_name', "%$this->patientPersonals%", false]);
            }
            if (!empty($this->executionNumber)) {
                $archiveRequest->andWhere(['execution_number' => $this->executionNumber]);
            }

            if (!empty($this->executionDateStart) && !empty($this->executionDateFinish)) {
                $archiveRequest->andWhere([">=", 'execution_date', $this->executionDateStart]);
                $archiveRequest->andWhere(["<=", 'execution_date', $this->executionDateFinish]);
            } else if (!empty($this->executionDateStart)) {
                $archiveRequest->andWhere(['execution_date' => $this->executionDateStart]);
            }

            if ($this->center > 0) {
                switch ($this->center) {
                    case 1:
                        $archiveRequest->andWhere(['like', 'execution_number', "A%", false]);
                        break;
                    case 2:
                        $archiveRequest->andWhere(['not like', 'execution_number', "A%", false]);
                        $archiveRequest->andWhere(['not like', 'execution_number', "T%", false]);
                        break;
                    case 3:
                        $archiveRequest->andWhere(['like', 'execution_number', "T%", false]);
                        break;
                }
            }
            $offset = (($this->page - 1) * 50) - count($answer);
            if ($offset < 0) {
                $offset = 0;
            }
            $count = $archiveRequest->count();
            $this->totalResults += $count;
            $archiveRequest
                ->limit(50 - count($answer))
                ->offset($offset);

            switch ($this->sortBy) {
                case 0:
                    $archiveRequest->orderBy('execution_date');
                    break;
                case 1:
                    $archiveRequest->orderBy('execution_date DESC');
                    break;
                case 2:
                    $archiveRequest->orderBy('patient_name');
                    break;
                case 3:
                    $archiveRequest->orderBy('patient_name DESC');
                    break;
                case 4:
                    $archiveRequest->orderBy('execution_number');
                    break;
                case 5:
                    $archiveRequest->orderBy('execution_number DESC');
                    break;
                case 6:
                    $archiveRequest->orderBy('doctor');
                    break;
                case 7:
                    $archiveRequest->orderBy('doctor DESC');
                    break;
                case 8:
                    $archiveRequest->orderBy('contrast_info');
                    break;
                case 9:
                    $archiveRequest->orderBy('contrast_info DESC');
                    break;
                case 10:
                    $archiveRequest->orderBy('execution_area');
                    break;
                case 11:
                    $archiveRequest->orderBy('execution_area DESC');
                    break;
            }

            $results = $archiveRequest->all();
            if (!empty($results)) {
                foreach ($results as $item) {
                    $patient = new FoundPatient();
                    $patient->executionId = $item->execution_number;
                    $patient->patientName = $item->patient_name;
                    $patient->executionDate = $item->execution_date;
                    $patient->patientBirthdate = $item->patient_birthdate;
                    $patient->executionArea = $item->execution_area;
                    $patient->conclusionId = $item->execution_identifier;
                    $patient->contrastAgent = $item->contrast_info;
                    $patient->diagnostician = $item->doctor;
                    // check modality
                    if (str_starts_with($item->execution_number, 'A')) {
                        $patient->modality = "Аврора";
                    } else if (str_starts_with($item->execution_number, 'T')) {
                        $patient->modality = "КТ";
                    } else {
                        $patient->modality = "НВН";
                    }
                    $patient->state = 3;
                    $answer[] = $patient;
                }
            }
        }
        return $answer;
    }

    public function getPage()
    {
        return $this->page;
    }
}
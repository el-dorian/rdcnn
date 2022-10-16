<?php

namespace app\models\selections;

class FoundConclusion
{
    public string $examinationId = '';
    public string $conclusionId = '';
    public ?string $patientName = null;
    public ?string $patientBirthdate = null;
    public ?string $executionDate = null;
    public ?string $examinationArea = null;
    public ?string $contrastInfo = null;
    public ?string $diagnostician = null;
    public string $center = '';
    public bool $archive = false;
}
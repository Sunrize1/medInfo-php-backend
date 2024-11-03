<?php
class DiagnosisService {
    private $model;


    public function __construct($model) {
        $this->model = $model;
    }


    public function createDiagnosis($data, $inspectionId) {
        $requiredFields = ['description', 'type', 'icd_10_id'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        return $this->model->create($data, $inspectionId);
    }


    public function getMainDiagnosisByInspectionId($patientId) {
        return $this->model->getMainDiagnosis($patientId);
    }
}
<?php
class DiagnosisService {
    private $model;


    public function __construct($model) {
        $this->model = $model;
    }


    public function createDiagnoses($data) {
        $requiredFields = ['description', 'type', 'icd_10_id'];
        foreach($data['diagnoses'] as $diagnosis) {
        foreach ($requiredFields as $field) {
            if (!isset($diagnosis[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        }

        $ids = [];
        foreach ($data['diagnoses'] as $diagnosis) {
            $ids[] = $this->model->create($diagnosis, $data['inspection_id']);
        }
        return $ids;
    }


    public function getMainDiagnosisByInspectionId($patientId) {
        return $this->model->getMainDiagnosis($patientId);
    }

    public function updateDiagnoses($data) {
        $ids = [];
        foreach($data as $newDiagnosis) {
            $oldDiagnosis = $this->model->getById($newDiagnosis['id']);
        if(!$oldDiagnosis) {
            throw new Exception("diagnosis not found");
        }

        $requiredFields = ['description', 'type', 'icd_10_id'];
        foreach ($requiredFields as $field) {
            if (!isset($newDiagnosis[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

         $ids[] = $this->model->update($newDiagnosis);
        }
        return $ids;
    }

}
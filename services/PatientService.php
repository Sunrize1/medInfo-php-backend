<?php
require_once 'auth/JWTHandler.php';

class PatientService {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function createPatient($data) {
        $requiredFields = ['name', 'birthday', 'gender'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        return $this->model->create($data);
    }

    public function getAllPatients() {
        return $this->model->get();
    }

    public function getPatientById($id) {
        $patient = $this->model->getById($id);
        if (!$patient) {
            throw new Exception('Patient not found');
        }
        return $patient;
    }
}
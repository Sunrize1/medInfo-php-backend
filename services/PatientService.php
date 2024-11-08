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
                throw new Exception("Missing required field: $field", 400);
            }
        }

        return $this->model->create($data);
    }

    public function getAllPatients(
        $name,
        $conclusion,
        $sorting,
        $scheduledVisits,
        $onlyMine,
        $page,
        $size,
        $doctorId) {

        $offset = ($page - 1) * $size;
        $patients = $this->model->getAll(
            $name,
            $conclusion,
            $sorting,
            $scheduledVisits,
            $onlyMine,
            $page,
            $offset,
            $size,
            $doctorId
        );

        $totalCount = $this->model->getPatientsCount(
            $name,
            $conclusion,
            $scheduledVisits,
            $onlyMine,
            $doctorId
        );

        $pagination = [
            'size' => $size,
            'count' => ceil($totalCount / $size),
            'current' => $page
        ];

        return ['patients' => $patients, 'pagination' => $pagination];
    }

    public function getPatientById($id) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $patient = $this->model->getById($id);
        if (!$patient) {
            throw new Exception('Patient not found', 404);
        }
        return $patient;
    }
}
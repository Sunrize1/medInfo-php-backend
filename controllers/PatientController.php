<?php
require_once 'auth/JWTHandler.php';
require_once 'auth/authMiddleware.php';

class PatientController {
    private $model;
    private $pdo;


    public function __construct($model, $pdo) {
        $this->model = $model;
        $this->pdo = $pdo;
    }


    public function createPatient() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $requiredFields = ['name', 'birthday', 'gender'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        $newPatientId = $this->model->create($data);

        if ($newPatientId) {
            http_response_code(201);
            echo json_encode(['message' => 'Patient created successfully', 'id' => $newPatientId]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create patient']);
        }
    }


    public function getAllPatients() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $patients = $this->model->get();

        if($patients) {
            http_response_code(200);
            echo json_encode($patients);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get patients']);
        }
    }


    public function getPatientById($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $patient = $this->model->getById($id);

        if($patient) {
            http_response_code(200);
            echo json_encode($patient);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'patient not found']);
        }
    }
}
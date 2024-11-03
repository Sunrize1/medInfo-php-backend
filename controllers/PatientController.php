<?php
require_once 'auth/authMiddleware.php';
require_once 'services/PatientService.php';

class PatientController {
    private $patientService;
    private $inspectionService;
    private $pdo;


    public function __construct($patientService, $inspectionService, $pdo) {
        $this->patientService = $patientService;
        $this->inspectionService = $inspectionService;
        $this->pdo = $pdo;
    }


    public function createPatient() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $newPatientId = $this->patientService->createPatient($data);
            http_response_code(201);
            echo json_encode(['message' => 'Patient created successfully', 'id' => $newPatientId]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function getAllPatients() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        try {
            $patients = $this->patientService->getAllPatients();
            http_response_code(200);
            echo json_encode($patients);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function getPatientById($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        try {
            $patient = $this->patientService->getPatientById($id);
            http_response_code(200);
            echo json_encode($patient);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function createInspectionForPatient($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $newInspectionId= $this->inspectionService->createInspection($data, $id, $headers);
            http_response_code(201);
            echo json_encode(['message' => 'inspection created successfully', 'id' => $newInspectionId]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function getAllInspectionsOfPatient($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        try {
            $inspections = $this->inspectionService->getAllinspections($id);
            http_response_code(200);
            echo json_encode($inspections);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
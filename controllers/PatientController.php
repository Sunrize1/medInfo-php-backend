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
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function getAllPatients(
        $name,
        $conclusion,
        $sorting,
        $scheduledVisits,
        $onlyMine,
        $page,
        $size) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $doctorId = getDoctorIdByToken($headers);

        try {
            $patients = $this->patientService->getAllPatients(
                $name,
                $conclusion,
                $sorting,
                $scheduledVisits,
                $onlyMine,
                $page,
                $size,
                $doctorId);
            http_response_code(200);
            echo json_encode($patients);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
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
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function createInspectionForPatient($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $doctorId = getDoctorIdByToken($headers);

        $patient = $this->patientService->getPatientById($id);
        if(!$patient) {
            http_response_code(404);
            echo json_encode("patient not found");
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $response = $this->inspectionService->createInspection($data, $id, $doctorId);
            http_response_code(201);
            echo json_encode(['message' => 'inspection created successfully', 'id' => $response]);
            return $response;
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }


    public function getAllInspectionsOfPatient($id, $size, $page, $grouped) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $patient = $this->patientService->getPatientById($id);
        if(!$patient) {
            http_response_code(404);
            echo json_encode("patient not found");
            return;
        }

        try {
            $inspections = $this->inspectionService->getAllinspectionsOfPatient($id, $size, $page, $grouped);
            http_response_code(200);
            echo json_encode($inspections);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function searchInspectionsByDiagnosis($id, $request) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $patient = $this->patientService->getPatientById($id);
        if(!$patient) {
            http_response_code(404);
            echo json_encode("patient not found");
            return;
        }

        try {
            $inspections = $this->inspectionService->searchInspectionsByDiagnosis($id, $request);
            http_response_code(200);
            echo json_encode($inspections);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

}
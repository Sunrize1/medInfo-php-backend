<?php
require_once 'auth/authMiddleware.php';

class DoctorController {
    private $service;
    private $pdo;

    public function __construct($service, $pdo) {
        $this->service = $service;
        $this->pdo = $pdo;
    }

    public function loginDoctor() {
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $token = $this->service->loginDoctorService($data);
            http_response_code(200);
            echo json_encode(['token' => $token]);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function logoutDoctor() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        try {
            $this->service->logoutDoctor($headers);
            http_response_code(200);
            echo json_encode(['message' => 'Logout successful']);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function registerDoctor() {
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $newDoctorId = $this->service->registerDoctor($data);
            http_response_code(201);
            echo json_encode(['message' => 'Doctor registered successfully', 'doctor_id' => $newDoctorId]);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getCurrentDoctor() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $doctorId = getDoctorIdByToken($headers);

        try {
            $doctorInfo = $this->service->getCurrentDoctor($doctorId);
            http_response_code(200);
            echo json_encode($doctorInfo);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateDoctor() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $doctorId = getDoctorIdByToken($headers);

        try {
            $result = $this->service->updateDoctor($data, $doctorId);
            http_response_code(200);
            echo json_encode(['message' => 'Doctor updated successfully']);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
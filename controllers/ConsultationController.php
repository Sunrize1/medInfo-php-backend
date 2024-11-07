<?php
class ConsultationController {
    private $pdo;
    private $service;

    public function __construct($pdo, $service) {
        $this->pdo = $pdo;
        $this->service = $service;
    }


    public function createCommentForConsultation($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $doctorId = getDoctorIdByToken($headers);

        try {
            $result = $this->service->createCommentForConsultation($data, $id, $doctorId);
            http_response_code(200);
            echo json_encode(['message' => 'Comment created succesfuly', 'id' => $result]);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getConsultationById($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        try {
            $result = $this->service->getConsultationById($id);
            http_response_code(200);
            echo json_encode($result);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function updateComment($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $doctorId = getDoctorIdByToken($headers);
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $result = $this->service->updateComment($data, $id, $doctorId);
            http_response_code(200);
            echo json_encode(['message' => 'Success', 'id' => $result]);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getInspectionsWithConsultations() {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $doctorId = getDoctorIdByToken($headers);

        try {
            $results = $this->service->getInspectionsWithConsultations($doctorId);
            http_response_code(200);
            echo json_encode($results);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
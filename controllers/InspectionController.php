<?php
class InspectionController {
    private $pdo;
    private $service;


    public function __construct($pdo, $service) {
        $this->pdo = $pdo;
        $this->service = $service;
    }


    public function getInspectionById($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }
        
        try {
            $inspection = $this->service->getInspectionById($id);
            http_response_code(200);
            echo json_encode($inspection);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);;
        }

    }


    public function getInspectionChain($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }
        
        try {
            $chain = $this->service->getInspectionChain($id);
            http_response_code(200);
            echo json_encode($chain);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);;
        }
    }


    public function updateInspection($id) {
        $headers = apache_request_headers();
        if (!authMiddleware($headers, $this->pdo)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $response = $this->service->updateInspection($data, $id);
            http_response_code(200);
            echo json_encode(['message' => "updated succesfuly", 'id' => $response]);
        } catch (Exception $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : 500;
            http_response_code($errorCode);
            echo json_encode(['error' => $e->getMessage()]);;
        }
    }
}
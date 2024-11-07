<?php
class DictionaryController {
    private $service;

    public function __construct($service) {
        $this->service = $service;
    }


    public function getSpecialtiesList($name, $page, $size) {
        try {
            $response = $this->service->getSpecialtiesList($name, $page, $size);
            http_response_code(200);
            echo json_encode($response);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getIcd10List($request, $page, $size) {
        try {
            $response = $this->service->getIcd10List($request, $page, $size);
            http_response_code(200);
            echo json_encode($response);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getIcd10roots() {
        try {
            $response = $this->service->getIcd10Roots();
            http_response_code(200);
            echo json_encode($response);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
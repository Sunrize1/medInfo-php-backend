<?php
require_once 'auth/JWTHandler.php';
class InspectionService {
    private $model;
    private $diagnosisService;


    public function __construct($model, $diagnosisService) {
        $this->model = $model;
        $this->diagnosisService = $diagnosisService;
    }


    public function createInspection($data, $patientId, $headers) {
        $authHeader = $headers['Authorization'];
        preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
        $token = $matches[1];

        $jwtHandler = new JwtHandler();
        $decoded = $jwtHandler->jwtDecodeData($token);

        $doctorId = $decoded['data']->doctor_id;
        $data['patient_id'] = $patientId;
        $data['doctor_id'] = $doctorId;
        

        $requiredFields = ['doctor_id', 'date', 'anamnesis', 'complaints', 'treatment', 'conclusion', 'diagnoses'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }   

        $hasMainDiagnosis = false;
        foreach ($data['diagnoses'] as $diagnosis) {
            if ($diagnosis['type'] === 'Main') {
                $hasMainDiagnosis = true;
                break;
            }
        }

        if (!$hasMainDiagnosis) {
            throw new Exception("Only one diagnosis with type 'Main' is required.");
        }
        
        $diagnoses = $data['diagnoses'];
        unset($data['diagnoses']);
        
        if (!isset($data['nextvisitdate'])) {
            $data['nextvisitdate'] = null;
        }

        if (!isset($data['deathdate'])) {
            $data['deathdate'] = null;
        }

        if (!isset($data['previousinspectionid'])) {
            $data['previousinspectionid'] = null;
        } else {
            $inspection = $this->model->getById($data['previousinspectionid']);
            if(!$inspection){
                throw new Exception("inspection doesn't exists");
            }
        }
        

        $inspectionId = $this->model->create($data);
        foreach ($diagnoses as $diagnosis) {
            $this->diagnosisService->createDiagnosis($diagnosis, $inspectionId);
        }
        return $inspectionId;
    }


    public function getAllInspections($patientId) {
        $results = $this->model->getAll($patientId);

        $inspections = [];
        foreach($results as $result) {
            $diagnosis = $this->diagnosisService->getMainDiagnosisByInspectionId($result['id']);

            $inspection = [
            'id' => $result['id'],
            'createTime' => $result['createtime'],
            'previousId' => $result['previousinspectionid'],
            'date' => $result['date'],
            'conclusion' => $result['conclusion'],
            'doctorId' => $result['doctor_id'],
            'patientId' => $result['patient_id'],
            'diagnosis' => null
            ];

            $inspection['diagnosis'] = $diagnosis;
            $inspections[] = $inspection;
        }

        return [
        'inspections' => $inspections
        ];
    }


    public function getInspectionById($id) {
        $inspection= $this->model->getById($id);

        if(!$inspection) {
            throw new Exception('inspection not found');
        }

        return $inspection;
    }

}
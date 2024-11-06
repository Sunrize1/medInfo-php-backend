<?php
require_once 'auth/JWTHandler.php';
class InspectionService {
    private $model;
    private $diagnosisService;
    private $patientService;
    private $doctorService;


    public function __construct($model) {
        $this->model = $model;
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
            if(!$inspection || $inspection['conclusion'] === "Death"){
                throw new Exception("inspection doesn't exists or patient is dead");
            }
        }
        
        $inspectionId = $this->model->create($data);
        $response = ['diagnoses' => $diagnoses, 'inspection_id' => $inspectionId];
        return $response;
    }


    public function getAllInspections($patientId) {
        $results = $this->model->getAll($patientId);

        $inspections = [];
        foreach ($results as $row) {
            $inspection = [
                'id' => $row['id'],
                'createTime' => $row['createtime'],
                'previousId' => $row['previousinspectionid'],
                'date' => $row['date'],
                'conclusion' => $row['conclusion'],
                'doctorId' => $row['doctor_id'],
                'doctor' => $row['doctor_name'],
                'patientId' => $row['patient_id'],
                'patient' => $row['patient_name'],
                'diagnosis' => null,
                'hasChain' => false,
                'hasNested' => false
            ];

            $inspection['diagnosis'] = [
                    'id' => $row['diagnosis_id'],
                    'createTime' => $row['diagnosis_createtime'], 
                    'code' => $row['diagnosis_code'],
                    'name' => $row['diagnosis_name'],
                    'description' => $row['diagnosis_description'], 
                    'type' => $row['diagnosis_type']
                ];

            if ($row['previousinspectionid'] !== null) {
                $inspection['hasNested'] = true;
            }

            $chain = $this->model->getInspectionChain($row['id']);
            if ($chain) {
                $inspection['hasChain'] = true;
            }

            $inspections[] = $inspection;
        }

        return ['inspections' => $inspections];
        
    }

    public function getInspectionChain($id) {
       $results = $this->model->getInspectionChain($id);

       if($results) {
        $chain = [];
        foreach ($results as $row) {
                $inspection = [
                    'id' => $row['id'],
                    'createTime' => $row['createtime'],
                    'previousId' => $row['previousinspectionid'],
                    'date' => $row['date'],
                    'conclusion' => $row['conclusion'],
                    'doctorId' => $row['doctor_id'],
                    'doctor' => $row['doctor_name'],
                    'patientId' => $row['patient_id'],
                    'patient' => $row['patient_name'],
                    'diagnosis' => $this->getInspectionDiagnoses($row['id']),
                    'hasChain' => false,
                    'hasNested' => true
                ];


                $subChain = $this->model->getInspectionChain($row['id']);
                if ($subChain) {
                    $inspection['hasChain'] = true;
                }

                $chain[] = $inspection;
            }

            return $chain;
       } else {
            throw new Exception("chain not found");
        }
    }


    public function getInspectionById($id) {
        $results = $this->model->getById($id);

        if($results) {
           $inspection = [
            'id' => $results[0]['id'],
            'createTime' => $results[0]['createtime'],
            'date' => $results[0]['date'],
            'anamnesis' => $results[0]['anamnesis'],
            'complaints' => $results[0]['complaints'],
            'treatment' => $results[0]['treatment'],
            'conclusion' => $results[0]['conclusion'],
            'nextVisitDate' => $results[0]['nextvisitdate'],
            'deathDate' => $results[0]['deathdate'],
            'previousInspectionId' => $results[0]['previousinspectionid'],
            'patient' => [
                'id' => $results[0]['patient_id'],
                'createTime' => $results[0]['patient_createtime'],
                'name' => $results[0]['patient_name'],
                'birthday' => $results[0]['patient_birthday'],
                'gender' => $results[0]['patient_gender']
            ],
            'doctor' => [
                'id' => $results[0]['doctor_id'],
                'createTime' => $results[0]['doctor_createtime'],
                'name' => $results[0]['doctor_name'],
                'birthday' => $results[0]['doctor_birthday'],
                'gender' => $results[0]['doctor_gender'],
                'email' => $results[0]['doctor_email'],
                'phone' => $results[0]['doctor_phone']
            ],
            'diagnoses' => $this->getInspectionDiagnoses($results[0]['id'])
        ]; 

        $baseInspectionId = $this->model->getBaseInspectionId($results[0]['id']);
        $inspection['baseInspectionId'] = $baseInspectionId;

        return $inspection;
        } else {
            throw new Exception("Inspection not found");
        }
    }

    
    public function searchInspectionsByDiagnosis($id, $request) {
        $results = $this->model->getInspectionsByDiagnosis($request, $id);

        $inspections = [];
        foreach ($results as $row) {
            $inspection = [
                'id' => $row['id'],
                'createTime' => $row['createtime'],
                'date' => $row['date'],
                'diagnosis' => [
                    'id' => $row['diagnosis_id'],
                    'createTime' => $row['diagnosis_createtime'],
                    'code' => $row['diagnosis_code'],
                    'name' => $row['diagnosis_name'],
                    'description' => $row['diagnosis_description'],
                    'type' => $row['diagnosis_type']
                ]
            ];

            $inspections[] = $inspection;
        }

        return $inspections;
    }


    public function getInspectionDiagnoses($id) {
        $results = $this->model->getInspectionDiagnoses($id);

        $diagnoses = [];
        foreach ($results as $row) {
            $diagnoses[] = [
                'id' => $row['diagnosis_id'],
                'createTime' => $row['diagnosis_createtime'],
                'code' => $row['diagnosis_code'],
                'name' => $row['diagnosis_name'],
                'description' => $row['diagnosis_description'],
                'type' => $row['diagnosis_type']
            ];
        }

        return $diagnoses;
    }


    public function updateInspection($data, $id) {
    
        $mainDiagnosis;
        $diagnosesForUpdate = [];
        $diagnosesForCreate = [];

        foreach ($data['diagnoses'] as $diagnosis) {
            if ($diagnosis['type'] === 'Main') {
                $mainDiagnosis = $diagnosis;
            } else {
                $diagnosesForCreate[] = $diagnosis;
            }
        }


        if (!$mainDiagnosis) {
            throw new InvalidArgumentException("required at least 1 diagnosis with type -'Main'");
        }

        $requiredFields = [ 'anamnesis', 'complaints', 'treatment', 'conclusion', 'diagnoses'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        } 

        if (!isset($data['nextvisitdate'])) {
            $data['nextvisitdate'] = null;
        }

        if (!isset($data['deathdate'])) {
            $data['deathdate'] = null;
        }

        unset($data['diagnoses']);
        $existingDiagnoses = $this->getInspectionDiagnoses($id);
        foreach ($existingDiagnoses as $key => $existingDiagnosis) {
            if($existingDiagnosis['type'] === "Main") {
                $mainDiagnosis['id'] = $existingDiagnosis['id'];
                unset($existingDiagnoses[$key]);
                break;
            }
        }

        if($existingDiagnoses) {
            foreach ($existingDiagnoses as $existingDiagnosis) {
            $found = false;
            foreach ($diagnosesForCreate as $key => $newDiagnosis) {
                if ($newDiagnosis['type'] === $existingDiagnosis['type']) {
                    $diagnosesForUpdate[] = array_merge($newDiagnosis, ['id' => $existingDiagnosis['id']]);
                    unset($diagnosesForCreate[$key]);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $diagnosesForUpdate[] = $existingDiagnosis;
            }
        }
        }

        $diagnosesForUpdate[] = $mainDiagnosis;

    
        $this->model->update($id, $data);
        $response = [];
        $response['diagnosesForUpdate'] = $diagnosesForUpdate;
        $response['diagnosesForCreate'] = ['diagnoses' => $diagnosesForCreate, 'inspection_id' => $id];
        return $response;
    }

}

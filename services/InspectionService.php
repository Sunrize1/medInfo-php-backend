<?php
require_once 'auth/JWTHandler.php';
class InspectionService {
    private $model;
    private $consultationService;

    public function __construct($model, $consultationService) {
        $this->model = $model;
        $this->consultationService = $consultationService;
    }


    public function createInspection($data, $patientId, $doctorId) {
        if(!UUIDValidator::isValid($patientId)) {
         throw new Exception('invalid id format', 400) ;  
        }
        
        $data['patient_id'] = $patientId;
        $data['doctor_id'] = $doctorId;
        

        $requiredFieldsForInspection = ['doctor_id', 'date', 'anamnesis', 'complaints', 'treatment', 'conclusion', 'diagnoses'];
        foreach ($requiredFieldsForInspection as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }   

        $this->validateDiagnosesForInspection($data['diagnoses']);
        $diagnoses = $data['diagnoses'];
        unset($data['diagnoses']);

        $consultations = [];
        if(isset($data['consultations'])) {
            $this->validateConsultationsForInspection($data['consultations']);
            $this->validateCommentsForConsultation($data['consultations']);
            $consultations = $data['consultations'];
            unset($data['consultations']);
        }
        
        if (!isset($data['nextvisitdate'])) {
            $data['nextvisitdate'] = null;
        }

        if (!isset($data['deathdate'])) {
            $data['deathdate'] = null;
        }

        if (!isset($data['previousinspectionid'])) {
            $data['previousinspectionid'] = null;
        } else {
            if(!UUIDValidator::isValid($data['previousinspectionid'])) {
                throw new Exception('invalid id format', 400) ;  
            }

            $inspection = $this->model->getById($data['previousinspectionid']);
            if(!$inspection){
                throw new Exception("inspection doesn't exists", 404);
            }
            if($inspection['patient_id'] !== $patientId) {
                throw new Exception("previous inspection patient is different", 400);
            }
        }
        
        $inspectionId = $this->model->create($data);
        $this->createDiagnoses($diagnoses, $inspectionId);
        $this->consultationService->createConsultations($consultations, $inspectionId, $doctorId);
        return $inspectionId;
    }


    public function getAllInspectionsOfPatient($patientId, $size, $page, $grouped) {
        if(!UUIDValidator::isValid($patientId)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $offset = ($page - 1) * $size;

        $results = $this->model->getAll($patientId, $size, $offset, $grouped);

        $inspections = [];
        if(!$results) return $inspections;
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
                'hasChain' => $row['has_chain'],
                'hasNested' => $row['has_nested']
            ];

            $inspection['diagnosis'] = $this->model->getMainDiagnosis($row['id']);

            $inspections[] = $inspection;
        }
        $totalCount = $this->model->getInspectionsCount($patientId);
        $pagination = ['size' => $size, 'count' => ceil($totalCount / $size), 'current' => $page];

        return ['inspections' => $inspections, 'pagination' => $pagination];
        
    }

    public function getInspectionChain($id) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $results = $this->model->getInspectionChain($id);
        $chain = [];
        
        if($results) return $chain;

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
                'hasChain' => $row['has_chain'],
                'hasNested' => true
            ];

            $chain[] = $inspection;
        }

        return $chain;
    }


    public function getInspectionById($id) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $result = $this->model->getById($id);

        if($result) {
           $inspection = [
            'id' => $result['id'],
            'createTime' => $result['createtime'],
            'date' => $result['date'],
            'anamnesis' => $result['anamnesis'],
            'complaints' => $result['complaints'],
            'treatment' => $result['treatment'],
            'conclusion' => $result['conclusion'],
            'nextVisitDate' => $result['nextvisitdate'],
            'deathDate' => $result['deathdate'],
            'previousInspectionId' => $result['previousinspectionid'],
            'patient' => [
                'id' => $result['patient_id'],
                'createTime' => $result['patient_createtime'],
                'name' => $result['patient_name'],
                'birthday' => $result['patient_birthday'],
                'gender' => $result['patient_gender']
            ],
            'doctor' => [
                'id' => $result['doctor_id'],
                'createTime' => $result['doctor_createtime'],
                'name' => $result['doctor_name'],
                'birthday' => $result['doctor_birthday'],
                'gender' => $result['doctor_gender'],
                'email' => $result['doctor_email'],
                'phone' => $result['doctor_phone']
            ],
            'diagnoses' => $this->getInspectionDiagnoses($result['id']),
            'consultations' => $this->getInspectionConsultations($result['id']),
            'baseInspectionId' => $this->model->getBaseInspectionId($result['id'])
        ]; 

        return $inspection;
        } else {
            throw new Exception("Inspection not found", 404);
        }
    }

    
    public function searchInspectionsByDiagnosis($id, $request) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

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

    public function getInspectionConsultations($id) {
        $results = $this->model->getInspectionConsultations($id);

        $consultations = [];

        if(!$results) return $consultations;
        foreach ($results as $row) {
            $consultation = [
                'id' => $row['consultation_id'],
                'createTime' => $row['consultation_createtime'],
                'inspectionId' => $row['inspection_id'],
                'speciality' => [
                    'id' => $row['speciality_id'],
                    'createTime' => $row['speciality_createtime'],
                    'name' => $row['speciality_name']
                ],
                'rootComment' => [
                    'id' => $row['root_comment_id'],
                    'createTime' => $row['root_comment_createtime'],
                    'parentId' => $row['parent_id'],
                    'content' => $row['root_comment_content'],
                    'author' => [
                        'id' => $row['author_id'],
                        'createTime' => $row['author_createtime'],
                        'name' => $row['author_name'],
                        'birthday' => $row['birthday'],
                        'gender' => $row['gender'],
                        'email' => $row['email'],
                        'phone' => $row['phone']
                    ],
                    'modifyTime' => $row['modified_date']
                ]
            ];
            $consultations[] = $consultation;
        }

        return $consultations;
    }


    public function updateInspection($data, $id) {
        if(!UUIDValidator::isValid($id)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $inspection = $this->model->getById($id);

        if(!$inspection){
            throw new Exception("inspection not found", 404);
        }
    
        $mainDiagnosis;
        $diagnosesForCreate = [];
        $diagnosesForUpdate = [];

        foreach ($data['diagnoses'] as $diagnosis) {
            if ($diagnosis['type'] === 'Main') {
                $mainDiagnosis = $diagnosis;
            } else {
                $diagnosesForCreate[] = $diagnosis;
            }
        }


        if (!$mainDiagnosis) {
            throw new Exception("required at least 1 diagnosis with type -'Main'", 400);
        }

        $requiredFields = [ 'anamnesis', 'complaints', 'treatment', 'conclusion', 'diagnoses'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        } 

        if (!isset($data['nextvisitdate'])) {
            $data['nextvisitdate'] = null;
        } else {
            $nextVisitDate = new DateTime($data['nextvisitdate']);
            $inspectionDate = new DateTime($inspection['date']);

            if ($nextVisitDate < $inspectionDate) {
                throw new Exception("Next visit date cannot be earlier than the inspection date", 400);
            }
        }

        if (!isset($data['deathdate'])) {
            $data['deathdate'] = null;
        }

        $existingDiagnoses = $this->getInspectionDiagnoses($id);

        if(count($data['diagnoses']) < count($existingDiagnoses)) {
            throw new Exception("Missing required field: $field", 400);
        }

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

    
        $this->updateDiagnoses($diagnosesForUpdate);
        $this->createDiagnoses($diagnosesForCreate, $id);
        $this->model->update($id, $data);
        return $id;
    }

    public function updateDiagnoses($data) {
        $ids = [];
        foreach($data as $newDiagnosis) {
            $oldDiagnosis = $this->model->getDiagnosisById($newDiagnosis['id']);
        if(!$oldDiagnosis) {
            throw new Exception("diagnosis not found", 404);
        }

        $requiredFields = ['description', 'type', 'icd_10_id'];
        foreach ($requiredFields as $field) {
            if (!isset($newDiagnosis[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

         $ids[] = $this->model->updateDiagnosis($newDiagnosis);
        }
        return $ids;
    }

    
    public function createDiagnoses($data, $inspectionId) {
        foreach ($data as $diagnosis) {
            if(!UUIDValidator::isValid($diagnosis['icd_10_id'])) {
                throw new Exception('invalid id format', 400);
                break;  
            }
             $this->model->createDiagnosis($diagnosis, $inspectionId);
        }
    }

     //validate functions
    public function validateDiagnosesForInspection($diagnoses) {
        $hasMainDiagnosis = false;
        foreach ($diagnoses as $diagnosis) {
            if ($diagnosis['type'] === 'Main') {
                $hasMainDiagnosis = true;
                break;
            }
        }

        if (!$hasMainDiagnosis) {
            throw new Exception("Only one diagnosis with type 'Main' is required.", 400);
        }

        $requiredFieldsForDiagnoses = ['description', 'type', 'icd_10_id'];
        foreach($diagnoses as $diagnosis) {
        foreach ($requiredFieldsForDiagnoses as $field) {
            if (!isset($diagnosis[$field])) {
                throw new Exception("Missing required field for diagnosis: $field", 400);
            }
        }
        }

        return true;
    }

    public function validateConsultationsForInspection($consultations) {
            $requiredFieldsForConsultations = ['speciality_id', 'comment'];
            foreach($consultations as $consultation) {
                foreach($requiredFieldsForConsultations as $field) {
                    if (!isset($consultation[$field])) {
                    throw new Exception("Missing required field for consultation: $field", 400);
                }
                }
            }

            $specialityIds = [];
            foreach ($consultations as $consultation) {
                if (isset($specialityIds[$consultation['speciality_id']])) {
                    throw new Exception("must be only one consultation for one doctor speciality", 400);
                }
                $specialityIds[$consultation['speciality_id']] = true;
            }

        return true;
    }

    public function validateCommentsForConsultation($consultations) {
        foreach($consultations as $consultation) {
            if(!isset($consultation['comment']['content'])) {
                throw new Exception("Missing required field for comment: content", 400);
            }
        }
    }

}

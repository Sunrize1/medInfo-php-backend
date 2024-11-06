<?php
class InspectionModel {
    private $pdo;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function create($data) {
        $sql = "INSERT INTO inspection (patient_id, doctor_id, date, anamnesis, complaints, treatment, conclusion, nextvisitdate, deathdate, previousinspectionid) 
                VALUES (:patient_id, :doctor_id, :date, :anamnesis, :complaints, :treatment, :conclusion, :nextvisitdate, :deathdate, :previousinspectionid) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch();
        return $result['id'];
    }


    public function getAll($patientId) {
            $sql = "
            SELECT 
                i.*,
                d.id AS doctor_id, d.name AS doctor_name, s.name AS doctor_speciality,
                p.id AS patient_id, p.name AS patient_name, p.birthday AS patient_birthday,
                diag.id AS diagnosis_id, diag.type AS diagnosis_type, diag.createtime AS diagnosis_createtime, diag.description AS diagnosis_description,  icd.name AS diagnosis_name, icd.code AS diagnosis_code
            FROM 
                inspection i
            LEFT JOIN 
                doctor d ON i.doctor_id = d.id
            LEFT JOIN 
                speciality s ON d.speciality_id = s.id
            LEFT JOIN 
                patient p ON i.patient_id = p.id
            LEFT JOIN 
                diagnosis diag ON i.id = diag.inspection_id AND diag.type = 'Main'
            LEFT JOIN 
                icd_10 icd ON diag.icd_10_id = icd.id
            WHERE 
                i.patient_id = :patientId
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['patientId' => $patientId]);
        return $stmt->fetchAll();
    }


    public function getById($id) {
        $sql = "
            SELECT 
                i.*,
                d.id AS doctor_id, d.name AS doctor_name, d.birthday AS doctor_birthday, d.gender AS doctor_gender, d.phone AS doctor_phone, d.email AS doctor_email, d.createtime AS doctor_createtime,
                p.id AS patient_id, p.name AS patient_name, p.birthday AS patient_birthday, p.gender AS patient_gender, p.createtime AS patient_createtime
            FROM 
                inspection i
            LEFT JOIN 
                doctor d ON i.doctor_id = d.id
            LEFT JOIN 
                patient p ON i.patient_id = p.id
            WHERE 
                i.id = :id
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    
    public function getBaseInspectionId($id) {
        $sql = "
            SELECT 
                id, previousinspectionid
            FROM 
                inspection
            WHERE 
                id = :inspectionId
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['inspectionId' => $id]);
        $result = $stmt->fetch();

        if ($result && $result['previousinspectionid'] !== null) {
            return $this->getBaseInspectionId($result['previousinspectionid']);
        }

        return $result['id'];
    }


    public function getInspectionChain($id) {
        $sql = "
            SELECT 
                i.*,
                d.id AS doctor_id, d.name AS doctor_name, d.birthday AS doctor_birthday, d.gender AS doctor_gender, d.phone AS doctor_phone, d.email AS doctor_email, d.createtime AS doctor_createtime,
                p.id AS patient_id, p.name AS patient_name, p.birthday AS patient_birthday, p.gender AS patient_gender, p.createtime AS patient_createtime
            FROM 
                inspection i
            LEFT JOIN 
                doctor d ON i.doctor_id = d.id
            LEFT JOIN 
                patient p ON i.patient_id = p.id
            WHERE 
                i.previousinspectionid = :id
        ";
    
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function getInspectionsByDiagnosis($request, $patientId) {
        $sql = "
            SELECT 
                i.id, i.createtime, i.date,
                diag.id AS diagnosis_id, diag.createtime AS diagnosis_createtime, diag.type AS diagnosis_type,
                icd.code AS diagnosis_code, icd.name AS diagnosis_name, diag.description AS diagnosis_description
            FROM 
                inspection i
            LEFT JOIN 
                diagnosis diag ON i.id = diag.inspection_id
            LEFT JOIN 
                icd_10 icd ON diag.icd_10_id = icd.id
            WHERE 
                i.patient_id = :patientId
                AND (icd.name LIKE :request OR icd.code LIKE :request)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'patientId' => $patientId,
            'request' => '%' . $request . '%'
        ]);
        return $stmt->fetchAll();
    
    }


    public function getInspectionDiagnoses($id) {
        $sql = "
            SELECT 
                diag.id AS diagnosis_id, diag.createtime AS diagnosis_createtime, diag.type AS diagnosis_type,
                icd.code AS diagnosis_code, icd.name AS diagnosis_name, diag.description AS diagnosis_description
            FROM 
                diagnosis diag
            LEFT JOIN 
                icd_10 icd ON diag.icd_10_id = icd.id
            WHERE 
                diag.inspection_id = :inspectionId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['inspectionId' => $id]);
        return $stmt->fetchAll();
    }


    public function update($id, $data) {
        $params = [
        'anamnesis' => $data['anamnesis'],
        'complaints' => $data['complaints'],
        'treatment' => $data['treatment'],
        'conclusion' => $data['conclusion'],
        'nextvisitdate' => $data['nextvisitdate'],
        'deathdate' => $data['deathdate'],
        'inspection_id' => $id
        ];

        $sql = "UPDATE inspection SET anamnesis = :anamnesis, complaints = :complaints, treatment = :treatment, conclusion = :conclusion, nextvisitdate = :nextvisitdate, deathdate = :deathdate  WHERE id = :inspection_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
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


    public function getAll($patientId, $size, $offset, $grouped) {
            $sql = "
            SELECT 
                i.*,
                d.id AS doctor_id, d.name AS doctor_name, s.name AS doctor_speciality,
                p.id AS patient_id, p.name AS patient_name, p.birthday AS patient_birthday
            FROM 
                inspection i
            LEFT JOIN 
                doctor d ON i.doctor_id = d.id
            LEFT JOIN 
                speciality s ON d.speciality_id = s.id
            LEFT JOIN 
                patient p ON i.patient_id = p.id
            WHERE 
                i.patient_id = :patientId
        ";
        
        if ($grouped) {
            $sql .= " AND i.has_chain = true";
        }

        $sql .= " LIMIT :size OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['patientId' => $patientId, 'size' => $size, 'offset' => $offset]);
        return $stmt->fetchAll();
    }

    public function getInspectionsCount($patientId) {
        $sql = "
        SELECT 
            COUNT(*) AS total 
        FROM 
            inspection i
        WHERE 
            i.patient_id = :patient_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetch()['total'];
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
        return $stmt->fetch();
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

    public function getInspectionConsultations($id) {
        $sql = "
            SELECT 
                c.id AS consultation_id, c.createtime AS consultation_createtime, c.inspection_id,
                s.id AS speciality_id, s.createtime AS speciality_createtime, s.name AS speciality_name,
                rc.id AS root_comment_id, rc.createtime AS root_comment_createtime, rc.parent_id, rc.content AS root_comment_content,
                d.id AS author_id, d.createtime AS author_createtime, d.name AS author_name, d.birthday, d.gender, d.email, d.phone,
                rc.modified_date AS modified_date
            FROM 
                consultation c
            JOIN 
                speciality s ON c.speciality_id = s.id
            JOIN 
                comment rc ON rc.parent_id IS NULL AND rc.consultation_id = c.id
            JOIN 
                doctor d ON rc.doctor_id = d.id
            WHERE 
                c.inspection_id = :inspectionId
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

    public function createDiagnosis($data, $inspectionId) {
        $data['inspection_id'] = $inspectionId;
        $sql = "INSERT INTO diagnosis (inspection_id, description, type, icd_10_id) 
                VALUES (:inspection_id, :description, :type, :icd_10_id) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch();
        return $result['id'];
    }


    public function getMainDiagnosis($inspectionId) {
        $sql = "
        SELECT d.id,
            d.createtime AS createtime,
            d.description AS description,
            d.type AS type,
            icd.name AS name,
            icd.code AS code
        FROM diagnosis d
        JOIN icd_10 icd ON d.icd_10_id = icd.id
        WHERE d.inspection_id = :inspectionId AND d.type = 'Main'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['inspectionId'=> $inspectionId]);
        $result = $stmt->fetch();
        return $result;
    }

    public function getDiagnosisById($id) {
        $sql = "
        SELECT * FROM diagnosis WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateDiagnosis($data) {
        $sql = "
            UPDATE 
                diagnosis
            SET 
                type = :type,
                description = :description,
                icd_10_id = :icd_10_id
            WHERE 
                id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return ['message' => 'diagnosis updated succesfuly'];
    }

    public function createConsultation($specialityId, $inspectionId) {
        $data['inspection_id'] = $inspectionId;
        $sql = "INSERT INTO consultation (inspection_id, speciality_id) 
                VALUES (:inspection_id, :speciality_id) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['inspection_id' => $inspectionId, 'speciality_id' => $specialityId]);
        $result = $stmt->fetch();
        return $result['id'];
    }
}
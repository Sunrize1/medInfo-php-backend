<?php
class ConsultationModel {
    private $pdo;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function create($specialityId, $inspectionId) {
        $data['inspection_id'] = $inspectionId;
        $sql = "INSERT INTO consultation (inspection_id, speciality_id) 
                VALUES (:inspection_id, :speciality_id) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['inspection_id' => $inspectionId, 'speciality_id' => $specialityId]);
        $result = $stmt->fetch();
        return $result['id'];
    }

    public function createNestedComment($data, $consultationId, $doctorId) {
        $data['consultationId'] = $consultationId;
        $data['doctorId'] = $doctorId;
        $sql = "INSERT INTO comment (consultation_id, doctor_id, content, parent_id) 
                VALUES (:consultationId, :doctorId, :content, :parentId) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch();
        return $result['id'];
    }

    public function createBaseComment($data, $consultationId, $doctorId) {
        $data['consultationId'] = $consultationId;
        $data['doctorId'] = $doctorId;
        $sql = "INSERT INTO comment (consultation_id, doctor_id, content) 
                VALUES (:consultationId, :doctorId, :content) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch();
        return $result['id'];
    }

    public function getConsultationById($id) {
        $sql = "
        SELECT 
            c.id AS consultation_id,
            c.createtime AS consultation_createtime,
            c.inspection_id,
            s.id AS speciality_id,
            s.createtime AS speciality_createtime,
            s.name AS speciality_name,
            i.doctor_id AS doctor_id
        FROM
            consultation c
        JOIN 
            inspection i ON c.inspection_id = i.id
        JOIN
            speciality s ON c.speciality_id = s.id
        WHERE 
            c.id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getAllCommentsOfConsultation($id) {
        $sql = "
        SELECT 
            cm.id AS comment_id,
            cm.createtime AS comment_createtime,
            cm.modified_date AS comment_modified_date,
            cm.content,
            cm.doctor_id as author_id,
            d.name AS author_name,
            cm.parent_id
        FROM
            comment cm
        JOIN
            doctor d ON cm.doctor_id = d.id
        WHERE 
            cm.consultation_id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return  $stmt->fetchAll();
    }

    public function getCommentById($id) {
        $sql = "
        SELECT * FROM comment WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateComment($data, $id) {
        $data['id'] = $id;
        $sql = "
        UPDATE comment
        SET content = :content
        WHERE id = :id;
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $id;
    }


    public function getInspectionsWithConsultations($specialityId, $grouped, $offset, $size) {
        $sql = "
        SELECT 
            i.*,
            d.name AS doctor_name,
            p.name AS patient_name,
            di.id AS diagnosis_id,
            di.createtime AS diagnosis_createtime,
            di.description AS diagnosis_description,
            di.type AS diagnosis_type,
            icd.code AS diagnosis_code,
            icd.name AS diagnosis_name
        FROM 
            inspection i
        JOIN 
            consultation c ON i.id = c.inspection_id
        JOIN 
            doctor d ON i.doctor_id = d.id
        JOIN 
            patient p ON i.patient_id = p.id
        JOIN 
            diagnosis di ON di.inspection_id = i.id
        JOIN 
            icd_10 icd ON di.icd_10_id = icd.id
        WHERE 
            c.speciality_id = :speciality_id
            AND di.type = 'Main'
        ";

        if ($grouped) {
            $sql .= " AND i.has_chain = true";
        }

        $sql .= " LIMIT :size OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('speciality_id', $specialityId);
        $stmt->bindValue('size', $size);
        $stmt->bindValue('offset', $offset );

        $stmt->execute();
        return $stmt->fetchAll();

    }

    public function getInspectionsCount($specialityId) {
    $sql = "
        SELECT 
            COUNT(*) AS total 
        FROM 
            inspection i
        JOIN 
            consultation c ON i.id = c.inspection_id
        WHERE 
            c.speciality_id = :speciality_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['speciality_id' => $specialityId]);
        return $stmt->fetch()['total'];
    }
}
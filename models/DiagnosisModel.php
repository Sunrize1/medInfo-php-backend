<?php
class DiagnosisModel {
    private $pdo;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function create($data, $inspectionId) {
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
        SELECT d.*
        FROM diagnosis d
        WHERE d.inspection_id = :inspectionId AND d.type = 'Main'
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['inspectionId'=> $inspectionId]);
        $result = $stmt->fetch();
        return $result;
    }

    public function getById($id) {
        $sql = "
        SELECT * FROM diagnosis WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function update($data) {
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
}
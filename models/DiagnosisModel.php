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


}
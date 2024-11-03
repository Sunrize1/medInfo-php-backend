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
        $sql = "SELECT * FROM inspection WHERE patient_id = :patientId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['patientId' => $patientId]);
        return $stmt->fetchAll();
    }


    public function getById($id) {
        $sql = "SELECT * FROM inspection WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
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

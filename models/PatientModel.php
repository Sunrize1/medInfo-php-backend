<?php
class PatientModel {
    private $pdo;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO patient (name, birthday, gender) VALUES (:name, :birthday, :gender) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['id'];
    }


    public function get() {
        $sql = "SELECT * FROM patient";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function getById($id) {
        $sql = "SELECT * FROM patient WHERE id = :id"; 
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->fetch();
    }


}
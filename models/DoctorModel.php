<?php
class DoctorModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $sql = "INSERT INTO doctor (full_name, birth_date, gender, phone, email, specialty_id, password) VALUES (:full_name, :birth_date, :gender, :phone, :email, :specialty_id, :password)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function getById($id) {
        $sql = "SELECT * FROM doctor WHERE doctor_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        $sql = "SELECT * FROM doctor WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
}
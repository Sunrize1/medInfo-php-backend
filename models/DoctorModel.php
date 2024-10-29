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
        $sql = "SELECT doctor_id, full_name, birth_date, gender, phone, email FROM doctor WHERE doctor_id = :id";
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


    public function update($data, $id) {
        $params = [
        'full_name' => $data['full_name'],
        'birth_date' => $data['birth_date'],
        'gender' => $data['gender'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'doctor_id' => $id
        ];

        $sql = "UPDATE doctor SET full_name = :full_name, birth_date = :birth_date, gender = :gender, phone = :phone, email = :email WHERE doctor_id = :doctor_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }


    public function invalidateToken($token) {
        $sql = "INSERT INTO invalid_token (token) VALUES (:token)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
    }
}
?>
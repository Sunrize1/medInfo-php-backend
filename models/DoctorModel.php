<?php
class DoctorModel {
    private $pdo;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function create($data) {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $sql = "INSERT INTO doctor (name, birthDay, gender, phone, email, speciality_id, password) VALUES (:name, :birthDay, :gender, :phone, :email, :speciality_id, :password) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        $result = $stmt->fetch();
        return $result['id'];
    }


    public function getById($id) {
        $sql = "SELECT id, name, birthDay, gender, phone, email, createTime, speciality_id FROM doctor WHERE id = :id";
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
        'name' => $data['name'],
        'birthDay' => $data['birthDay'],
        'gender' => $data['gender'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'doctor_id' => $id
        ];

        $sql = "UPDATE doctor SET name = :name, birthDay = :birthDay, gender = :gender, phone = :phone, email = :email WHERE id = :doctor_id";

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
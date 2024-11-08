<?php
require_once 'auth/JWTHandler.php';

class DoctorService {
    private $model;


    public function __construct($model) {
        $this->model = $model;
    }


    public function loginDoctorService($data) {
        if (!isset($data['email']) || !isset($data['password'])) {
            throw new Exception('Missing required fields', 400);
        }

        $email = $data['email'];
        $password = $data['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format', 400);
        }

        $doctor = $this->model->getByEmail($email);

        if (!$doctor) {
            throw new Exception('Doctor not found', 404);
        }

        if (!password_verify($password, $doctor['password'])) {
            throw new Exception('Invalid password', 400);
        }

        $jwtHandler = new JwtHandler();
        return $jwtHandler->jwtEncodeData('doctor', ['doctor_id' => $doctor['id']]);
    }


    public function logoutDoctor($headers) {
        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);
        $this->model->invalidateToken($token);
    }


    public function registerDoctor($data) {
        $requiredFields = ['name', 'birthDay', 'gender', 'phone', 'email', 'speciality_id', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        return $this->model->create($data);
    }


    public function getCurrentDoctor($doctorId) {
        return $this->model->getById($doctorId);
    }

    public function getDoctorById($doctorId) {
        if(!UUIDValidator::isValid($doctorId)) {
         throw new Exception('invalid id format', 400) ;  
        }

        $doctor = $this->model->getById($doctorId);
        if (!$doctor) {
            throw new Exception('doctor not found', 404);
        }
        return $doctor;
    }


    public function updateDoctor($data, $doctorId) {
        if(!UUIDValidator::isValid($doctorId)) {
         throw new Exception('invalid id format', 400) ;  
        }
        
        $requiredFields = ['name', 'birthDay', 'gender', 'phone', 'email'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field", 400);
            }
        }

        if (!in_array($data['gender'], ['Male', 'Female'])) {
            throw new Exception('Invalid gender value', 400);
        }

        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) !== 11) {
            throw new Exception('Invalid phone number', 400);
        }
        $data['phone'] = '+7 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7, 2) . '-' . substr($phone, 9, 2);

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format', 400);
        }

        return $this->model->update($data, $doctorId);
    }
}
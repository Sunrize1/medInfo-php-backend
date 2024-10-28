<?php
require_once 'auth/JWTHandler.php';
require_once 'auth/authMiddleware.php';

class DoctorController {
 private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    function loginDoctor() {
    $data = json_decode(file_get_contents('php://input'), true);

    
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    $email = $data['email'];
    $password = $data['password'];

    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    $doctor = $this->model->getByEmail($email);

    if (!$doctor) {
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
        return;
    }

    
    if (!password_verify($password, $doctor['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid password']);
        return;
    }

    
    $jwtHandler = new JwtHandler();
    $token = $jwtHandler->jwtEncodeData('doctor', ['doctor_id' => $doctor['doctor_id']]);
    echo json_encode(['token' => $token]);
}

function registerDoctor() { 
    $data = json_decode(file_get_contents('php://input'), true);

    
    $requiredFields = ['full_name', 'birth_date', 'gender', 'phone', 'email', 'specialty_id', 'password'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }

    
    
    $newDoctorId = $this->model->createDoctor($data);

    if ($newDoctorId) {
        http_response_code(201);
        echo json_encode(['message' => 'Doctor registered successfully', 'doctor_id' => $newDoctorId]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to register doctor']);
    }
}

function getCurrentDoctor() {
    $headers = apache_request_headers();
    if (!authMiddleware($headers)) {
        return;
    }

    $authHeader = $headers['Authorization'];
    preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
    $token = $matches[1];

    $jwtHandler = new JwtHandler();
    $decoded = $jwtHandler->jwtDecodeData($token);

    $doctorId = $decoded['data']->doctor_id;

    $doctorInfo = $this->model->getById($doctorId);

    if ($doctorInfo) {
        echo json_encode($doctorInfo);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Doctor not found']);
    }
}
}

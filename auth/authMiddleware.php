<?php
require_once 'auth/JwtHandler.php';

function authMiddleware($headers, $pdo) {
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        return false;
    }

    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwtHandler = new JwtHandler();
        $decoded = $jwtHandler->jwtDecodeData($matches[1]);
        if (isset($decoded['data'])) {
            $sql = "SELECT * FROM invalid_token WHERE token = :token";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['token' => $matches[1]]);
            $invalidToken = $stmt->fetch();

            if ($invalidToken) {
                http_response_code(401);
                echo json_encode(['error' => 'Token is invalidated']);
                return false;
            }

            return true;
        }
    }

    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    return false;
}

function getDoctorIdByToken($headers) {
    $authHeader = $headers['Authorization'];
    preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
    $token = $matches[1];

    $jwtHandler = new JwtHandler();
    $decoded = $jwtHandler->jwtDecodeData($token);

    $doctorId = $decoded['data']->doctor_id;
    return $doctorId;
}
?>
<?php
require_once 'auth/JwtHandler.php';

function authMiddleware($headers) {
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
            return true;
        }
    }

    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    return false;
}
?>
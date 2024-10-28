<?php
require_once 'vendor/autoload.php';
require_once 'config/connect.php';
require_once 'api.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$match = $router->match();


if ($match && is_callable($match['target'])) {
    call_user_func_array($match['target'], $match['params']);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}

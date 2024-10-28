<?php
require_once 'vendor/autoload.php';
require_once 'router.php';
require_once 'controllers/DoctorController.php';
require_once 'models/DoctorModel.php';

header("Content-Type:application/json");

$router = new AltoRouter();

$doctorModel = new DoctorModel($pdo);
$doctorController = new DoctorController($doctorModel);

$router->map('POST', '/api/doctor/login', function() use ($doctorController) {
    $doctorController->loginDoctor();
});

$router->map('POST', '/api/doctor/register', function() use ($doctorController) {
    $doctorController->registerDoctor();
});

$router->map('GET', '/api/doctor/profile', function() use ($doctorController) {
    $headers = apache_request_headers();
    $doctorController->getCurrentDoctor($headers);
});
?>
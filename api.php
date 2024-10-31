<?php
require_once 'vendor/autoload.php';
require_once 'router.php';
require_once 'controllers/DoctorController.php';
require_once 'controllers/PatientController.php';
require_once 'models/DoctorModel.php';
require_once 'models/PatientModel.php';

header("Content-Type:application/json");

$router = new AltoRouter();

$doctorModel = new DoctorModel($pdo);
$doctorController = new DoctorController($doctorModel, $pdo);

$patientModel = new PatientModel($pdo);
$patientController = new PatientController($patientModel, $pdo);

//doctor
$router->map('POST', '/api/doctor/register', function() use ($doctorController) {
    $doctorController->registerDoctor();
});
$router->map('POST', '/api/doctor/login', function() use ($doctorController) {
    $doctorController->loginDoctor();
});
$router->map('POST', '/api/doctor/logout', function() use ($doctorController) {
    $doctorController->logoutDoctor();
});
$router->map('GET', '/api/doctor/profile', function() use ($doctorController) {
    $doctorController->getCurrentDoctor();
});
$router->map('PUT', '/api/doctor/profile', function() use ($doctorController) {
    $doctorController->updateDoctor();
});


//patient
$router->map('POST', '/api/patient', function() use ($patientController) {
    $patientController->createPatient();
});
$router->map('GET', '/api/patient', function() use ($patientController) {
    $patientController->getAllPatients();
});
$router->map('GET', '/api/patient/[:id]', function($id) use ($patientController) {
    $patientController->getPatientById($id);
});
?>
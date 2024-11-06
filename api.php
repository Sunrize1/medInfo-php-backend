<?php
require_once 'vendor/autoload.php';
require_once 'router.php';
require_once 'controllers/DoctorController.php';
require_once 'controllers/PatientController.php';
require_once 'controllers/InspectionController.php';
require_once 'models/DoctorModel.php';
require_once 'models/PatientModel.php';
require_once 'models/InspectionModel.php';
require_once 'models/DiagnosisModel.php';
require_once 'services/DoctorService.php';
require_once 'services/PatientService.php';
require_once 'services/InspectionService.php';
require_once 'services/DiagnosisService.php';

header("Content-Type:application/json");

$router = new AltoRouter();

$doctorModel = new DoctorModel($pdo);
$doctorService = new DoctorService($doctorModel);
$doctorController = new DoctorController($doctorService, $pdo);

$diagnosisModel = new DiagnosisModel($pdo);
$diagnosisService = new DiagnosisService($diagnosisModel);


$patientModel = new PatientModel($pdo);
$patientService = new PatientService($patientModel);

$inspectionModel = new InspectionModel($pdo);
$inspectionService = new InspectionService($inspectionModel);
$inspectionController = new InspectionController($pdo, $inspectionService);

$patientController = new PatientController($patientService, $inspectionService, $pdo);

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
$router->map('POST', '/api/patient/[:id]/inspections', function($id) use ($patientController, $diagnosisService) {
    $response = $patientController->createInspectionForPatient($id);
    if($response) $diagnosisService->createDiagnoses($response);

});
$router->map('GET', '/api/patient/[:id]/inspections', function($id) use ($patientController) {
    $patientController->getAllInspectionsOfPatient($id);
});
$router->map('GET', '/api/patient/[:id]/inspections/search', function($id) use ($patientController) {
    $request = $_GET['request'] ?? '';
    $patientController->searchInspectionsByDiagnosis($id, $request);
});

//inspection
$router->map('GET', '/api/inspection/[:id]', function($id) use ($inspectionController) {
    $inspectionController->getInspectionById($id);
});
$router->map('GET', '/api/inspection/[:id]/chain', function($id) use ($inspectionController) {
    $inspectionController->getInspectionChain($id);
});
$router->map('PUT', '/api/inspection/[:id]', function($id) use ($inspectionController, $diagnosisService) {
    try {
        $response = $inspectionController->updateInspection($id);
        if($response['diagnosesForCreate']) $diagnosisService->createDiagnoses($response['diagnosesForCreate']);
        if($response['diagnosesForUpdate']) $diagnosisService->updateDiagnoses($response['diagnosesForUpdate']);
        http_response_code(200);
        echo json_encode(['message' => "updated succesfuly"]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    };
});
?>
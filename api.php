<?php
require_once 'vendor/autoload.php';
require_once 'router.php';
require_once 'others/DictionaryModel.php';
require_once 'others/DictionaryService.php';
require_once 'others/DictionaryController.php';
require_once 'controllers/DoctorController.php';
require_once 'controllers/PatientController.php';
require_once 'controllers/InspectionController.php';
require_once 'controllers/ConsultationController.php';
require_once 'models/DoctorModel.php';
require_once 'models/PatientModel.php';
require_once 'models/InspectionModel.php';
require_once 'models/ConsultationModel.php';
require_once 'services/DoctorService.php';
require_once 'services/PatientService.php';
require_once 'services/InspectionService.php';
require_once 'services/ConsultationService.php';

header("Content-Type:application/json");

$router = new AltoRouter();

$dictionaryModel = new DictionaryModel($pdo);
$dictionaryService = new DictionaryService($dictionaryModel);
$dictionaryController = new DictionaryController($dictionaryService);

$doctorModel = new DoctorModel($pdo);
$doctorService = new DoctorService($doctorModel);
$doctorController = new DoctorController($doctorService, $pdo);

$consultationModel = new ConsultationModel($pdo);
$consultationService = new ConsultationService($consultationModel, $doctorModel);
$consultationController = new ConsultationController($pdo, $consultationService);

$patientModel = new PatientModel($pdo);
$patientService = new PatientService($patientModel);

$inspectionModel = new InspectionModel($pdo);
$inspectionService = new InspectionService($inspectionModel, $consultationService);
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
$router->map('POST', '/api/patient/[:id]/inspections', function($id) use ($patientController) {
    $patientController->createInspectionForPatient($id);
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
$router->map('PUT', '/api/inspection/[:id]', function($id) use ($inspectionController) {
    $inspectionController->updateInspection($id);
});

//dictionary
$router->map('GET', '/api/dictionary/speciality', function() use ($dictionaryController) {
    $name = $_GET['name'] ?? '';
    $page = $_GET['page'] ?? 1;
    $size = $_GET['size'] ?? 5;
    $dictionaryController->getSpecialtiesList($name, $page, $size);
});
$router->map('GET', '/api/dictionary/icd10', function() use ($dictionaryController) {
    $request = $_GET['request'] ?? '';
    $page = $_GET['page'] ?? 1;
    $size = $_GET['size'] ?? 5;
    $dictionaryController->getIcd10List($request, $page, $size);
});
$router->map('GET', '/api/dictionary/icd10/roots', function() use ($dictionaryController) {
    $dictionaryController->getIcd10Roots();
});

//consultation
$router->map('POST', '/api/consultation/[:id]/comment', function($id) use ($consultationController) {
    $consultationController->createCommentForConsultation($id);
});
$router->map('GET', '/api/consultation/[:id]', function($id) use ($consultationController) {
    $consultationController->getConsultationById($id);
});
$router->map('PUT', '/api/consultation/comment/[:id]', function($id) use ($consultationController) {
    $consultationController->updateComment($id);
});
$router->map('GET', '/api/consultation', function() use ($consultationController) {
    $consultationController->getInspectionsWithConsultations();
});
?>
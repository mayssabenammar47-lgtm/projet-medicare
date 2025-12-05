<?php
/**
 * projet-medicare/api/patient_history.php
 * API pour récupérer l'historique d'un patient
 */

header('Content-Type: application/json');
require_once '../includes/functions.php';

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$patientId = intval($_GET['id'] ?? 0);
if ($patientId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID patient invalide']);
    exit();
}

$history = getPatientHistory($patientId);

echo json_encode($history);
?>
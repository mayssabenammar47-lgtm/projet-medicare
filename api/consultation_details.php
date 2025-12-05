<?php
/**
 * projet-medicare/api/consultation_details.php
 * API pour récupérer les détails d'une consultation
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

$consultationId = intval($_GET['id'] ?? 0);
if ($consultationId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID consultation invalide']);
    exit();
}

$consultation = getConsultationById($consultationId);
if (!$consultation) {
    http_response_code(404);
    echo json_encode(['error' => 'Consultation non trouvée']);
    exit();
}

// Récupérer les prescriptions
$prescriptions = fetchAll("SELECT p.*, m.nom as medicament_nom 
                           FROM prescriptions p 
                           JOIN medicaments m ON p.id_medicament = m.id 
                           WHERE p.id_consultation = :id", 
                           ['id' => $consultationId]);

$response = array_merge($consultation, ['prescriptions' => $prescriptions]);

echo json_encode($response);
?>
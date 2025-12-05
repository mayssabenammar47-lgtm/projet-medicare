<?php
/**
 * projet-medicare/api/search.php
 * API de recherche avancée
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

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$limit = intval($_GET['limit'] ?? 10);

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$results = [];

// Recherche de patients
if ($type === 'all' || $type === 'patients') {
    $patients = fetchAll(
        "SELECT id, nom, prenom, email, telephone, date_naissance 
         FROM patients 
         WHERE nom LIKE :query OR prenom LIKE :query OR email LIKE :query 
         ORDER BY nom, prenom 
         LIMIT :limit",
        ['query' => '%' . $query . '%', 'limit' => $limit]
    );
    
    foreach ($patients as $patient) {
        $results[] = [
            'id' => $patient['id'],
            'type' => 'patient',
            'title' => $patient['nom'] . ' ' . $patient['prenom'],
            'subtitle' => $patient['email'] ?: $patient['telephone'],
            'url' => '../pages/patients/patients_form.php?id=' . $patient['id'],
            'data' => $patient
        ];
    }
}

// Recherche de médecins
if ($type === 'all' || $type === 'medecins') {
    $medecins = fetchAll(
        "SELECT u.id, u.nom, u.email, m.specialite, m.telephone 
         FROM utilisateurs u 
         JOIN medecins m ON u.id = m.id_utilisateur 
         WHERE u.role = 'medecin' AND (u.nom LIKE :query OR m.specialite LIKE :query) 
         ORDER BY u.nom 
         LIMIT :limit",
        ['query' => '%' . $query . '%', 'limit' => $limit]
    );
    
    foreach ($medecins as $medecin) {
        $results[] = [
            'id' => $medecin['id'],
            'type' => 'medecin',
            'title' => 'Dr ' . $medecin['nom'],
            'subtitle' => $medecin['specialite'] ?: 'Médecin',
            'url' => '../pages/medecins/medecins.php',
            'data' => $medecin
        ];
    }
}

// Recherche de rendez-vous
if ($type === 'all' || $type === 'rendezvous') {
    $rendezvous = fetchAll(
        "SELECT rv.id, rv.date_heure, rv.statut, 
                p.nom as patient_nom, p.prenom as patient_prenom,
                u.nom as medecin_nom
         FROM rendez_vous rv 
         JOIN patients p ON rv.id_patient = p.id 
         JOIN medecins m ON rv.id_medecin = m.id 
         JOIN utilisateurs u ON m.id_utilisateur = u.id 
         WHERE p.nom LIKE :query OR p.prenom LIKE :query OR u.nom LIKE :query 
         ORDER BY rv.date_heure DESC 
         LIMIT :limit",
        ['query' => '%' . $query . '%', 'limit' => $limit]
    );
    
    foreach ($rendezvous as $rdv) {
        $results[] = [
            'id' => $rdv['id'],
            'type' => 'rendezvous',
            'title' => 'RDV - ' . $rdv['patient_nom'] . ' ' . $rdv['patient_prenom'],
            'subtitle' => formatDate($rdv['date_heure']) . ' - Dr ' . $rdv['medecin_nom'],
            'url' => '../pages/rendez_vous/rendez_vous.php?edit=' . $rdv['id'],
            'data' => $rdv
        ];
    }
}

// Recherche de consultations
if ($type === 'all' || $type === 'consultations') {
    $consultations = fetchAll(
        "SELECT c.id, c.date_consultation, c.motif, c.diagnostic,
                p.nom as patient_nom, p.prenom as patient_prenom,
                u.nom as medecin_nom
         FROM consultations c 
         JOIN patients p ON c.id_patient = p.id 
         JOIN medecins m ON c.id_medecin = m.id 
         JOIN utilisateurs u ON m.id_utilisateur = u.id 
         WHERE p.nom LIKE :query OR p.prenom LIKE :query OR c.diagnostic LIKE :query 
         ORDER BY c.date_consultation DESC 
         LIMIT :limit",
        ['query' => '%' . $query . '%', 'limit' => $limit]
    );
    
    foreach ($consultations as $consultation) {
        $results[] = [
            'id' => $consultation['id'],
            'type' => 'consultation',
            'title' => 'Consultation - ' . $consultation['patient_nom'] . ' ' . $consultation['patient_prenom'],
            'subtitle' => formatDate($consultation['date_consultation']) . ' - Dr ' . $consultation['medecin_nom'],
            'url' => '../pages/consultations/consultations.php',
            'data' => $consultation
        ];
    }
}

// Recherche de médicaments
if ($type === 'all' || $type === 'medicaments') {
    $medicaments = fetchAll(
        "SELECT id, nom, description, stock, prix 
         FROM medicaments 
         WHERE nom LIKE :query OR description LIKE :query 
         ORDER BY nom 
         LIMIT :limit",
        ['query' => '%' . $query . '%', 'limit' => $limit]
    );
    
    foreach ($medicaments as $medicament) {
        $results[] = [
            'id' => $medicament['id'],
            'type' => 'medicament',
            'title' => $medicament['nom'],
            'subtitle' => 'Stock: ' . $medicament['stock'] . ' - Prix: ' . number_format($medicament['prix'], 2) . '€',
            'url' => '../pages/medicaments/medicaments.php',
            'data' => $medicament
        ];
    }
}

// Trier par pertinence (simple: priorité aux correspondances exactes)
usort($results, function($a, $b) use ($query) {
    $aExact = stripos($a['title'], $query) === 0;
    $bExact = stripos($b['title'], $query) === 0;
    
    if ($aExact && !$bExact) return -1;
    if (!$aExact && $bExact) return 1;
    
    return 0;
});

echo json_encode(array_slice($results, 0, $limit));
?>
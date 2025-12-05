<?php
/**
 * projet-medicare/test_application.php
 * Script de test de l'application Medicare
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de l'application Medicare</h1>";

// Test de connexion à la base de données
echo "<h2>Test de connexion à la base de données</h2>";
try {
    require_once 'config/database.php';
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    
    // Test de création de tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables trouvées: " . implode(', ', $tables) . "</p>";
    
    // Test de données
    $userCount = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $patientCount = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
    $rdvCount = $pdo->query("SELECT COUNT(*) FROM rendez_vous")->fetchColumn();
    
    echo "<p>Utilisateurs: $userCount, Patients: $patientCount, Rendez-vous: $rdvCount</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de base de données: " . $e->getMessage() . "</p>";
}

// Test des fonctions
echo "<h2>Test des fonctions principales</h2>";
try {
    require_once 'includes/functions.php';
    
    // Test de getPatients
    $patients = getPatients();
    echo "<p style='color: green;'>✅ getPatients() fonctionne (" . count($patients) . " patients)</p>";
    
    // Test de getMedecins
    $medecins = getMedecins();
    echo "<p style='color: green;'>✅ getMedecins() fonctionne (" . count($medecins) . " médecins)</p>";
    
    // Test de getRendezVous
    $rendezVous = getRendezVous();
    echo "<p style='color: green;'>✅ getRendezVous() fonctionne (" . count($rendezVous) . " rendez-vous)</p>";
    
    // Test de getConsultations
    $consultations = getConsultations();
    echo "<p style='color: green;'>✅ getConsultations() fonctionne (" . count($consultations) . " consultations)</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur dans les fonctions: " . $e->getMessage() . "</p>";
}

// Test des fichiers
echo "<h2>Test des fichiers requis</h2>";
$requiredFiles = [
    'config/database.php',
    'includes/functions.php',
    'includes/header.php',
    'includes/footer.php',
    'css/style.css',
    'js/script.js',
    'pages/dashboard.php',
    'pages/patients/patients.php',
    'pages/patients/patients_form.php',
    'pages/rendez_vous/rendez_vous.php',
    'pages/consultations/consultation_form.php',
    'pages/consultations/consultations.php',
    'api/search.php',
    'api/patient_history.php',
    'api/consultation_details.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file</p>";
    } else {
        echo "<p style='color: red;'>❌ $file manquant</p>";
    }
}

// Test de l'API
echo "<h2>Test de l'API</h2>";
try {
    // Simuler une session
    session_start();
    $_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
    
    // Test de l'API de recherche
    $_GET['q'] = 'test';
    ob_start();
    include 'api/search.php';
    $result = ob_get_clean();
    $searchData = json_decode($result, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ API search.php fonctionne</p>";
    } else {
        echo "<p style='color: red;'>❌ API search.php erreur JSON</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur API: " . $e->getMessage() . "</p>";
}

echo "<h2>Test terminé</h2>";
echo "<p><a href='index.php'>Aller à la page de connexion</a></p>";
?>
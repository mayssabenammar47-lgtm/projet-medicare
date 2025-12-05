<?php
require_once 'config/database.php';

// Test des statistiques
$stats = [
    'patients_total' => fetchOne("SELECT COUNT(*) as count FROM patients")['count'],
    'medecins_total' => fetchOne("SELECT COUNT(*) as count FROM medecins")['count'],
    'rdv_aujourd'hui' => fetchOne("SELECT COUNT(*) as count FROM rendez_vous WHERE DATE(date_heure) = DATE('now')")['count'],
    'consultations_mois' => fetchOne("SELECT COUNT(*) as count FROM consultations WHERE strftime('%m', date_consultation) = strftime('%m', 'now')")['count'],
    'medicaments_stock_faible' => fetchOne("SELECT COUNT(*) as count FROM medicaments WHERE stock < 10")['count']
];

echo "=== STATISTIQUES CORRIGÉES ===\n";
echo "Patients total: " . $stats['patients_total'] . "\n";
echo "Médecins total: " . $stats['medecins_total'] . "\n";
echo "RDV aujourd'hui: " . $stats['rdv_aujourd'hui'] . "\n";
echo "Consultations ce mois: " . $stats['consultations_mois'] . "\n";
echo "Médicaments stock faible: " . $stats['medicaments_stock_faible'] . "\n";
?>
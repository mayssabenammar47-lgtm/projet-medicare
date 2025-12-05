<?php
/**
 * projet-medicare/includes/functions.php
 * Fonctions utilitaires pour l'application
 */

require_once '../config/database.php';

/**
 * Authentifier un utilisateur
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe
 * @return array|false Données utilisateur ou false
 */
function authenticateUser($email, $password) {
    $sql = "SELECT u.*, m.specialite 
            FROM utilisateurs u 
            LEFT JOIN medecins m ON u.id = m.id_utilisateur 
            WHERE u.email = :email";
    
    $user = fetchOne($sql, ['email' => $email]);
    
    if ($user && password_verify($password, $user['mot_de_passe'])) {
        // Mettre à jour la dernière connexion
        update("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id", 
               ['id' => $user['id']]);
        return $user;
    }
    
    return false;
}

/**
 * Créer un nouvel utilisateur
 * @param array $userData Données de l'utilisateur
 * @return int ID de l'utilisateur créé
 */
function createUser($userData) {
    $sql = "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
            VALUES (:nom, :email, :mot_de_passe, :role)";
    
    $params = [
        'nom' => $userData['nom'],
        'email' => $userData['email'],
        'mot_de_passe' => password_hash($userData['mot_de_passe'], PASSWORD_DEFAULT),
        'role' => $userData['role']
    ];
    
    return insert($sql, $params);
}

/**
 * Obtenir la liste des patients
 * @param string $search Terme de recherche
 * @return array
 */
function getPatients($search = '') {
    $sql = "SELECT * FROM patients";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " WHERE nom LIKE :search OR prenom LIKE :search OR email LIKE :search";
        $params['search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY nom, prenom";
    
    return fetchAll($sql, $params);
}

/**
 * Obtenir un patient par son ID
 * @param int $id ID du patient
 * @return array|null
 */
function getPatientById($id) {
    $sql = "SELECT * FROM patients WHERE id = :id";
    return fetchOne($sql, ['id' => $id]);
}

/**
 * Créer ou mettre à jour un patient
 * @param array $patientData Données du patient
 * @return int ID du patient
 */
function savePatient($patientData) {
    if (isset($patientData['id']) && !empty($patientData['id'])) {
        // Mise à jour
        $sql = "UPDATE patients SET nom = :nom, prenom = :prenom, 
                date_naissance = :date_naissance, telephone = :telephone, 
                email = :email, adresse = :adresse 
                WHERE id = :id";
        
        $params = [
            'nom' => $patientData['nom'],
            'prenom' => $patientData['prenom'],
            'date_naissance' => $patientData['date_naissance'],
            'telephone' => $patientData['telephone'],
            'email' => $patientData['email'],
            'adresse' => $patientData['adresse'],
            'id' => $patientData['id']
        ];
        
        update($sql, $params);
        return $patientData['id'];
    } else {
        // Création
        $sql = "INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse) 
                VALUES (:nom, :prenom, :date_naissance, :telephone, :email, :adresse)";
        
        $params = [
            'nom' => $patientData['nom'],
            'prenom' => $patientData['prenom'],
            'date_naissance' => $patientData['date_naissance'],
            'telephone' => $patientData['telephone'],
            'email' => $patientData['email'],
            'adresse' => $patientData['adresse']
        ];
        
        return insert($sql, $params);
    }
}

/**
 * Obtenir la liste des médecins
 * @return array
 */
function getMedecins() {
    $sql = "SELECT u.*, m.specialite, m.telephone, m.adresse 
            FROM utilisateurs u 
            JOIN medecins m ON u.id = m.id_utilisateur 
            WHERE u.role = 'medecin' 
            ORDER BY u.nom";
    
    return fetchAll($sql);
}

/**
 * Obtenir les rendez-vous d'un médecin
 * @param int $medecinId ID du médecin
 * @param string $date Date optionnelle
 * @return array
 */
function getRendezVousByMedecin($medecinId, $date = '') {
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            WHERE rv.id_medecin = :id_medecin";
    
    $params = ['id_medecin' => $medecinId];
    
    if (!empty($date)) {
        $sql .= " AND DATE(rv.date_heure) = :date";
        $params['date'] = $date;
    }
    
    $sql .= " ORDER BY rv.date_heure";
    
    return fetchAll($sql, $params);
}

/**
 * Obtenir les rendez-vous d'un patient
 * @param int $patientId ID du patient
 * @return array
 */
function getRendezVousByPatient($patientId) {
    $sql = "SELECT rv.*, u.nom as medecin_nom, m.specialite 
            FROM rendez_vous rv 
            JOIN medecins m ON rv.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id 
            WHERE rv.id_patient = :id_patient 
            ORDER BY rv.date_heure DESC";
    
    return fetchAll($sql, ['id_patient' => $patientId]);
}

/**
 * Créer un rendez-vous
 * @param array $rdvData Données du rendez-vous
 * @return int ID du rendez-vous
 */
function createRendezVous($rdvData) {
    $sql = "INSERT INTO rendez_vous (id_patient, id_medecin, date_heure, statut, notes) 
            VALUES (:id_patient, :id_medecin, :date_heure, :statut, :notes)";
    
    $params = [
        'id_patient' => $rdvData['id_patient'],
        'id_medecin' => $rdvData['id_medecin'],
        'date_heure' => $rdvData['date_heure'],
        'statut' => $rdvData['statut'] ?? 'planifie',
        'notes' => $rdvData['notes'] ?? null
    ];
    
    return insert($sql, $params);
}

/**
 * Obtenir la liste des médicaments
 * @param string $search Terme de recherche
 * @return array
 */
function getMedicaments($search = '') {
    $sql = "SELECT * FROM medicaments";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " WHERE nom LIKE :search OR description LIKE :search";
        $params['search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY nom";
    
    return fetchAll($sql, $params);
}

/**
 * Obtenir les statistiques du tableau de bord
 * @return array
 */
function getDashboardStats() {
    $stats = [];
    
    // Nombre total de patients
    $stats['patients_total'] = fetchOne("SELECT COUNT(*) as count FROM patients")['count'];
    
    // Nombre total de médecins (compte les médecins réels avec entrée dans la table medecins)
    $stats['medecins_total'] = fetchOne("SELECT COUNT(*) as count FROM medecins")['count'];
    
    // Rendez-vous aujourd'hui (compatible SQLite)
    $stats['rdv_aujourd'hui'] = fetchOne("SELECT COUNT(*) as count FROM rendez_vous WHERE DATE(date_heure) = DATE('now')")['count'];
    
    // Rendez-vous cette semaine (compatible SQLite)
    $stats['rdv_semaine'] = fetchOne("SELECT COUNT(*) as count FROM rendez_vous WHERE strftime('%W', date_heure) = strftime('%W', 'now')")['count'];
    
    // Consultations ce mois (compatible SQLite)
    $stats['consultations_mois'] = fetchOne("SELECT COUNT(*) as count FROM consultations WHERE strftime('%m', date_consultation) = strftime('%m', 'now')")['count'];
    
    // Médicaments en stock faible
    $stats['medicaments_stock_faible'] = fetchOne("SELECT COUNT(*) as count FROM medicaments WHERE stock < 10")['count'];
    
    return $stats;
}

/**
 * Définir un message flash
 * @param string $message Message à afficher
 * @param string $type Type de message (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type
    ];
}

/**
 * Rediriger vers une page avec un message
 * @param string $url URL de destination
 * @param string $message Message à afficher
 * @param string $type Type de message
 */
function redirectWithMessage($url, $message, $type = 'info') {
    setFlashMessage($message, $type);
    header('Location: ' . $url);
    exit();
}

/**
 * Valider une adresse email
 * @param string $email Email à valider
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valider une date
 * @param string $date Date à valider
 * @param string $format Format attendu
 * @return bool
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Nettoyer une entrée utilisateur
 * @param string $input Entrée à nettoyer
 * @return string
 */
function cleanInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function requireLogin() {
    session_start();
    if (!isset($_SESSION['user'])) {
        redirectWithMessage('../index.php', 'Veuillez vous connecter', 'error');
    }
}

/**
 * Vérifier si l'utilisateur a un rôle spécifique
 * @param string $role Rôle à vérifier
 * @return bool
 */
function hasRole($role) {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

/**
 * Formater une date pour l'affichage
 * @param string $date Date à formater
 * @return string
 */
function formatDate($date) {
    if (!$date) return '-';
    $d = new DateTime($date);
    return $d->format('d/m/Y H:i');
}

/**
 * Obtenir les rendez-vous
 * @param string $search Terme de recherche
 * @param string $statut Filtre par statut
 * @return array
 */
function getRendezVous($search = '', $statut = '') {
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            JOIN medecins m ON rv.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id";
    
    $params = [];
    $where = [];
    
    if (!empty($search)) {
        $where[] = "(p.nom LIKE :search OR p.prenom LIKE :search OR u.nom LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    if (!empty($statut)) {
        $where[] = "rv.statut = :statut";
        $params['statut'] = $statut;
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    
    $sql .= " ORDER BY rv.date_heure DESC";
    
    return fetchAll($sql, $params);
}

/**
 * Obtenir un rendez-vous par son ID
 * @param int $id ID du rendez-vous
 * @return array|null
 */
function getRendezVousById($id) {
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            JOIN medecins m ON rv.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id
            WHERE rv.id = :id";
    return fetchOne($sql, ['id' => $id]);
}

/**
 * Mettre à jour un rendez-vous
 * @param array $rdvData Données du rendez-vous
 * @return bool
 */
function updateRendezVous($rdvData) {
    $sql = "UPDATE rendez_vous SET id_patient = :id_patient, id_medecin = :id_medecin, 
            date_heure = :date_heure, statut = :statut, notes = :notes 
            WHERE id = :id";
    
    $params = [
        'id_patient' => $rdvData['id_patient'],
        'id_medecin' => $rdvData['id_medecin'],
        'date_heure' => $rdvData['date_heure'],
        'statut' => $rdvData['statut'],
        'notes' => $rdvData['notes'],
        'id' => $rdvData['id']
    ];
    
    return update($sql, $params) > 0;
}

/**
 * Supprimer un rendez-vous
 * @param int $id ID du rendez-vous
 * @return bool
 */
function deleteRendezVous($id) {
    return delete("DELETE FROM rendez_vous WHERE id = :id", ['id' => $id]) > 0;
}

/**
 * Obtenir les consultations
 * @param string $search Terme de recherche
 * @return array
 */
function getConsultations($search = '') {
    $sql = "SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom,
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM consultations c 
            JOIN patients p ON c.id_patient = p.id 
            JOIN medecins m ON c.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id";
    
    $params = [];
    
    if (!empty($search)) {
        $sql .= " WHERE p.nom LIKE :search OR p.prenom LIKE :search OR u.nom LIKE :search";
        $params['search'] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY c.date_consultation DESC";
    
    return fetchAll($sql, $params);
}

/**
 * Obtenir une consultation par son ID
 * @param int $id ID de la consultation
 * @return array|null
 */
function getConsultationById($id) {
    $sql = "SELECT c.*, p.nom as patient_nom, p.prenom as patient_prenom,
                   u.nom as medecin_nom, u.prenom as medecin_prenom
            FROM consultations c 
            JOIN patients p ON c.id_patient = p.id 
            JOIN medecins m ON c.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id
            WHERE c.id = :id";
    return fetchOne($sql, ['id' => $id]);
}

/**
 * Créer ou mettre à jour une consultation
 * @param array $consultationData Données de la consultation
 * @return int ID de la consultation
 */
function saveConsultation($consultationData) {
    if (isset($consultationData['id']) && !empty($consultationData['id'])) {
        // Mise à jour
        $sql = "UPDATE consultations SET id_patient = :id_patient, id_medecin = :id_medecin,
                date_consultation = :date_consultation, motif = :motif, 
                symptomes = :symptomes, diagnostic = :diagnostic, 
                traitement = :traitement, notes = :notes 
                WHERE id = :id";
        
        $params = [
            'id_patient' => $consultationData['id_patient'],
            'id_medecin' => $consultationData['id_medecin'],
            'date_consultation' => $consultationData['date_consultation'],
            'motif' => $consultationData['motif'],
            'symptomes' => $consultationData['symptomes'],
            'diagnostic' => $consultationData['diagnostic'],
            'traitement' => $consultationData['traitement'],
            'notes' => $consultationData['notes'],
            'id' => $consultationData['id']
        ];
        
        update($sql, $params);
        return $consultationData['id'];
    } else {
        // Création
        $sql = "INSERT INTO consultations (id_patient, id_medecin, date_consultation, 
                motif, symptomes, diagnostic, traitement, notes) 
                VALUES (:id_patient, :id_medecin, :date_consultation, :motif, 
                :symptomes, :diagnostic, :traitement, :notes)";
        
        $params = [
            'id_patient' => $consultationData['id_patient'],
            'id_medecin' => $consultationData['id_medecin'],
            'date_consultation' => $consultationData['date_consultation'],
            'motif' => $consultationData['motif'],
            'symptomes' => $consultationData['symptomes'],
            'diagnostic' => $consultationData['diagnostic'],
            'traitement' => $consultationData['traitement'],
            'notes' => $consultationData['notes']
        ];
        
        return insert($sql, $params);
    }
}

/**
 * Supprimer une consultation
 * @param int $id ID de la consultation
 * @return bool
 */
function deleteConsultation($id) {
    return delete("DELETE FROM consultations WHERE id = :id", ['id' => $id]) > 0;
}

/**
 * Obtenir l'historique complet d'un patient
 * @param int $patientId ID du patient
 * @return array
 */
function getPatientHistory($patientId) {
    $history = [];
    
    // Rendez-vous
    $history['rendez_vous'] = fetchAll(
        "SELECT rv.*, u.nom as medecin_nom, u.prenom as medecin_prenom
         FROM rendez_vous rv 
         JOIN medecins m ON rv.id_medecin = m.id 
         JOIN utilisateurs u ON m.id_utilisateur = u.id 
         WHERE rv.id_patient = :id_patient 
         ORDER BY rv.date_heure DESC",
        ['id_patient' => $patientId]
    );
    
    // Consultations
    $history['consultations'] = fetchAll(
        "SELECT c.*, u.nom as medecin_nom, u.prenom as medecin_prenom
         FROM consultations c 
         JOIN medecins m ON c.id_medecin = m.id 
         JOIN utilisateurs u ON m.id_utilisateur = u.id 
         WHERE c.id_patient = :id_patient 
         ORDER BY c.date_consultation DESC",
        ['id_patient' => $patientId]
    );
    
    return $history;
}
?>
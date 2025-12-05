<?php
/**
 * projet-medicare/includes/header.php
 * En-tête commun à toutes les pages
 */

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Vérifier le rôle de l'utilisateur
function hasRole($role) {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
}

// Rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

// Rediriger si rôle incorrect
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ../pages/dashboard.php');
        exit();
    }
}

// Nettoyer les entrées utilisateur
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Formater la date
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier le token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare - Plateforme de Gestion de Cabinet Médical</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-heartbeat"></i>
                    <h1>MediCare</h1>
                </div>
                
                <?php if (isLoggedIn()): ?>
                <nav class="nav">
                    <ul>
                        <li><a href="../pages/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a></li>
                        
                        <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                        <li><a href="../pages/patients/patients.php" class="nav-link <?php echo strpos(basename($_SERVER['PHP_SELF']), 'patients') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Patients
                        </a></li>
                        <?php endif; ?>
                        
                        <li><a href="../pages/rendez_vous/rendez_vous.php" class="nav-link <?php echo strpos(basename($_SERVER['PHP_SELF']), 'rendez_vous') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-calendar"></i> Rendez-vous
                        </a></li>
                        
                        <?php if (hasRole('medecin') || hasRole('admin')): ?>
                        <li><a href="../pages/consultations/consultation_form.php" class="nav-link <?php echo strpos(basename($_SERVER['PHP_SELF']), 'consultations') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-stethoscope"></i> Consultations
                        </a></li>
                        <?php endif; ?>
                        
                        <?php if (hasRole('admin')): ?>
                        <li><a href="../pages/medecins/medecins.php" class="nav-link <?php echo strpos(basename($_SERVER['PHP_SELF']), 'medecins') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-user-md"></i> Médecins
                        </a></li>
                        <li><a href="../pages/medicaments/medicaments.php" class="nav-link <?php echo strpos(basename($_SERVER['PHP_SELF']), 'medicaments') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-pills"></i> Médicaments
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <!-- Barre de recherche avancée -->
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" 
                               id="global-search" 
                               class="search-input" 
                               placeholder="Rechercher (Ctrl+K)..." 
                               data-live-search="search-results">
                        <button class="search-btn" onclick="performGlobalSearch()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="search-results" class="search-dropdown" style="display: none;"></div>
                </div>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($_SESSION['user']['role']); ?></span>
                    </div>
                    <div class="user-actions">
                        <a href="../logout.php" class="btn btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <main class="main">
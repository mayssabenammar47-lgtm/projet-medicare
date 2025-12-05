<?php
/**
 * projet-medicare/pages/dashboard.php
 * Tableau de bord principal
 */

require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Obtenir les statistiques du tableau de bord
$stats = getDashboardStats();

// Obtenir les rendez-vous récents selon le rôle
$recentAppointments = [];
if (hasRole('medecin')) {
    // Rendez-vous du médecin connecté
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            WHERE rv.id_medecin = (SELECT id FROM medecins WHERE id_utilisateur = :user_id) 
            ORDER BY rv.date_heure DESC 
            LIMIT 5";
    $recentAppointments = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
} elseif (hasRole('secretaire') || hasRole('admin')) {
    // Tous les rendez-vous récents
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   u.nom as medecin_nom, m.specialite 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            JOIN medecins m ON rv.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id 
            ORDER BY rv.date_heure DESC 
            LIMIT 5";
    $recentAppointments = fetchAll($sql);
}

// Obtenir les patients récents (pour secrétaire et admin)
$recentPatients = [];
if (hasRole('secretaire') || hasRole('admin')) {
    $sql = "SELECT * FROM patients ORDER BY date_creation DESC LIMIT 5";
    $recentPatients = fetchAll($sql);
}
?>

<div class="container">
    <div class="dashboard-header">
        <h1>Tableau de bord</h1>
        <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        <?php if ($_SESSION['user_specialite']): ?>
        <p class="specialite">Spécialité: <?php echo htmlspecialchars($_SESSION['user_specialite']); ?></p>
        <?php endif; ?>
    </div>

    <!-- Statistiques principales -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color: var(--secondary-color);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number"><?php echo $stats['patients_total']; ?></div>
            <div class="stat-label">Patients total</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: var(--success-color);">
                <i class="fas fa-user-md"></i>
            </div>
            <div class="stat-number"><?php echo $stats['medecins_total']; ?></div>
            <div class="stat-label">Médecins</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: var(--warning-color);">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-number"><?php echo $stats['rdv_aujourd'hui']; ?></div>
            <div class="stat-label">RDV aujourd'hui</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: var(--danger-color);">
                <i class="fas fa-stethoscope"></i>
            </div>
            <div class="stat-number"><?php echo $stats['consultations_mois']; ?></div>
            <div class="stat-label">Consultations ce mois</div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="row">
            <!-- Rendez-vous récents -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar"></i> 
                            Rendez-vous récents
                        </h3>
                        <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                        <a href="rendez_vous/rendez_vous.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nouveau RDV
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentAppointments)): ?>
                        <p class="text-muted">Aucun rendez-vous récent.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Patient</th>
                                        <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                                        <th>Médecin</th>
                                        <?php endif; ?>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAppointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo formatDate($appointment['date_heure']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['patient_nom'] . ' ' . $appointment['patient_prenom']); ?>
                                        </td>
                                        <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                                        <td>
                                            <?php echo htmlspecialchars($appointment['medecin_nom']); ?>
                                            <?php if ($appointment['specialite']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($appointment['specialite']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge status-<?php echo $appointment['statut']; ?>">
                                                <?php 
                                                $statusLabels = [
                                                    'planifie' => 'Planifié',
                                                    'confirme' => 'Confirmé',
                                                    'annule' => 'Annulé',
                                                    'passe' => 'Passé'
                                                ];
                                                echo $statusLabels[$appointment['statut']] ?? $appointment['statut'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <?php if (hasRole('medecin') && $appointment['statut'] === 'planifie'): ?>
                                                <a href="../consultations/consultation_form.php?rdv_id=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-success btn-action" title="Consulter">
                                                    <i class="fas fa-stethoscope"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                                                <a href="../rendez_vous/rendez_vous.php?edit=<?php echo $appointment['id']; ?>" 
                                                   class="btn btn-primary btn-action" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Patients récents (secrétaire/admin) -->
                <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users"></i> Patients récents
                        </h3>
                        <a href="patients/patients.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Ajouter
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentPatients)): ?>
                        <p class="text-muted">Aucun patient récent.</p>
                        <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentPatients as $patient): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php if ($patient['email']) echo $patient['email'] . '<br>'; ?>
                                            <?php if ($patient['telephone']) echo $patient['telephone']; ?>
                                        </small>
                                    </div>
                                    <div class="actions">
                                        <a href="patients/patients_form.php?id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-primary btn-action" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions rapides -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bolt"></i> Actions rapides
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                            <a href="patients/patients_form.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Nouveau patient
                            </a>
                            <a href="rendez_vous/rendez_vous.php" class="btn btn-outline-success">
                                <i class="fas fa-calendar-plus"></i> Nouveau RDV
                            </a>
                            <?php endif; ?>
                            
                            <?php if (hasRole('medecin')): ?>
                            <a href="consultations/consultation_form.php" class="btn btn-outline-info">
                                <i class="fas fa-stethoscope"></i> Nouvelle consultation
                            </a>
                            <?php endif; ?>
                            
                            <?php if (hasRole('admin')): ?>
                            <a href="medecins/medecins.php" class="btn btn-outline-warning">
                                <i class="fas fa-user-md"></i> Gérer les médecins
                            </a>
                            <a href="medicaments/medicaments.php" class="btn btn-outline-secondary">
                                <i class="fas fa-pills"></i> Gérer les médicaments
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Alertes -->
                <?php if ($stats['medicaments_stock_faible'] > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle"></i> Alertes
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-pills"></i>
                            <strong><?php echo $stats['medicaments_stock_faible']; ?></strong> 
                            médicament(s) en stock faible
                        </div>
                        <?php if (hasRole('admin')): ?>
                        <a href="medicaments/medicaments.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-eye"></i> Voir les stocks
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.specialite {
    color: var(--gray-color);
    font-style: italic;
}

.row {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.col-md-8 {
    flex: 2;
    min-width: 300px;
}

.col-md-4 {
    flex: 1;
    min-width: 250px;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-planifie {
    background-color: var(--warning-color);
    color: white;
}

.status-confirme {
    background-color: var(--success-color);
    color: white;
}

.status-annule {
    background-color: var(--danger-color);
    color: white;
}

.status-passe {
    background-color: var(--gray-color);
    color: white;
}

.list-group-item {
    border: 1px solid var(--light-color);
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: var(--border-radius);
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .col-md-8,
    .col-md-4 {
        flex: 1;
        min-width: 100%;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
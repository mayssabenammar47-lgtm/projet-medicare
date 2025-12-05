<?php
/**
 * projet-medicare/pages/patients/patients.php
 * Liste des patients
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();
if (!hasRole('secretaire') && !hasRole('admin')) {
    redirectWithMessage('../dashboard.php', 'Accès non autorisé', 'error');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $patientId = $_POST['patient_id'] ?? 0;
                if ($patientId > 0) {
                    // Vérifier si le patient a des rendez-vous
                    $rdvCount = fetchOne("SELECT COUNT(*) as count FROM rendez_vous WHERE id_patient = :id", 
                                       ['id' => $patientId])['count'];
                    
                    if ($rdvCount > 0) {
                        redirectWithMessage('patients.php', 'Impossible de supprimer ce patient : il a des rendez-vous associés', 'error');
                    } else {
                        delete("DELETE FROM patients WHERE id = :id", ['id' => $patientId]);
                        redirectWithMessage('patients.php', 'Patient supprimé avec succès', 'success');
                    }
                }
                break;
        }
    }
}

// Recherche et filtrage
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Obtenir les patients
$patients = getPatients($search);

// Pagination
$totalPatients = count($patients);
$totalPages = ceil($totalPatients / $limit);
$patients = array_slice($patients, $offset, $limit);
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Gestion des patients</h1>
        <div class="page-actions">
            <a href="patients_form.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau patient
            </a>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="search-form">
                <div class="form-row">
                    <div class="form-col">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Rechercher par nom, prénom, email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des patients -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des patients 
                <?php if ($totalPatients > 0): ?>
                <span class="badge badge-secondary"><?php echo $totalPatients; ?></span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($patients)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h4>Aucun patient trouvé</h4>
                <p><?php echo $search ? 'Aucun patient ne correspond à votre recherche.' : 'Commencez par ajouter un nouveau patient.'; ?></p>
                <a href="patients_form.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un patient
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="patients-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contact</th>
                            <th>Date de naissance</th>
                            <th>Adresse</th>
                            <th>Date d'ajout</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']); ?></strong>
                            </td>
                            <td>
                                <?php if ($patient['email']): ?>
                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($patient['email']); ?></div>
                                <?php endif; ?>
                                <?php if ($patient['telephone']): ?>
                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($patient['telephone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($patient['date_naissance']) {
                                    $date = new DateTime($patient['date_naissance']);
                                    $today = new DateTime();
                                    $age = $today->diff($date)->y;
                                    echo formatDate($patient['date_naissance']) . ' (' . $age . ' ans)';
                                } else {
                                    echo '<span class="text-muted">Non spécifiée</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($patient['adresse']) {
                                    echo nl2br(htmlspecialchars(substr($patient['adresse'], 0, 50)));
                                    if (strlen($patient['adresse']) > 50) {
                                        echo '...';
                                    }
                                } else {
                                    echo '<span class="text-muted">Non spécifiée</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo formatDate($patient['date_creation']); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="patients_form.php?id=<?php echo $patient['id']; ?>" 
                                       class="btn btn-primary btn-action" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../rendez_vous/rendez_vous.php?patient_id=<?php echo $patient['id']; ?>" 
                                       class="btn btn-success btn-action" title="Prendre RDV">
                                        <i class="fas fa-calendar-plus"></i>
                                    </a>
                                    <a href="#" onclick="showPatientHistory(<?php echo $patient['id']; ?>)" 
                                       class="btn btn-info btn-action" title="Historique">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button type="button" onclick="confirmDeletePatient(<?php echo $patient['id']; ?>, '<?php echo addslashes($patient['nom'] . ' ' . $patient['prenom']); ?>')" 
                                            class="btn btn-danger btn-action" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $url = 'patients.php';
                if ($search) {
                    $url .= '?search=' . urlencode($search);
                }
                ?>
                
                <?php if ($page > 1): ?>
                <a href="<?php echo $url . ($search ? '&' : '?') . 'page=' . ($page - 1); ?>" 
                   class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
                <?php endif; ?>

                <span class="page-info">
                    Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                </span>

                <?php if ($page < $totalPages): ?>
                <a href="<?php echo $url . ($search ? '&' : '?') . 'page=' . ($page + 1); ?>" 
                   class="btn btn-secondary">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation de suppression</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer le patient <strong id="deletePatientName"></strong> ?</p>
            <p class="text-warning">Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="patient_id" id="deletePatientId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'historique du patient -->
<div id="historyModal" class="modal" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-history"></i> Historique du patient</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="patientHistoryContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Fermer</button>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-header h1 {
    color: var(--primary-color);
    margin: 0;
}

.search-form .input-group {
    display: flex;
    gap: 0.5rem;
}

.search-form .form-control {
    flex: 1;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--gray-color);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h4 {
    margin-bottom: 0.5rem;
    color: var(--dark-color);
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-info {
    color: var(--gray-color);
}

.modal-lg {
    max-width: 800px;
}

.badge {
    background-color: var(--secondary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form .input-group {
        flex-direction: column;
    }
    
    .pagination {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
function confirmDeletePatient(patientId, patientName) {
    document.getElementById('deletePatientId').value = patientId;
    document.getElementById('deletePatientName').textContent = patientName;
    document.getElementById('deleteModal').style.display = 'block';
}

function showPatientHistory(patientId) {
    document.getElementById('historyModal').style.display = 'block';
    
    // Charger l'historique via AJAX
    fetch(`../api/patient_history.php?id=${patientId}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.rendez_vous && data.rendez_vous.length > 0) {
                html += '<h4>Rendez-vous</h4>';
                html += '<div class="table-responsive">';
                html += '<table class="table">';
                html += '<thead><tr><th>Date</th><th>Médecin</th><th>Statut</th></tr></thead>';
                html += '<tbody>';
                
                data.rendez_vous.forEach(rdv => {
                    html += '<tr>';
                    html += '<td>' + rdv.date_heure + '</td>';
                    html += '<td>' + rdv.medecin_nom + '</td>';
                    html += '<td><span class="badge">' + rdv.statut + '</span></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            if (data.consultations && data.consultations.length > 0) {
                html += '<h4>Consultations</h4>';
                html += '<div class="table-responsive">';
                html += '<table class="table">';
                html += '<thead><tr><th>Date</th><th>Motif</th><th>Diagnostic</th></tr></thead>';
                html += '<tbody>';
                
                data.consultations.forEach(consultation => {
                    html += '<tr>';
                    html += '<td>' + consultation.date_consultation + '</td>';
                    html += '<td>' + (consultation.motif || '-') + '</td>';
                    html += '<td>' + (consultation.diagnostic || '-') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            if (!html) {
                html = '<p class="text-muted">Aucun historique trouvé pour ce patient.</p>';
            }
            
            document.getElementById('patientHistoryContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('patientHistoryContent').innerHTML = 
                '<p class="text-danger">Erreur lors du chargement de l\'historique.</p>';
        });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
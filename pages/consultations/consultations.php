<?php
/**
 * projet-medicare/pages/consultations/consultations.php
 * Liste des consultations
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();
if (!hasRole('medecin') && !hasRole('admin')) {
    redirectWithMessage('../dashboard.php', 'Accès non autorisé', 'error');
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $consultationId = $_POST['consultation_id'] ?? 0;
                if ($consultationId > 0) {
                    deleteConsultation($consultationId);
                    redirectWithMessage('consultations.php', 'Consultation supprimée avec succès', 'success');
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

// Obtenir les consultations
$consultations = getConsultations($search);

// Pagination
$totalConsultations = count($consultations);
$totalPages = ceil($totalConsultations / $limit);
$consultations = array_slice($consultations, $offset, $limit);
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-stethoscope"></i> Gestion des consultations</h1>
        <div class="page-actions">
            <a href="../rendez_vous/rendez_vous.php" class="btn btn-success">
                <i class="fas fa-calendar-plus"></i> Nouvelle consultation
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
                                   placeholder="Rechercher par patient, médecin..." 
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

    <!-- Liste des consultations -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des consultations 
                <?php if ($totalConsultations > 0): ?>
                <span class="badge badge-secondary"><?php echo $totalConsultations; ?></span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($consultations)): ?>
            <div class="empty-state">
                <i class="fas fa-file-medical"></i>
                <h4>Aucune consultation trouvée</h4>
                <p><?php echo $search ? 'Aucune consultation ne correspond à votre recherche.' : 'Commencez par créer une nouvelle consultation.'; ?></p>
                <a href="../rendez_vous/rendez_vous.php" class="btn btn-success">
                    <i class="fas fa-stethoscope"></i> Nouvelle consultation
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="consultations-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <?php if (!hasRole('medecin')): ?>
                            <th>Médecin</th>
                            <?php endif; ?>
                            <th>Motif</th>
                            <th>Diagnostic</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                        <tr>
                            <td>
                                <strong><?php echo formatDate($consultation['date_consultation']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($consultation['patient_nom'] . ' ' . $consultation['patient_prenom']); ?></strong>
                            </td>
                            <?php if (!hasRole('medecin')): ?>
                            <td>
                                <?php echo htmlspecialchars($consultation['medecin_nom'] . ' ' . $consultation['medecin_prenom']); ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php 
                                if ($consultation['motif']) {
                                    echo nl2br(htmlspecialchars(substr($consultation['motif'], 0, 50)));
                                    if (strlen($consultation['motif']) > 50) {
                                        echo '...';
                                    }
                                } else {
                                    echo '<span class="text-muted">Non spécifié</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($consultation['diagnostic']) {
                                    echo nl2br(htmlspecialchars(substr($consultation['diagnostic'], 0, 50)));
                                    if (strlen($consultation['diagnostic']) > 50) {
                                        echo '...';
                                    }
                                } else {
                                    echo '<span class="text-muted">Non spécifié</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="consultation_form.php?id=<?php echo $consultation['id']; ?>" 
                                       class="btn btn-primary btn-action" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" onclick="showConsultationDetails(<?php echo $consultation['id']; ?>)" 
                                       class="btn btn-info btn-action" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasRole('admin')): ?>
                                    <button type="button" onclick="confirmDeleteConsultation(<?php echo $consultation['id']; ?>)" 
                                            class="btn btn-danger btn-action" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
                $url = 'consultations.php';
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
            <p>Êtes-vous sûr de vouloir supprimer cette consultation ?</p>
            <p class="text-warning">Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="consultation_id" id="deleteConsultationId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal des détails de consultation -->
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-file-medical"></i> Détails de la consultation</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="consultationDetailsContent">
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
function confirmDeleteConsultation(consultationId) {
    document.getElementById('deleteConsultationId').value = consultationId;
    document.getElementById('deleteModal').style.display = 'block';
}

function showConsultationDetails(consultationId) {
    document.getElementById('detailsModal').style.display = 'block';
    
    // Charger les détails via AJAX
    fetch(`../api/consultation_details.php?id=${consultationId}`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            html += '<div class="consultation-details">';
            html += '<div class="row">';
            html += '<div class="col-md-6">';
            html += '<h4>Informations générales</h4>';
            html += '<p><strong>Date:</strong> ' + data.date_consultation + '</p>';
            html += '<p><strong>Patient:</strong> ' + data.patient_nom + ' ' + data.patient_prenom + '</p>';
            html += '<p><strong>Médecin:</strong> ' + data.medecin_nom + ' ' + data.medecin_prenom + '</p>';
            html += '</div>';
            html += '<div class="col-md-6">';
            html += '<h4>Constantes vitales</h4>';
            if (data.poids) html += '<p><strong>Poids:</strong> ' + data.poids + ' kg</p>';
            if (data.taille) html += '<p><strong>Taille:</strong> ' + data.taille + ' cm</p>';
            if (data.tension) html += '<p><strong>Tension:</strong> ' + data.tension + '</p>';
            if (data.temperature) html += '<p><strong>Température:</strong> ' + data.temperature + '°C</p>';
            if (data.pouls) html += '<p><strong>Pouls:</strong> ' + data.pouls + ' bpm</p>';
            html += '</div>';
            html += '</div>';
            
            if (data.motif) {
                html += '<h4>Motif de la consultation</h4>';
                html += '<p>' + data.motif + '</p>';
            }
            
            if (data.diagnostic) {
                html += '<h4>Diagnostic</h4>';
                html += '<p>' + data.diagnostic + '</p>';
            }
            
            if (data.observations) {
                html += '<h4>Observations</h4>';
                html += '<p>' + data.observations + '</p>';
            }
            
            if (data.prescriptions && data.prescriptions.length > 0) {
                html += '<h4>Prescriptions</h4>';
                html += '<div class="table-responsive">';
                html += '<table class="table">';
                html += '<thead><tr><th>Médicament</th><th>Posologie</th><th>Durée</th><th>Instructions</th></tr></thead>';
                html += '<tbody>';
                
                data.prescriptions.forEach(prescription => {
                    html += '<tr>';
                    html += '<td>' + prescription.medicament_nom + '</td>';
                    html += '<td>' + (prescription.posologie || '-') + '</td>';
                    html += '<td>' + (prescription.duree || '-') + '</td>';
                    html += '<td>' + (prescription.instructions || '-') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            html += '</div>';
            
            document.getElementById('consultationDetailsContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('consultationDetailsContent').innerHTML = 
                '<p class="text-danger">Erreur lors du chargement des détails.</p>';
        });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
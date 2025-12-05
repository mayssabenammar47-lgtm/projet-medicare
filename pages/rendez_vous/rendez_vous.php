<?php
/**
 * projet-medicare/pages/rendez_vous/rendez_vous.php
 * Gestion des rendez-vous
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $rdvData = [
                    'id_patient' => intval($_POST['id_patient']),
                    'id_medecin' => intval($_POST['id_medecin']),
                    'date_heure' => $_POST['date_heure'],
                    'statut' => $_POST['statut'] ?? 'planifie',
                    'notes' => cleanInput($_POST['notes'] ?? '')
                ];
                
                // Validation
                $errors = [];
                
                if (empty($rdvData['id_patient'])) {
                    $errors[] = 'Le patient est obligatoire';
                }
                
                if (empty($rdvData['id_medecin'])) {
                    $errors[] = 'Le médecin est obligatoire';
                }
                
                if (empty($rdvData['date_heure'])) {
                    $errors[] = 'La date et heure sont obligatoires';
                } elseif (!validateDate($rdvData['date_heure'], 'Y-m-d H:i')) {
                    $errors[] = 'La date et heure ne sont pas valides';
                }
                
                if (empty($errors)) {
                    if ($_POST['action'] === 'create') {
                        createRendezVous($rdvData);
                        redirectWithMessage('rendez_vous.php', 'Rendez-vous créé avec succès', 'success');
                    } else {
                        $rdvId = intval($_POST['rdv_id']);
                        update("UPDATE rendez_vous SET id_patient = :id_patient, id_medecin = :id_medecin, 
                               date_heure = :date_heure, statut = :statut, notes = :notes 
                               WHERE id = :id", 
                               array_merge($rdvData, ['id' => $rdvId]));
                        redirectWithMessage('rendez_vous.php', 'Rendez-vous modifié avec succès', 'success');
                    }
                } else {
                    $error_message = implode('<br>', $errors);
                }
                break;
                
            case 'delete':
                $rdvId = intval($_POST['rdv_id']);
                delete("DELETE FROM rendez_vous WHERE id = :id", ['id' => $rdvId]);
                redirectWithMessage('rendez_vous.php', 'Rendez-vous supprimé avec succès', 'success');
                break;
        }
    }
}

// Récupérer le rendez-vous à modifier
$editRdv = null;
$editMode = false;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $rdvId = intval($_GET['edit']);
    $editRdv = fetchOne("SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom 
                        FROM rendez_vous rv 
                        JOIN patients p ON rv.id_patient = p.id 
                        WHERE rv.id = :id", ['id' => $rdvId]);
    
    if (!$editRdv) {
        redirectWithMessage('rendez_vous.php', 'Rendez-vous non trouvé', 'error');
    }
    
    $editMode = true;
}

// Pré-sélection du patient si passé en paramètre
$selectedPatientId = $_GET['patient_id'] ?? 0;

// Obtenir les données pour les formulaires
$patients = fetchAll("SELECT id, nom, prenom FROM patients ORDER BY nom, prenom");
$medecins = getMedecins();

// Obtenir les rendez-vous selon le rôle
$rendezVous = [];
if (hasRole('medecin')) {
    // Rendez-vous du médecin connecté
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            WHERE rv.id_medecin = (SELECT id FROM medecins WHERE id_utilisateur = :user_id) 
            ORDER BY rv.date_heure DESC";
    $rendezVous = fetchAll($sql, ['user_id' => $_SESSION['user']['id']]);
} else {
    // Tous les rendez-vous pour secrétaire et admin
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   u.nom as medecin_nom, m.specialite 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            JOIN medecins m ON rv.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id 
            ORDER BY rv.date_heure DESC";
    $rendezVous = fetchAll($sql);
}
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-calendar"></i> Gestion des rendez-vous</h1>
        <div class="page-actions">
            <a href="calendrier.php" class="btn btn-info">
                <i class="fas fa-calendar-alt"></i> Vue calendrier
            </a>
            <?php if (!$editMode): ?>
            <button type="button" onclick="showAddForm()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau rendez-vous
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout/modification -->
    <?php if ($editMode || isset($_GET['add'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus'; ?>"></i>
                <?php echo $editMode ? 'Modifier' : 'Ajouter'; ?> un rendez-vous
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" data-validate>
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'create'; ?>">
                <?php if ($editMode): ?>
                <input type="hidden" name="rdv_id" value="<?php echo $editRdv['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="id_patient" class="form-label">
                                Patient <span class="required">*</span>
                            </label>
                            <select id="id_patient" name="id_patient" class="form-control" required>
                                <option value="">Sélectionner un patient</option>
                                <?php foreach ($patients as $patient): ?>
                                <option value="<?php echo $patient['id']; ?>" 
                                        <?php echo (($editMode && $editRdv['id_patient'] == $patient['id']) || 
                                                  (!$editMode && $selectedPatientId == $patient['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patient['nom'] . ' ' . $patient['prenom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="id_medecin" class="form-label">
                                Médecin <span class="required">*</span>
                            </label>
                            <select id="id_medecin" name="id_medecin" class="form-control" required>
                                <option value="">Sélectionner un médecin</option>
                                <?php foreach ($medecins as $medecin): ?>
                                <option value="<?php echo $medecin['id']; ?>" 
                                        <?php echo ($editMode && $editRdv['id_medecin'] == $medecin['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($medecin['nom']); ?>
                                    <?php if ($medecin['specialite']): ?>
                                    (<?php echo htmlspecialchars($medecin['specialite']); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="date_heure" class="form-label">
                                Date et heure <span class="required">*</span>
                            </label>
                            <input type="datetime-local" id="date_heure" name="date_heure" 
                                   class="form-control" required
                                   value="<?php echo $editMode ? date('Y-m-d\TH:i', strtotime($editRdv['date_heure'])) : ''; ?>"
                                   min="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="statut" class="form-label">Statut</label>
                            <select id="statut" name="statut" class="form-control">
                                <option value="planifie" <?php echo ($editMode && $editRdv['statut'] === 'planifie') ? 'selected' : ''; ?>>
                                    Planifié
                                </option>
                                <option value="confirme" <?php echo ($editMode && $editRdv['statut'] === 'confirme') ? 'selected' : ''; ?>>
                                    Confirmé
                                </option>
                                <option value="annule" <?php echo ($editMode && $editRdv['statut'] === 'annule') ? 'selected' : ''; ?>>
                                    Annulé
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" 
                              placeholder="Notes supplémentaires..."><?php echo htmlspecialchars($editRdv['notes'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $editMode ? 'Mettre à jour' : 'Créer'; ?> le rendez-vous
                    </button>
                    <a href="rendez_vous.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste des rendez-vous -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des rendez-vous
                <?php if (count($rendezVous) > 0): ?>
                <span class="badge badge-secondary"><?php echo count($rendezVous); ?></span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($rendezVous)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h4>Aucun rendez-vous</h4>
                <p>Commencez par ajouter un nouveau rendez-vous.</p>
                <button type="button" onclick="showAddForm()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un rendez-vous
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="rendezvous-table">
                    <thead>
                        <tr>
                            <th>Date et heure</th>
                            <th>Patient</th>
                            <?php if (!hasRole('medecin')): ?>
                            <th>Médecin</th>
                            <?php endif; ?>
                            <th>Statut</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rendezVous as $rdv): ?>
                        <tr class="<?php echo $rdv['statut'] === 'annule' ? 'cancelled' : ''; ?>">
                            <td>
                                <strong><?php echo formatDate($rdv['date_heure']); ?></strong>
                                <?php 
                                $rdvDate = new DateTime($rdv['date_heure']);
                                $now = new DateTime();
                                if ($rdvDate < $now && $rdv['statut'] !== 'annule') {
                                    echo '<br><small class="text-warning">Passé</small>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($rdv['patient_nom'] . ' ' . $rdv['patient_prenom']); ?>
                            </td>
                            <?php if (!hasRole('medecin')): ?>
                            <td>
                                <?php echo htmlspecialchars($rdv['medecin_nom']); ?>
                                <?php if ($rdv['specialite']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($rdv['specialite']); ?></small>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <span class="badge status-<?php echo $rdv['statut']; ?>">
                                    <?php 
                                    $statusLabels = [
                                        'planifie' => 'Planifié',
                                        'confirme' => 'Confirmé',
                                        'annule' => 'Annulé',
                                        'passe' => 'Passé'
                                    ];
                                    echo $statusLabels[$rdv['statut']] ?? $rdv['statut'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($rdv['notes']) {
                                    echo nl2br(htmlspecialchars(substr($rdv['notes'], 0, 50)));
                                    if (strlen($rdv['notes']) > 50) {
                                        echo '...';
                                    }
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php if (hasRole('medecin') && $rdv['statut'] === 'planifie'): ?>
                                    <a href="../consultations/consultation_form.php?rdv_id=<?php echo $rdv['id']; ?>" 
                                       class="btn btn-success btn-action" title="Consulter">
                                        <i class="fas fa-stethoscope"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasRole('secretaire') || hasRole('admin')): ?>
                                    <a href="rendez_vous.php?edit=<?php echo $rdv['id']; ?>" 
                                       class="btn btn-primary btn-action" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" onclick="confirmDeleteRdv(<?php echo $rdv['id']; ?>)" 
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
            <p>Êtes-vous sûr de vouloir supprimer ce rendez-vous ?</p>
            <p class="text-warning">Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="rdv_id" id="deleteRdvId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
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

.page-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.required {
    color: var(--danger-color);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--light-color);
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

.cancelled {
    opacity: 0.6;
    text-decoration: line-through;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-actions {
        justify-content: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showAddForm() {
    window.location.href = 'rendez_vous.php?add=1';
}

function confirmDeleteRdv(rdvId) {
    document.getElementById('deleteRdvId').value = rdvId;
    document.getElementById('deleteModal').style.display = 'block';
}
</script>

<?php require_once '../../includes/footer.php'; ?>
<?php
/**
 * projet-medicare/pages/patients/patients_form.php
 * Formulaire d'ajout/modification de patient
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();
if (!hasRole('secretaire') && !hasRole('admin')) {
    redirectWithMessage('../dashboard.php', 'Accès non autorisé', 'error');
}

// Récupérer le patient à modifier
$patient = null;
$editMode = false;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $patientId = intval($_GET['id']);
    $patient = getPatientById($patientId);
    
    if (!$patient) {
        redirectWithMessage('patients.php', 'Patient non trouvé', 'error');
    }
    
    $editMode = true;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientData = [
        'nom' => cleanInput($_POST['nom'] ?? ''),
        'prenom' => cleanInput($_POST['prenom'] ?? ''),
        'date_naissance' => $_POST['date_naissance'] ?? '',
        'telephone' => cleanInput($_POST['telephone'] ?? ''),
        'email' => cleanInput($_POST['email'] ?? ''),
        'adresse' => cleanInput($_POST['adresse'] ?? '')
    ];
    
    // Validation
    $errors = [];
    
    if (empty($patientData['nom'])) {
        $errors[] = 'Le nom est obligatoire';
    }
    
    if (empty($patientData['prenom'])) {
        $errors[] = 'Le prénom est obligatoire';
    }
    
    if (!empty($patientData['date_naissance']) && !validateDate($patientData['date_naissance'])) {
        $errors[] = 'La date de naissance n\'est pas valide';
    }
    
    if (!empty($patientData['email']) && !validateEmail($patientData['email'])) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    
    // Vérifier l'unicité de l'email en cas de création
    if (!$editMode && !empty($patientData['email'])) {
        $existingPatient = fetchOne("SELECT id FROM patients WHERE email = :email", 
                                    ['email' => $patientData['email']]);
        if ($existingPatient) {
            $errors[] = 'Un patient avec cet email existe déjà';
        }
    }
    
    if (empty($errors)) {
        if ($editMode) {
            $patientData['id'] = $patientId;
        }
        
        $patientId = savePatient($patientData);
        
        $message = $editMode ? 'Patient modifié avec succès' : 'Patient ajouté avec succès';
        redirectWithMessage('patients.php', $message, 'success');
    }
}
?>

<div class="container">
    <div class="page-header">
        <h1>
            <i class="fas fa-user-<?php echo $editMode ? 'edit' : 'plus'; ?>"></i>
            <?php echo $editMode ? 'Modifier un patient' : 'Ajouter un patient'; ?>
        </h1>
        <div class="page-actions">
            <a href="patients.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    <?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Erreurs:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="" data-validate>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nom" class="form-label">
                                Nom <span class="required">*</span>
                            </label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['nom'] ?? ''); ?>" 
                                   required data-error="Le nom est obligatoire">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="prenom" class="form-label">
                                Prénom <span class="required">*</span>
                            </label>
                            <input type="text" id="prenom" name="prenom" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['prenom'] ?? ''); ?>" 
                                   required data-error="Le prénom est obligatoire">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" id="date_naissance" name="date_naissance" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['date_naissance'] ?? ''); ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['telephone'] ?? ''); ?>"
                                   placeholder="06 12 34 56 78">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($patient['email'] ?? ''); ?>"
                           placeholder="patient@email.com">
                </div>

                <div class="form-group">
                    <label for="adresse" class="form-label">Adresse</label>
                    <textarea id="adresse" name="adresse" class="form-control" rows="3" 
                              placeholder="Numéro, rue, code postal, ville..."><?php echo htmlspecialchars($patient['adresse'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $editMode ? 'Mettre à jour' : 'Ajouter'; ?> le patient
                    </button>
                    <a href="patients.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editMode && $patient): ?>
    <!-- Informations supplémentaires -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-info-circle"></i> Informations supplémentaires
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Date d'ajout:</strong> <?php echo formatDate($patient['date_creation']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Âge:</strong> 
                        <?php 
                        if ($patient['date_naissance']) {
                            $date = new DateTime($patient['date_naissance']);
                            $today = new DateTime();
                            echo $today->diff($date)->y . ' ans';
                        } else {
                            echo 'Non spécifié';
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="mt-3">
                <h4>Actions rapides</h4>
                <div class="actions">
                    <a href="../rendez_vous/rendez_vous.php?patient_id=<?php echo $patient['id']; ?>" 
                       class="btn btn-success">
                        <i class="fas fa-calendar-plus"></i> Prendre un RDV
                    </a>
                    <a href="#" onclick="showPatientHistory(<?php echo $patient['id']; ?>)" 
                       class="btn btn-info">
                        <i class="fas fa-history"></i> Voir l'historique
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
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

.row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.col-md-6 {
    flex: 1;
    min-width: 250px;
}

.modal-lg {
    max-width: 800px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .row {
        flex-direction: column;
    }
}
</style>

<script>
function showPatientHistory(patientId) {
    document.getElementById('historyModal').style.display = 'block';
    
    // Charger l'historique via AJAX
    fetch(`../../api/patient_history.php?id=${patientId}`)
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
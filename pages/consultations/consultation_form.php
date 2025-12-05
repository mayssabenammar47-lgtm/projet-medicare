<?php
/**
 * projet-medicare/pages/consultations/consultation_form.php
 * Formulaire de consultation et prescription
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();
if (!hasRole('medecin') && !hasRole('admin')) {
    redirectWithMessage('../dashboard.php', 'Accès non autorisé', 'error');
}

// Récupérer le rendez-vous si passé en paramètre
$rendezVous = null;
$consultation = null;
$editMode = false;

if (isset($_GET['rdv_id']) && !empty($_GET['rdv_id'])) {
    $rdvId = intval($_GET['rdv_id']);
    $rendezVous = fetchOne("SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                            p.date_naissance, p.telephone, p.email,
                            u.nom as medecin_nom, m.specialite 
                            FROM rendez_vous rv 
                            JOIN patients p ON rv.id_patient = p.id 
                            JOIN medecins m ON rv.id_medecin = m.id 
                            JOIN utilisateurs u ON m.id_utilisateur = u.id 
                            WHERE rv.id = :id", ['id' => $rdvId]);
    
    if (!$rendezVous) {
        redirectWithMessage('../rendez_vous/rendez_vous.php', 'Rendez-vous non trouvé', 'error');
    }
    
    // Vérifier si une consultation existe déjà pour ce rendez-vous
    $consultation = fetchOne("SELECT * FROM consultations WHERE id_patient = :id_patient AND date_consultation = :date_consultation", 
                             ['id_patient' => $rendezVous['id_patient'], 'date_consultation' => $rendezVous['date_heure']]);
    if ($consultation) {
        $editMode = true;
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultationData = [
        'id_medecin' => intval($_POST['id_medecin']),
        'id_patient' => intval($_POST['id_patient']),
        'date_consultation' => $_POST['date_consultation'],
        'motif' => cleanInput($_POST['motif'] ?? ''),
        'symptomes' => cleanInput($_POST['symptomes'] ?? ''),
        'diagnostic' => cleanInput($_POST['diagnostic'] ?? ''),
        'traitement' => cleanInput($_POST['traitement'] ?? ''),
        'notes' => cleanInput($_POST['notes'] ?? '')
    ];
    
    // Validation
    $errors = [];
    
    if (empty($consultationData['id_medecin'])) {
        $errors[] = 'Le médecin est obligatoire';
    }
    
    if (empty($consultationData['id_patient'])) {
        $errors[] = 'Le patient est obligatoire';
    }
    
    if (empty($consultationData['date_consultation'])) {
        $errors[] = 'La date et heure sont obligatoires';
    }
    
    if (empty($errors)) {
        // Démarrer une transaction
        global $pdo;
        $pdo->beginTransaction();
        
        try {
            if ($editMode) {
                $consultationData['id'] = $consultation['id'];
                $consultationId = saveConsultation($consultationData);
            } else {
                $consultationId = saveConsultation($consultationData);
                
                // Mettre à jour le statut du rendez-vous si applicable
                if (isset($rendezVous['id'])) {
                    update("UPDATE rendez_vous SET statut = 'passe' WHERE id = :id", 
                           ['id' => $rendezVous['id']]);
                }
            }
            
            // Traiter les prescriptions
            if (isset($_POST['prescriptions']) && is_array($_POST['prescriptions'])) {
                // Supprimer les anciennes prescriptions si en mode édition
                if ($editMode) {
                    delete("DELETE FROM prescriptions WHERE id_consultation = :id", 
                           ['id' => $consultationId]);
                }
                
                foreach ($_POST['prescriptions'] as $prescription) {
                    if (!empty($prescription['id_medicament'])) {
                        $sql = "INSERT INTO prescriptions (id_consultation, id_medicament, posologie, duree, instructions) 
                                VALUES (:id_consultation, :id_medicament, :posologie, :duree, :instructions)";
                        
                        insert($sql, [
                            'id_consultation' => $consultationId,
                            'id_medicament' => intval($prescription['id_medicament']),
                            'posologie' => cleanInput($prescription['posologie'] ?? ''),
                            'duree' => cleanInput($prescription['duree'] ?? ''),
                            'instructions' => cleanInput($prescription['instructions'] ?? '')
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            
            $message = $editMode ? 'Consultation modifiée avec succès' : 'Consultation enregistrée avec succès';
            redirectWithMessage('../rendez_vous/rendez_vous.php', $message, 'success');
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Une erreur est survenue: ' . $e->getMessage();
        }
    }
}

// Obtenir les données pour les formulaires
$medicaments = getMedicaments();
$medecins = getMedecins();

// Obtenir les prescriptions existantes si en mode édition
$prescriptions = [];
if ($editMode && $consultation) {
    $prescriptions = fetchAll("SELECT p.*, m.nom as medicament_nom 
                              FROM prescriptions p 
                              JOIN medicaments m ON p.id_medicament = m.id 
                              WHERE p.id_consultation = :id", 
                              ['id' => $consultation['id']]);
}
?>

<div class="container">
    <div class="page-header">
        <h1>
            <i class="fas fa-stethoscope"></i>
            <?php echo $editMode ? 'Modifier' : 'Nouvelle'; ?> consultation
        </h1>
        <div class="page-actions">
            <a href="../rendez_vous/rendez_vous.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour aux rendez-vous
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

    <?php if ($rendezVous): ?>
    <!-- Informations du rendez-vous -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-info-circle"></i> Informations du rendez-vous
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($rendezVous['patient_nom'] . ' ' . $rendezVous['patient_prenom']); ?></p>
                    <?php if ($rendezVous['date_naissance']): ?>
                    <?php 
                    $date = new DateTime($rendezVous['date_naissance']);
                    $today = new DateTime();
                    $age = $today->diff($date)->y;
                    ?>
                    <p><strong>Âge:</strong> <?php echo $age; ?> ans</p>
                    <?php endif; ?>
                    <?php if ($rendezVous['telephone']): ?>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($rendezVous['telephone']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <p><strong>Médecin:</strong> <?php echo htmlspecialchars($rendezVous['medecin_nom']); ?></p>
                    <?php if ($rendezVous['specialite']): ?>
                    <p><strong>Spécialité:</strong> <?php echo htmlspecialchars($rendezVous['specialite']); ?></p>
                    <?php endif; ?>
                    <p><strong>Date du RDV:</strong> <?php echo formatDate($rendezVous['date_heure']); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulaire de consultation -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-notes-medical"></i> Détails de la consultation
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" data-validate id="consultationForm">
                <input type="hidden" name="id_medecin" value="<?php echo $rendezVous['id_medecin']; ?>">
                <input type="hidden" name="id_patient" value="<?php echo $rendezVous['id_patient']; ?>">
                <input type="hidden" name="date_consultation" value="<?php echo date('Y-m-d H:i:s'); ?>">

                <!-- Constantes vitales -->
                <div class="section-title">
                    <h4><i class="fas fa-heartbeat"></i> Constantes vitales</h4>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="poids" class="form-label">Poids (kg)</label>
                            <input type="number" id="poids" name="poids" class="form-control" 
                                   step="0.1" min="0" value="<?php echo htmlspecialchars($consultation['poids'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="taille" class="form-label">Taille (cm)</label>
                            <input type="number" id="taille" name="taille" class="form-control" 
                                   step="0.1" min="0" value="<?php echo htmlspecialchars($consultation['taille'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="tension" class="form-label">Tension (mmHg)</label>
                            <input type="text" id="tension" name="tension" class="form-control" 
                                   placeholder="120/80" value="<?php echo htmlspecialchars($consultation['tension'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="temperature" class="form-label">Température (°C)</label>
                            <input type="number" id="temperature" name="temperature" class="form-control" 
                                   step="0.1" min="35" max="42" value="<?php echo htmlspecialchars($consultation['temperature'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="pouls" class="form-label">Pouls (bpm)</label>
                            <input type="number" id="pouls" name="pouls" class="form-control" 
                                   min="40" max="200" value="<?php echo htmlspecialchars($consultation['pouls'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Motif et diagnostic -->
                <div class="section-title">
                    <h4><i class="fas fa-file-medical"></i> Diagnostic</h4>
                </div>

                <div class="form-group">
                    <label for="motif" class="form-label">Motif de la consultation</label>
                    <textarea id="motif" name="motif" class="form-control" rows="3" 
                              placeholder="Motif principal de la consultation..."><?php echo htmlspecialchars($consultation['motif'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="diagnostic" class="form-label">Diagnostic</label>
                    <textarea id="diagnostic" name="diagnostic" class="form-control" rows="4" 
                              placeholder="Diagnostic détaillé..." required><?php echo htmlspecialchars($consultation['diagnostic'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="symptomes" class="form-label">Symptômes</label>
                    <textarea id="symptomes" name="symptomes" class="form-control" rows="3" 
                              placeholder="Symptômes décrits par le patient..."><?php echo htmlspecialchars($consultation['symptomes'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="traitement" class="form-label">Traitement</label>
                    <textarea id="traitement" name="traitement" class="form-control" rows="3" 
                              placeholder="Traitement prescrit..."><?php echo htmlspecialchars($consultation['traitement'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" 
                              placeholder="Notes supplémentaires..."><?php echo htmlspecialchars($consultation['notes'] ?? ''); ?></textarea>
                </div>

                <!-- Prescriptions -->
                <div class="section-title">
                    <h4><i class="fas fa-pills"></i> Prescriptions</h4>
                    <button type="button" onclick="addPrescription()" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Ajouter un médicament
                    </button>
                </div>

                <div id="prescriptions-container">
                    <?php if (empty($prescriptions)): ?>
                    <!-- Prescription vide par défaut -->
                    <div class="prescription-item">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Médicament</label>
                                    <select name="prescriptions[0][id_medicament]" class="form-control medicament-select">
                                        <option value="">Sélectionner un médicament</option>
                                        <?php foreach ($medicaments as $medicament): ?>
                                        <option value="<?php echo $medicament['id']; ?>">
                                            <?php echo htmlspecialchars($medicament['nom']); ?>
                                            <?php if ($medicament['stock'] < 10): ?>
                                            <span class="text-warning">(Stock: <?php echo $medicament['stock']; ?>)</span>
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Posologie</label>
                                    <input type="text" name="prescriptions[0][posologie]" class="form-control" 
                                           placeholder="ex: 1 comprimé 3 fois/jour">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Durée</label>
                                    <input type="text" name="prescriptions[0][duree]" class="form-control" 
                                           placeholder="ex: 7 jours">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Instructions</label>
                                    <input type="text" name="prescriptions[0][instructions]" class="form-control" 
                                           placeholder="ex: Pendant les repas">
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="removePrescription(this)" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                    <?php else: ?>
                    <?php foreach ($prescriptions as $index => $prescription): ?>
                    <div class="prescription-item">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Médicament</label>
                                    <select name="prescriptions[<?php echo $index; ?>][id_medicament]" class="form-control medicament-select">
                                        <option value="">Sélectionner un médicament</option>
                                        <?php foreach ($medicaments as $medicament): ?>
                                        <option value="<?php echo $medicament['id']; ?>" 
                                                <?php echo $prescription['id_medicament'] == $medicament['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($medicament['nom']); ?>
                                            <?php if ($medicament['stock'] < 10): ?>
                                            <span class="text-warning">(Stock: <?php echo $medicament['stock']; ?>)</span>
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Posologie</label>
                                    <input type="text" name="prescriptions[<?php echo $index; ?>][posologie]" 
                                           class="form-control" value="<?php echo htmlspecialchars($prescription['posologie']); ?>"
                                           placeholder="ex: 1 comprimé 3 fois/jour">
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Durée</label>
                                    <input type="text" name="prescriptions[<?php echo $index; ?>][duree]" 
                                           class="form-control" value="<?php echo htmlspecialchars($prescription['duree']); ?>"
                                           placeholder="ex: 7 jours">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label class="form-label">Instructions</label>
                                    <input type="text" name="prescriptions[<?php echo $index; ?>][instructions]" 
                                           class="form-control" value="<?php echo htmlspecialchars($prescription['instructions']); ?>"
                                           placeholder="ex: Pendant les repas">
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="removePrescription(this)" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $editMode ? 'Mettre à jour' : 'Enregistrer'; ?> la consultation
                    </button>
                    <a href="../rendez_vous/rendez_vous.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
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

.row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.col-md-6 {
    flex: 1;
    min-width: 250px;
}

.section-title {
    margin: 2rem 0 1rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--light-color);
}

.section-title h4 {
    color: var(--primary-color);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.prescription-item {
    border: 1px solid var(--light-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #fafafa;
}

.prescription-item .form-row {
    margin-bottom: 1rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--light-color);
}

.text-warning {
    color: var(--warning-color);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .row {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .section-title h4 {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>

<script>
let prescriptionCount = <?php echo max(count($prescriptions), 1); ?>;

function addPrescription() {
    const container = document.getElementById('prescriptions-container');
    const prescriptionDiv = document.createElement('div');
    prescriptionDiv.className = 'prescription-item';
    
    prescriptionDiv.innerHTML = `
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Médicament</label>
                    <select name="prescriptions[${prescriptionCount}][id_medicament]" class="form-control medicament-select">
                        <option value="">Sélectionner un médicament</option>
                        <?php foreach ($medicaments as $medicament): ?>
                        <option value="<?php echo $medicament['id']; ?>">
                            <?php echo htmlspecialchars($medicament['nom']); ?>
                            <?php if ($medicament['stock'] < 10): ?>
                            <span class="text-warning">(Stock: <?php echo $medicament['stock']; ?>)</span>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Posologie</label>
                    <input type="text" name="prescriptions[${prescriptionCount}][posologie]" class="form-control" 
                           placeholder="ex: 1 comprimé 3 fois/jour">
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Durée</label>
                    <input type="text" name="prescriptions[${prescriptionCount}][duree]" class="form-control" 
                           placeholder="ex: 7 jours">
                </div>
            </div>
            <div class="form-col">
                <div class="form-group">
                    <label class="form-label">Instructions</label>
                    <input type="text" name="prescriptions[${prescriptionCount}][instructions]" class="form-control" 
                           placeholder="ex: Pendant les repas">
                </div>
            </div>
        </div>
        <button type="button" onclick="removePrescription(this)" class="btn btn-danger btn-sm">
            <i class="fas fa-trash"></i> Supprimer
        </button>
    `;
    
    container.appendChild(prescriptionDiv);
    prescriptionCount++;
}

function removePrescription(button) {
    const prescriptionItem = button.closest('.prescription-item');
    prescriptionItem.remove();
}

// Calculer l'IMC automatiquement
document.addEventListener('DOMContentLoaded', function() {
    const poidsInput = document.getElementById('poids');
    const tailleInput = document.getElementById('taille');
    
    function calculateIMC() {
        const poids = parseFloat(poidsInput.value);
        const taille = parseFloat(tailleInput.value);
        
        if (poids > 0 && taille > 0) {
            const imc = poids / Math.pow(taille / 100, 2);
            // Vous pouvez afficher l'IMC quelque part si nécessaire
            console.log('IMC:', imc.toFixed(2));
        }
    }
    
    if (poidsInput && tailleInput) {
        poidsInput.addEventListener('input', calculateIMC);
        tailleInput.addEventListener('input', calculateIMC);
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
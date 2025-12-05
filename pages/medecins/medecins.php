<?php
/**
 * projet-medicare/pages/medecins/medecins.php
 * Gestion des médecins (réservé à l'admin)
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();
requireRole('admin');

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $userData = [
                    'nom' => cleanInput($_POST['nom'] ?? ''),
                    'email' => cleanInput($_POST['email'] ?? ''),
                    'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
                    'role' => 'medecin'
                ];
                
                $medecinData = [
                    'specialite' => cleanInput($_POST['specialite'] ?? ''),
                    'telephone' => cleanInput($_POST['telephone'] ?? ''),
                    'adresse' => cleanInput($_POST['adresse'] ?? '')
                ];
                
                // Validation
                $errors = [];
                
                if (empty($userData['nom'])) {
                    $errors[] = 'Le nom est obligatoire';
                }
                
                if (empty($userData['email'])) {
                    $errors[] = 'L\'email est obligatoire';
                } elseif (!validateEmail($userData['email'])) {
                    $errors[] = 'L\'email n\'est pas valide';
                }
                
                if (empty($userData['mot_de_passe'])) {
                    $errors[] = 'Le mot de passe est obligatoire';
                } elseif (strlen($userData['mot_de_passe']) < 6) {
                    $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
                }
                
                if (empty($errors)) {
                    try {
                        // Créer l'utilisateur
                        $userId = createUser($userData);
                        
                        // Créer le profil médecin
                        $sql = "INSERT INTO medecins (id_utilisateur, specialite, telephone, adresse) 
                                VALUES (:id_utilisateur, :specialite, :telephone, :adresse)";
                        
                        insert($sql, [
                            'id_utilisateur' => $userId,
                            'specialite' => $medecinData['specialite'],
                            'telephone' => $medecinData['telephone'],
                            'adresse' => $medecinData['adresse']
                        ]);
                        
                        redirectWithMessage('medecins.php', 'Médecin ajouté avec succès', 'success');
                        
                    } catch (Exception $e) {
                        $errors[] = 'Une erreur est survenue: ' . $e->getMessage();
                    }
                }
                
                if (!empty($errors)) {
                    $error_message = implode('<br>', $errors);
                }
                break;
                
            case 'delete':
                $medecinId = intval($_POST['medecin_id']);
                
                // Vérifier si le médecin a des rendez-vous
                $rdvCount = fetchOne("SELECT COUNT(*) as count FROM rendez_vous WHERE id_medecin = :id", 
                                   ['id' => $medecinId])['count'];
                
                if ($rdvCount > 0) {
                    redirectWithMessage('medecins.php', 'Impossible de supprimer ce médecin : il a des rendez-vous associés', 'error');
                } else {
                    // Récupérer l'ID utilisateur
                    $medecin = fetchOne("SELECT id_utilisateur FROM medecins WHERE id = :id", 
                                       ['id' => $medecinId]);
                    
                    if ($medecin) {
                        // Supprimer le profil médecin
                        delete("DELETE FROM medecins WHERE id = :id", ['id' => $medecinId]);
                        // Supprimer l'utilisateur
                        delete("DELETE FROM utilisateurs WHERE id = :id", ['id' => $medecin['id_utilisateur']]);
                        
                        redirectWithMessage('medecins.php', 'Médecin supprimé avec succès', 'success');
                    }
                }
                break;
        }
    }
}

// Recherche et filtrage
$search = $_GET['search'] ?? '';
$specialite = $_GET['specialite'] ?? '';

// Obtenir la liste des médecins
$sql = "SELECT u.*, m.specialite, m.telephone, m.adresse, m.id as medecin_id 
        FROM utilisateurs u 
        JOIN medecins m ON u.id = m.id_utilisateur 
        WHERE u.role = 'medecin'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE :search OR u.email LIKE :search OR m.specialite LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($specialite)) {
    $sql .= " AND m.specialite = :specialite";
    $params['specialite'] = $specialite;
}

$sql .= " ORDER BY u.nom";

$medecins = fetchAll($sql, $params);

// Obtenir les spécialités pour le filtre
$specialites = fetchAll("SELECT DISTINCT specialite FROM medecins WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite");
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-user-md"></i> Gestion des médecins</h1>
        <div class="page-actions">
            <button type="button" onclick="showAddForm()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau médecin
            </button>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <!-- Filtres et recherche -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="search-form">
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" id="search" name="search" class="form-control" 
                                   placeholder="Nom, email, spécialité..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="specialite" class="form-label">Spécialité</label>
                            <select id="specialite" name="specialite" class="form-control">
                                <option value="">Toutes les spécialités</option>
                                <?php foreach ($specialites as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec['specialite']); ?>" 
                                        <?php echo $specialite === $spec['specialite'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['specialite']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulaire d'ajout -->
    <div id="addForm" class="card" style="display: none;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-plus"></i> Ajouter un médecin
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" data-validate>
                <input type="hidden" name="action" value="create">
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nom" class="form-label">
                                Nom <span class="required">*</span>
                            </label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   required data-error="Le nom est obligatoire">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="email" class="form-label">
                                Email <span class="required">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   required data-error="L'email est obligatoire">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="mot_de_passe" class="form-label">
                                Mot de passe <span class="required">*</span>
                            </label>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" 
                                   required minlength="6" data-error="Le mot de passe doit contenir au moins 6 caractères">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="specialite" class="form-label">Spécialité</label>
                            <input type="text" id="specialite" name="specialite" class="form-control" 
                                   placeholder="ex: Cardiologie">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" class="form-control" 
                                   placeholder="06 12 34 56 78">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" id="adresse" name="adresse" class="form-control" 
                                   placeholder="Numéro, rue, ville...">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Ajouter le médecin
                    </button>
                    <button type="button" onclick="hideAddForm()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des médecins -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des médecins
                <?php if (count($medecins) > 0): ?>
                <span class="badge badge-secondary"><?php echo count($medecins); ?></span>
                <?php endif; ?>
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($medecins)): ?>
            <div class="empty-state">
                <i class="fas fa-user-md-slash"></i>
                <h4>Aucun médecin trouvé</h4>
                <p><?php echo $search || $specialite ? 'Aucun médecin ne correspond à vos critères.' : 'Commencez par ajouter un nouveau médecin.'; ?></p>
                <button type="button" onclick="showAddForm()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un médecin
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="medecins-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contact</th>
                            <th>Spécialité</th>
                            <th>Adresse</th>
                            <th>Date d'ajout</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medecins as $medecin): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($medecin['nom']); ?></strong>
                            </td>
                            <td>
                                <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($medecin['email']); ?></div>
                                <?php if ($medecin['telephone']): ?>
                                <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($medecin['telephone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($medecin['specialite']) {
                                    echo '<span class="badge badge-info">' . htmlspecialchars($medecin['specialite']) . '</span>';
                                } else {
                                    echo '<span class="text-muted">Non spécifiée</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($medecin['adresse']) {
                                    echo nl2br(htmlspecialchars(substr($medecin['adresse'], 0, 50)));
                                    if (strlen($medecin['adresse']) > 50) {
                                        echo '...';
                                    }
                                } else {
                                    echo '<span class="text-muted">Non spécifiée</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo formatDate($medecin['date_creation']); ?></td>
                            <td>
                                <div class="actions">
                                    <button type="button" onclick="showMedecinStats(<?php echo $medecin['medecin_id']; ?>)" 
                                            class="btn btn-info btn-action" title="Statistiques">
                                        <i class="fas fa-chart-bar"></i>
                                    </button>
                                    <button type="button" onclick="confirmDeleteMedecin(<?php echo $medecin['medecin_id']; ?>, '<?php echo addslashes($medecin['nom']); ?>')" 
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
            <p>Êtes-vous sûr de vouloir supprimer le médecin <strong id="deleteMedecinName"></strong> ?</p>
            <p class="text-warning">Cette action est irréversible et supprimera également le compte utilisateur associé.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="medecin_id" id="deleteMedecinId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal des statistiques -->
<div id="statsModal" class="modal" style="display: none;">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-chart-bar"></i> Statistiques du médecin</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="medecinStatsContent">
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

.page-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.search-form .form-row {
    align-items: flex-end;
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
    font-size: 0.8rem;
}

.badge-info {
    background-color: var(--secondary-color);
    color: white;
}

.badge-secondary {
    background-color: var(--gray-color);
    color: white;
}

.modal-lg {
    max-width: 800px;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form .form-row {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showAddForm() {
    document.getElementById('addForm').style.display = 'block';
    document.getElementById('addForm').scrollIntoView({ behavior: 'smooth' });
}

function hideAddForm() {
    document.getElementById('addForm').style.display = 'none';
}

function confirmDeleteMedecin(medecinId, medecinName) {
    document.getElementById('deleteMedecinId').value = medecinId;
    document.getElementById('deleteMedecinName').textContent = medecinName;
    document.getElementById('deleteModal').style.display = 'block';
}

function showMedecinStats(medecinId) {
    document.getElementById('statsModal').style.display = 'block';
    
    // Charger les statistiques via AJAX
    fetch(`../../api/medecin_stats.php?id=${medecinId}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">${data.total_rdv}</div>
                        <div class="stat-label">Total rendez-vous</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">${data.rdv_ce_mois}</div>
                        <div class="stat-label">RDV ce mois</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">${data.total_consultations}</div>
                        <div class="stat-label">Total consultations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">${data.consultations_ce_mois}</div>
                        <div class="stat-label">Consultations ce mois</div>
                    </div>
                </div>
            `;
            
            if (data.rendez_vous_recents && data.rendez_vous_recents.length > 0) {
                html += '<h4>Rendez-vous récents</h4>';
                html += '<div class="table-responsive">';
                html += '<table class="table">';
                html += '<thead><tr><th>Date</th><th>Patient</th><th>Statut</th></tr></thead>';
                html += '<tbody>';
                
                data.rendez_vous_recents.forEach(rdv => {
                    html += '<tr>';
                    html += '<td>' + rdv.date_heure + '</td>';
                    html += '<td>' + rdv.patient_nom + '</td>';
                    html += '<td><span class="badge">' + rdv.statut + '</span></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            document.getElementById('medecinStatsContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('medecinStatsContent').innerHTML = 
                '<p class="text-danger">Erreur lors du chargement des statistiques.</p>';
        });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
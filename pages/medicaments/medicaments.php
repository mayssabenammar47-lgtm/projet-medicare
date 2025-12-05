<?php
/**
 * projet-medicare/pages/medicaments/medicaments.php
 * Gestion des médicaments (réservé à l'admin)
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
            case 'update':
                $medicamentData = [
                    'nom' => cleanInput($_POST['nom'] ?? ''),
                    'description' => cleanInput($_POST['description'] ?? ''),
                    'stock' => intval($_POST['stock'] ?? 0),
                    'prix' => floatval($_POST['prix'] ?? 0)
                ];
                
                // Validation
                $errors = [];
                
                if (empty($medicamentData['nom'])) {
                    $errors[] = 'Le nom du médicament est obligatoire';
                }
                
                if ($medicamentData['stock'] < 0) {
                    $errors[] = 'Le stock ne peut pas être négatif';
                }
                
                if ($medicamentData['prix'] < 0) {
                    $errors[] = 'Le prix ne peut pas être négatif';
                }
                
                if (empty($errors)) {
                    if ($_POST['action'] === 'create') {
                        $sql = "INSERT INTO medicaments (nom, description, stock, prix) 
                                VALUES (:nom, :description, :stock, :prix)";
                        
                        insert($sql, $medicamentData);
                        redirectWithMessage('medicaments.php', 'Médicament ajouté avec succès', 'success');
                    } else {
                        $medicamentId = intval($_POST['medicament_id']);
                        update("UPDATE medicaments SET nom = :nom, description = :description, 
                               stock = :stock, prix = :prix WHERE id = :id", 
                               array_merge($medicamentData, ['id' => $medicamentId]));
                        redirectWithMessage('medicaments.php', 'Médicament modifié avec succès', 'success');
                    }
                } else {
                    $error_message = implode('<br>', $errors);
                }
                break;
                
            case 'delete':
                $medicamentId = intval($_POST['medicament_id']);
                
                // Vérifier si le médicament est utilisé dans des prescriptions
                $prescriptionCount = fetchOne("SELECT COUNT(*) as count FROM prescriptions WHERE id_medicament = :id", 
                                             ['id' => $medicamentId])['count'];
                
                if ($prescriptionCount > 0) {
                    redirectWithMessage('medicaments.php', 'Impossible de supprimer ce médicament : il est utilisé dans des prescriptions', 'error');
                } else {
                    delete("DELETE FROM medicaments WHERE id = :id", ['id' => $medicamentId]);
                    redirectWithMessage('medicaments.php', 'Médicament supprimé avec succès', 'success');
                }
                break;
                
            case 'restock':
                $medicamentId = intval($_POST['medicament_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($quantity > 0) {
                    update("UPDATE medicaments SET stock = stock + :quantity WHERE id = :id", 
                           ['quantity' => $quantity, 'id' => $medicamentId]);
                    redirectWithMessage('medicaments.php', 'Stock mis à jour avec succès', 'success');
                } else {
                    $error_message = 'La quantité doit être supérieure à 0';
                }
                break;
        }
    }
}

// Recherche et filtrage
$search = $_GET['search'] ?? '';
$stockFilter = $_GET['stock'] ?? '';

// Obtenir la liste des médicaments
$sql = "SELECT * FROM medicaments";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE nom LIKE :search OR description LIKE :search";
    $params['search'] = '%' . $search . '%';
}

if (!empty($stockFilter)) {
    $sql .= ($search ? ' AND' : ' WHERE');
    switch ($stockFilter) {
        case 'low':
            $sql .= " stock < 10";
            break;
        case 'out':
            $sql .= " stock = 0";
            break;
        case 'available':
            $sql .= " stock > 0";
            break;
    }
}

$sql .= " ORDER BY nom";

$medicaments = fetchAll($sql, $params);

// Récupérer le médicament à modifier
$editMedicament = null;
$editMode = false;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $medicamentId = intval($_GET['edit']);
    $editMedicament = fetchOne("SELECT * FROM medicaments WHERE id = :id", ['id' => $medicamentId]);
    
    if (!$editMedicament) {
        redirectWithMessage('medicaments.php', 'Médicament non trouvé', 'error');
    }
    
    $editMode = true;
}

// Statistiques
$totalMedicaments = count($medicaments);
$lowStock = count(array_filter($medicaments, fn($m) => $m['stock'] < 10 && $m['stock'] > 0));
$outOfStock = count(array_filter($medicaments, fn($m) => $m['stock'] == 0));
$totalValue = array_sum(array_map(fn($m) => $m['stock'] * $m['prix'], $medicaments));
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-pills"></i> Gestion des médicaments</h1>
        <div class="page-actions">
            <?php if (!$editMode): ?>
            <button type="button" onclick="showAddForm()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau médicament
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon" style="color: var(--secondary-color);">
                <i class="fas fa-pills"></i>
            </div>
            <div class="stat-number"><?php echo $totalMedicaments; ?></div>
            <div class="stat-label">Total médicaments</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: var(--warning-color);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-number"><?php echo $lowStock; ?></div>
            <div class="stat-label">Stock faible</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: var(--danger-color);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-number"><?php echo $outOfStock; ?></div>
            <div class="stat-label">Rupture de stock</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="color: var(--success-color);">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-number"><?php echo number_format($totalValue, 2); ?> €</div>
            <div class="stat-label">Valeur totale</div>
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
                                   placeholder="Nom, description..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="stock" class="form-label">Filtre stock</label>
                            <select id="stock" name="stock" class="form-control">
                                <option value="">Tous les stocks</option>
                                <option value="available" <?php echo $stockFilter === 'available' ? 'selected' : ''; ?>>
                                    Disponible
                                </option>
                                <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>
                                    Stock faible (&lt;10)
                                </option>
                                <option value="out" <?php echo $stockFilter === 'out' ? 'selected' : ''; ?>>
                                    Rupture de stock
                                </option>
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

    <!-- Formulaire d'ajout/modification -->
    <?php if ($editMode || isset($_GET['add'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus'; ?>"></i>
                <?php echo $editMode ? 'Modifier' : 'Ajouter'; ?> un médicament
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" data-validate>
                <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'create'; ?>">
                <?php if ($editMode): ?>
                <input type="hidden" name="medicament_id" value="<?php echo $editMedicament['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nom" class="form-label">
                                Nom <span class="required">*</span>
                            </label>
                            <input type="text" id="nom" name="nom" class="form-control" 
                                   value="<?php echo htmlspecialchars($editMedicament['nom'] ?? ''); ?>" 
                                   required data-error="Le nom est obligatoire">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" id="stock" name="stock" class="form-control" 
                                   value="<?php echo htmlspecialchars($editMedicament['stock'] ?? 0); ?>" 
                                   min="0">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="prix" class="form-label">Prix (€)</label>
                            <input type="number" id="prix" name="prix" class="form-control" 
                                   value="<?php echo htmlspecialchars($editMedicament['prix'] ?? 0); ?>" 
                                   step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3" 
                                      placeholder="Description du médicament..."><?php echo htmlspecialchars($editMedicament['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $editMode ? 'Mettre à jour' : 'Ajouter'; ?> le médicament
                    </button>
                    <a href="medicaments.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste des médicaments -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des médicaments
                <?php if (count($medicaments) > 0): ?>
                <span class="badge badge-secondary"><?php echo count($medicaments); ?></span>
                <?php endif; ?>
            </h3>
            <div class="card-actions">
                <button type="button" onclick="exportMedicaments()" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-download"></i> Exporter CSV
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($medicaments)): ?>
            <div class="empty-state">
                <i class="fas fa-pills-slash"></i>
                <h4>Aucun médicament trouvé</h4>
                <p><?php echo $search || $stockFilter ? 'Aucun médicament ne correspond à vos critères.' : 'Commencez par ajouter un nouveau médicament.'; ?></p>
                <button type="button" onclick="showAddForm()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter un médicament
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table" id="medicaments-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Stock</th>
                            <th>Prix</th>
                            <th>Valeur totale</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicaments as $medicament): ?>
                        <?php
                        $stockStatus = '';
                        $stockClass = '';
                        if ($medicament['stock'] == 0) {
                            $stockStatus = 'Rupture';
                            $stockClass = 'stock-out';
                        } elseif ($medicament['stock'] < 10) {
                            $stockStatus = 'Faible';
                            $stockClass = 'stock-low';
                        } else {
                            $stockStatus = 'Disponible';
                            $stockClass = 'stock-ok';
                        }
                        $totalValue = $medicament['stock'] * $medicament['prix'];
                        ?>
                        <tr class="<?php echo $stockClass; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($medicament['nom']); ?></strong>
                            </td>
                            <td>
                                <?php 
                                if ($medicament['description']) {
                                    echo nl2br(htmlspecialchars(substr($medicament['description'], 0, 100)));
                                    if (strlen($medicament['description']) > 100) {
                                        echo '...';
                                    }
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $stockClass; ?>">
                                    <?php echo $medicament['stock']; ?> unités
                                </span>
                                <br><small class="text-muted"><?php echo $stockStatus; ?></small>
                            </td>
                            <td><?php echo number_format($medicament['prix'], 2); ?> €</td>
                            <td><?php echo number_format($totalValue, 2); ?> €</td>
                            <td>
                                <div class="actions">
                                    <a href="medicaments.php?edit=<?php echo $medicament['id']; ?>" 
                                       class="btn btn-primary btn-action" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" onclick="showRestockForm(<?php echo $medicament['id']; ?>, '<?php echo addslashes($medicament['nom']); ?>', <?php echo $medicament['stock']; ?>)" 
                                            class="btn btn-success btn-action" title="Réapprovisionner">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" onclick="confirmDeleteMedicament(<?php echo $medicament['id']; ?>, '<?php echo addslashes($medicament['nom']); ?>')" 
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
            <p>Êtes-vous sûr de vouloir supprimer le médicament <strong id="deleteMedicamentName"></strong> ?</p>
            <p class="text-warning">Cette action est irréversible.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="medicament_id" id="deleteMedicamentId">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal de réapprovisionnement -->
<div id="restockModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Réapprovisionner le stock</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Ajouter du stock pour: <strong id="restockMedicamentName"></strong></p>
            <p>Stock actuel: <span id="restockCurrentStock"></span> unités</p>
            
            <form method="POST" action="" data-validate>
                <input type="hidden" name="action" value="restock">
                <input type="hidden" name="medicament_id" id="restockMedicamentId">
                
                <div class="form-group">
                    <label for="quantity" class="form-label">Quantité à ajouter</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" 
                           min="1" required data-error="La quantité est obligatoire">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Ajouter au stock
                    </button>
                    <button type="button" class="btn btn-secondary modal-close">Annuler</button>
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
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-secondary {
    background-color: var(--gray-color);
    color: white;
}

.badge-stock-ok {
    background-color: var(--success-color);
    color: white;
}

.badge-stock-low {
    background-color: var(--warning-color);
    color: white;
}

.badge-stock-out {
    background-color: var(--danger-color);
    color: white;
}

.stock-ok {
    background-color: rgba(39, 174, 96, 0.1);
}

.stock-low {
    background-color: rgba(243, 156, 18, 0.1);
}

.stock-out {
    background-color: rgba(231, 76, 60, 0.1);
}

.card-actions {
    display: flex;
    gap: 0.5rem;
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
    window.location.href = 'medicaments.php?add=1';
}

function confirmDeleteMedicament(medicamentId, medicamentName) {
    document.getElementById('deleteMedicamentId').value = medicamentId;
    document.getElementById('deleteMedicamentName').textContent = medicamentName;
    document.getElementById('deleteModal').style.display = 'block';
}

function showRestockForm(medicamentId, medicamentName, currentStock) {
    document.getElementById('restockMedicamentId').value = medicamentId;
    document.getElementById('restockMedicamentName').textContent = medicamentName;
    document.getElementById('restockCurrentStock').textContent = currentStock;
    document.getElementById('restockModal').style.display = 'block';
}

function exportMedicaments() {
    window.MediCare.exportToCSV('medicaments-table', 'medicaments_' + new Date().toISOString().split('T')[0] + '.csv');
}
</script>

<?php require_once '../../includes/footer.php'; ?>
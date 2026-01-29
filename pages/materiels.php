<?php
require_once '../includes/auth.php';
checkLogin();

$pageTitle = "Gestion des Matériels";
require_once '../includes/header.php';

require_once '../config/database.php';

// Récupérer la liste des matériels
$query = "SELECT * FROM materiels ORDER BY created_at DESC";
$materiels = $pdo->query($query)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pc-display"></i> Gestion des Matériels</h2>
    
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Ajouter
        </button>
        
        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-file-earmark-arrow-up"></i> Importer Excel</button><!-- import button -->

        <button type="button" class="btn btn-success" onclick="exportTableToExcel('materielsTable', 'materiels')"><!-- export button -->
            <i class="bi bi-file-excel"></i> Export Excel
        </button>
        
        <button type="button" class="btn btn-info" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select" id="filterType" onchange="filterTable()">
                    <option value="">Tous</option>
                    <option value="ordinateur">Ordinateurs</option>
                    <option value="ecran">Écrans</option>
                    <option value="imprimante">Imprimantes</option>
                    <option value="ventilateur">Ventilateurs</option>
                    <option value="autre">Autres</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select class="form-select" id="filterStatut" onchange="filterTable()">
                    <option value="">Tous</option>
                    <option value="stock">Stock</option>
                    <option value="affecte">Affecté</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="perdu">Perdu</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">État</label>
                <select class="form-select" id="filterEtat" onchange="filterTable()">
                    <option value="">Tous</option>
                    <option value="neuf">Neuf</option>
                    <option value="bon">Bon</option>
                    <option value="moyen">Moyen</option>
                    <option value="mauvais">Mauvais</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" class="form-control" id="searchInput" 
                       placeholder="Code, marque, modèle..." onkeyup="filterTable()">
            </div>
        </div>
    </div>
</div>

<!-- Tableau des matériels -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="materielsTable">
                <thead class="table-dark">
                    <tr>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Marque/Modèle</th>
                        <th>N° Série</th>
                        <th>État</th>
                        <th>Statut</th>
                        <th>Localisation</th>
                        <th>Date Acquisition</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materiels as $materiel): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($materiel['code_barre'] ?? 'N/A'); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                <?php echo htmlspecialchars(ucfirst($materiel['type'])); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($materiel['marque']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($materiel['modele']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($materiel['numero_serie'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $etat_classes = [
                                'neuf' => 'success',
                                'bon' => 'primary',
                                'moyen' => 'warning',
                                'mauvais' => 'danger'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $etat_classes[$materiel['etat']]; ?>">
                                <?php echo ucfirst($materiel['etat']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $statut_classes = [
                                'stock' => 'info',
                                'affecte' => 'success',
                                'maintenance' => 'warning',
                                'perdu' => 'danger'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $statut_classes[$materiel['statut']]; ?>">
                                <?php echo ucfirst($materiel['statut']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($materiel['localisation'] ?? '-'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($materiel['date_acquisition'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                        onclick="viewMateriel(<?php echo $materiel['id']; ?>)"
                                        title="Voir détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                
                                <?php if (isGestionnaire()): ?>
                                <button class="btn btn-outline-warning" 
                                        onclick="editMateriel(<?php echo $materiel['id']; ?>)"
                                        title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <?php if ($materiel['statut'] == 'stock'): ?>
                                <button class="btn btn-outline-success" 
                                        onclick="affecterMateriel(<?php echo $materiel['id']; ?>)"
                                        title="Affecter">
                                    <i class="bi bi-person-plus"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-danger" 
                                        onclick="deleteMateriel(<?php echo $materiel['id']; ?>)"
                                        title="Supprimer">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout Matériel -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Ajouter un Nouveau Matériel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_materiel.php" method="POST" id="addForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type de matériel *</label>
                            <select class="form-select" name="type" required>
                                <option value="">Sélectionner...</option>
                                <option value="ordinateur">Ordinateur portable</option>
                                <option value="ordinateur_bureau">Ordinateur bureau</option>
                                <option value="ecran">Écran</option>
                                <option value="imprimante">Imprimante</option>
                                <option value="scanner">Scanner</option>
                                <option value="ventilateur">Ventilateur</option>
                                <option value="clavier">Clavier</option>
                                <option value="souris">Souris</option>
                                <option value="onduleur">Onduleur</option>
                                <option value="serveur">Serveur</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marque *</label>
                            <input type="text" class="form-control" name="marque" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Modèle *</label>
                            <input type="text" class="form-control" name="modele" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Numéro de série</label>
                            <input type="text" class="form-control" name="numero_serie">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Caractéristiques</label>
                        <textarea class="form-control" name="caracteristiques" rows="3" 
                                  placeholder="RAM, Processeur, Stockage, etc."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date d'acquisition</label>
                            <input type="date" class="form-control" name="date_acquisition">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix (FCFA)</label>
                            <input type="number" class="form-control" name="prix" step="0.01">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">État</label>
                            <select class="form-select" name="etat">
                                <option value="neuf">Neuf</option>
                                <option value="bon" selected>Bon</option>
                                <option value="moyen">Moyen</option>
                                <option value="mauvais">Mauvais</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Statut initial</label>
                            <select class="form-select" name="statut">
                                <option value="stock" selected>En stock</option>
                                <option value="affecte">Affecté</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Localisation actuelle</label>
                        <input type="text" class="form-control" name="localisation" 
                               placeholder="Ex: Entrepôt IT, Bureau 201...">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Commentaires</label>
                        <textarea class="form-control" name="commentaires" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" name="action" value="add">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Materiels -->
<div class="modal fade" id="importModal" tabindex="-1"><!-- import modal wrapper -->
    <div class="modal-dialog modal-lg"><!-- import modal dialog -->
        <div class="modal-content"><!-- import modal content -->
            <div class="modal-header bg-secondary text-white"><!-- import modal header -->
                <h5 class="modal-title">Importer des Materiels</h5><!-- import modal title -->
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button><!-- import modal close -->
            </div><!-- end import modal header -->
            <form action="process_materiel.php" method="POST" id="importForm"><!-- import form -->
                <input type="hidden" name="action" value="import"><!-- import action -->
                <input type="hidden" name="rows" id="importRows"><!-- import rows payload -->
                <div class="modal-body"><!-- import modal body -->
                    <div class="mb-3"><!-- import file group -->
                        <label class="form-label">Fichier Excel (.xlsx ou .csv)</label><!-- import label -->
                        <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv" required><!-- import file input -->
                        <small class="text-muted">Colonnes: type, marque, modele, numero_serie, caracteristiques, date_acquisition, prix, etat, statut, localisation, commentaires</small><!-- import help -->
                    </div><!-- end import file group -->
                </div><!-- end import modal body -->
                <div class="modal-footer"><!-- import modal footer -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><!-- import cancel -->
                    <button type="submit" class="btn btn-secondary">Importer</button><!-- import submit -->
                </div><!-- end import modal footer -->
            </form><!-- end import form -->
        </div><!-- end import modal content -->
    </div><!-- end import modal dialog -->
</div><!-- end import modal -->
<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
var materielsTable = null; // DataTable instance
$(document).ready(function() {
    // Initialiser DataTable
    materielsTable = $('#materielsTable').DataTable({ // Init DataTable
        "pageLength": 25, // Page length
        "language": { // Language config
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json" // Language url
        } // End language config
    }); // End DataTable init
}); // End document ready

function filterTable() { // Filter table rows
    var type = ($('#filterType').val() || '').toLowerCase(); // Read type filter
    var statut = ($('#filterStatut').val() || '').toLowerCase(); // Read status filter
    var etat = ($('#filterEtat').val() || '').toLowerCase(); // Read condition filter
    var search = ($('#searchInput').val() || '').toLowerCase(); // Read search text
    if (materielsTable) { // Ensure DataTable exists
        materielsTable.column(1).search(type, false, false); // Apply type filter
        materielsTable.column(5).search(statut, false, false); // Apply status filter
        materielsTable.column(4).search(etat, false, false); // Apply condition filter
        materielsTable.search(search, false, false); // Apply global search
        materielsTable.draw(); // Redraw table
        return; // Stop fallback
    } // End DataTable guard
} // End filterTable
function viewMateriel(id) { // View material
    window.location.href = 'edit_materiel.php?id=' + id; // Redirect to edit page
} // End viewMateriel
function editMateriel(id) { // Edit material
    window.location.href = 'edit_materiel.php?id=' + id; // Redirect to edit page
} // End editMateriel
function affecterMateriel(id) { // Assign material
    window.location.href = 'affectations.php?materiel_id=' + id; // Redirect to affectations
} // End affecterMateriel
function deleteMateriel(id) { // Delete material
    if (confirm('Supprimer ce materiel ?')) { // Confirm deletion
        window.location.href = 'process_materiel.php?action=delete&id=' + id; // Redirect to delete
    } // End confirm
} // End deleteMateriel
var importForm = document.getElementById('importForm'); // Locate import form
if (importForm) { // Guard when form exists
    importForm.addEventListener('submit', function(event) { // Bind import submit
        event.preventDefault(); // Stop default submit
        var form = this; // Capture form
        var fileInput = document.getElementById('importFile'); // Resolve file input
        var file = fileInput ? fileInput.files[0] : null; // Read file
        if (!file) { // Validate file
            alert('Veuillez choisir un fichier Excel.'); // Alert missing file
            return; // Stop handler
        } // End file validation
        if (typeof XLSX === 'undefined') { // Ensure XLSX is available
            alert('Librairie XLSX manquante.'); // Alert missing lib
            return; // Stop handler
        } // End XLSX check
        var reader = new FileReader(); // Create reader
        reader.onload = function(loadEvent) { // Handle file load
            var data = new Uint8Array(loadEvent.target.result); // Read array buffer
            var workbook = XLSX.read(data, { type: 'array', cellDates: true }); // Parse workbook
            var sheetName = workbook.SheetNames[0]; // Select first sheet
            var rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { defval: '' }); // Convert to rows
            document.getElementById('importRows').value = JSON.stringify(rows); // Fill payload
            form.submit(); // Submit form
        }; // End load handler
        reader.readAsArrayBuffer(file); // Start reading
    }); // End import submit binding
} // End import form guard
</script><!-- end page script -->
<?php require_once '../includes/footer.php'; ?><!-- include footer -->
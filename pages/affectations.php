<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

checkLogin();
$pageTitle = "Gestion des Affectations";
require_once __DIR__ . '/../includes/header.php';

// Récupérer les affectations
$query = "
    SELECT a.*, 
           m.code_barre, m.type as materiel_type, m.marque, m.modele,
           CONCAT(u.nom, ' ', u.prenom) as utilisateur_nom,
           u.departement,
           CONCAT(ap.nom, ' ', ap.prenom) as approbateur_nom
    FROM affectations a
    JOIN materiels m ON a.materiel_id = m.id
    JOIN utilisateurs u ON a.utilisateur_id = u.id
    LEFT JOIN utilisateurs ap ON a.approuve_par = ap.id
    ORDER BY a.created_at DESC
";

$affectations = $pdo->query($query)->fetchAll();
$preselectedMaterielId = isset($_GET['materiel_id']) ? (int)$_GET['materiel_id'] : 0; // Preselect materiel id
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Gestion des Affectations</h2>
    
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Nouvelle Affectation
        </button>
        <!-- <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-file-earmark-arrow-up"></i> Importer Excel</button>import button -->
    </div>
</div>

<!-- Modal Edit Affectation -->
<div class="modal fade" id="editModal" tabindex="-1"><!-- edit modal wrapper -->
    <div class="modal-dialog modal-lg"><!-- edit modal dialog -->
        <div class="modal-content"><!-- edit modal content -->
            <div class="modal-header bg-warning text-white"><!-- edit modal header -->
                <h5 class="modal-title">Modifier Affectation</h5><!-- edit modal title -->
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button><!-- edit modal close -->
            </div><!-- end edit modal header -->
            <form action="process_affectation.php" method="POST" id="editForm"><!-- edit form -->
                <input type="hidden" name="action" value="edit"><!-- edit action -->
                <input type="hidden" name="id" id="editAffectationId"><!-- edit id -->
                <div class="modal-body"><!-- edit modal body -->
                    <div class="row"><!-- info row -->
                        <div class="col-md-6 mb-3"><!-- materiel field -->
                            <label class="form-label">Materiel</label><!-- materiel label -->
                            <input type="text" class="form-control" id="editMateriel" readonly><!-- materiel input -->
                        </div><!-- end materiel field -->
                        <div class="col-md-6 mb-3"><!-- utilisateur field -->
                            <label class="form-label">Utilisateur</label><!-- utilisateur label -->
                            <input type="text" class="form-control" id="editUtilisateur" readonly><!-- utilisateur input -->
                        </div><!-- end utilisateur field -->
                    </div><!-- end info row -->
                    <div class="row"><!-- dates row -->
                        <div class="col-md-6 mb-3"><!-- date debut field -->
                            <label class="form-label">Date debut *</label><!-- date debut label -->
                            <input type="date" class="form-control" name="date_debut" id="editDateDebut" required><!-- date debut input -->
                        </div><!-- end date debut field -->
                        <div class="col-md-6 mb-3"><!-- date fin field -->
                            <label class="form-label">Date fin</label><!-- date fin label -->
                            <input type="date" class="form-control" name="date_fin" id="editDateFin"><!-- date fin input -->
                        </div><!-- end date fin field -->
                    </div><!-- end dates row -->
                    <div class="mb-3"><!-- motif field -->
                        <label class="form-label">Motif *</label><!-- motif label -->
                        <textarea class="form-control" name="motif" id="editMotif" rows="3" required></textarea><!-- motif input -->
                    </div><!-- end motif field -->
                    <div class="row"><!-- etat row -->
                        <div class="col-md-6 mb-3"><!-- etat depart field -->
                            <label class="form-label">Etat depart</label><!-- etat depart label -->
                            <select class="form-select" name="etat_depart" id="editEtatDepart"><!-- etat depart select -->
                                <option value="bon">Bon</option><!-- etat bon -->
                                <option value="neuf">Neuf</option><!-- etat neuf -->
                                <option value="moyen">Moyen</option><!-- etat moyen -->
                                <option value="mauvais">Mauvais</option><!-- etat mauvais -->
                            </select><!-- end etat depart select -->
                        </div><!-- end etat depart field -->
                        <div class="col-md-6 mb-3"><!-- etat retour field -->
                            <label class="form-label">Etat retour</label><!-- etat retour label -->
                            <select class="form-select" name="etat_retour" id="editEtatRetour"><!-- etat retour select -->
                                <option value="">-</option><!-- etat empty -->
                                <option value="bon">Bon</option><!-- etat bon -->
                                <option value="neuf">Neuf</option><!-- etat neuf -->
                                <option value="moyen">Moyen</option><!-- etat moyen -->
                                <option value="mauvais">Mauvais</option><!-- etat mauvais -->
                            </select><!-- end etat retour select -->
                        </div><!-- end etat retour field -->
                    </div><!-- end etat row -->
                    <div class="mb-3"><!-- observations field -->
                        <label class="form-label">Observations</label><!-- observations label -->
                        <textarea class="form-control" name="observations" id="editObservations" rows="2"></textarea><!-- observations input -->
                    </div><!-- end observations field -->
                </div><!-- end edit modal body -->
                <div class="modal-footer"><!-- edit modal footer -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><!-- edit cancel -->
                    <button type="submit" class="btn btn-warning">Enregistrer</button><!-- edit submit -->
                </div><!-- end edit modal footer -->
            </form><!-- end edit form -->
        </div><!-- end edit modal content -->
    </div><!-- end edit modal dialog -->
</div><!-- end edit modal -->
<!-- Modal Import Affectations -->
<div class="modal fade" id="importModal" tabindex="-1"><!-- import modal wrapper -->
    <div class="modal-dialog modal-lg"><!-- import modal dialog -->
        <div class="modal-content"><!-- import modal content -->
            <div class="modal-header bg-secondary text-white"><!-- import modal header -->
                <h5 class="modal-title">Importer Affectations</h5><!-- import modal title -->
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button><!-- import modal close -->
            </div><!-- end import modal header -->
            <form action="process_affectation.php" method="POST" id="importFormAffectations"><!-- import form -->
                <input type="hidden" name="action" value="import"><!-- import action -->
                <input type="hidden" name="rows" id="importRowsAffectations"><!-- import rows payload -->
                <div class="modal-body"><!-- import modal body -->
                    <div class="mb-3"><!-- import file group -->
                        <label class="form-label">Fichier Excel (.xlsx ou .csv)</label><!-- import label -->
                        <input type="file" class="form-control" id="importFileAffectations" accept=".xlsx,.xls,.csv" required><!-- import file input -->
                        <small class="text-muted">Colonnes: code_barre ou materiel_id, username ou email ou utilisateur_id, date_debut, motif, date_fin, etat_depart, etat_retour, statut, observations</small><!-- import help -->
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
<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select class="form-select" id="filterStatut" onchange="applyAffectationsFilters()"><!-- filter statut -->
                    <option value="">Tous</option>
                    <option value="en_attente">En attente</option>
                    <option value="approuve">Approuvé</option>
                    <option value="refuse">Refusé</option>
                    <option value="retourne">Retourné</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Département</label>
                <select class="form-select" id="filterDepartement" onchange="applyAffectationsFilters()"><!-- filter departement -->
                    <option value="">Tous</option>
                    <?php
                    $depts = $pdo->query("SELECT DISTINCT departement FROM utilisateurs WHERE departement IS NOT NULL ORDER BY departement")->fetchAll();
                    foreach ($depts as $dept): ?>
                    <option value="<?php echo escape($dept['departement']); ?>">
                        <?php echo escape($dept['departement']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Date de début</label>
                <input type="date" class="form-control" id="filterDateDebut" onchange="applyAffectationsFilters()"><!-- filter date -->
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" class="form-control" id="searchInput" placeholder="Utilisateur, matériel, motif..." onkeyup="applyAffectationsFilters()"><!-- search input -->
            </div>
        </div>
    </div>
</div>

<!-- Tableau des affectations -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="affectationsTable">
                <thead class="table-dark">
                    <tr><!-- header row -->
                        <th>ID</th>
                        <th>Matériel</th>
                        <th>Utilisateur</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Statut</th>
                        <th>Motif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($affectations as $aff): ?>
                    <tr data-affectation-id="<?php echo $aff['id']; ?>" data-materiel-id="<?php echo $aff['materiel_id']; ?>" data-utilisateur-id="<?php echo $aff['utilisateur_id']; ?>" data-date-debut="<?php echo escape($aff['date_debut']); ?>" data-date-fin="<?php echo escape($aff['date_fin']); ?>" data-statut="<?php echo escape($aff['statut']); ?>" data-departement="<?php echo escape($aff['departement']); ?>" data-motif="<?php echo escape($aff['motif']); ?>" data-etat-depart="<?php echo escape($aff['etat_depart']); ?>" data-etat-retour="<?php echo escape($aff['etat_retour']); ?>" data-observations="<?php echo escape($aff['observations']); ?>" data-materiel-label="<?php echo escape($aff['code_barre'] . ' - ' . $aff['marque'] . ' ' . $aff['modele']); ?>" data-utilisateur-label="<?php echo escape($aff['utilisateur_nom']); ?>"><!-- row data -->
                        <td><?php echo $aff['id']; ?></td>
                        <td>
                            <strong><?php echo escape($aff['code_barre']); ?></strong><br>
                            <small class="text-muted">
                                <?php echo escape($aff['marque'] . ' ' . $aff['modele']); ?>
                            </small>
                        </td>
                        <td>
                            <strong><?php echo escape($aff['utilisateur_nom']); ?></strong><br>
                            <small class="text-muted"><?php echo escape($aff['departement']); ?></small>
                        </td>
                        <td><?php echo formatDate($aff['date_debut']); ?></td>
                        <td><?php echo $aff['date_fin'] ? formatDate($aff['date_fin']) : '-'; ?></td>
                        <td>
                            <?php displayBadge($aff['statut']); ?>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 200px;" 
                                 title="<?php echo escape($aff['motif']); ?>">
                                <?php echo escape($aff['motif']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                        onclick="viewAffectation(<?php echo $aff['id']; ?>)"
                                        title="Voir détails">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if (isGestionnaire()): ?><!-- gestionnaire actions -->
                                <button class="btn btn-outline-warning" onclick="openEditModalById(<?php echo $aff['id']; ?>)" title="Modifier"><i class="bi bi-pencil"></i></button><!-- edit button -->
                                <button class="btn btn-outline-danger" onclick="deleteAffectation(<?php echo $aff['id']; ?>)" title="Supprimer"><i class="bi bi-trash"></i></button><!-- delete button -->
                                <?php endif; ?><!-- end gestionnaire actions -->
                                
                                <?php if (isGestionnaire() && $aff['statut'] == 'en_attente'): ?>
                                <button class="btn btn-outline-success" 
                                        onclick="approveAffectation(<?php echo $aff['id']; ?>)"
                                        title="Approuver">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                
                                <button class="btn btn-outline-danger" 
                                        onclick="rejectAffectation(<?php echo $aff['id']; ?>)"
                                        title="Refuser">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if (isGestionnaire() && $aff['statut'] == 'approuve' && !$aff['date_fin']): ?>
                                <button class="btn btn-outline-warning" 
                                        onclick="returnMaterial(<?php echo $aff['id']; ?>)"
                                        title="Retour matériel">
                                    <i class="bi bi-arrow-return-left"></i>
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

<!-- Modal Nouvelle Affectation -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Nouvelle Affectation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_affectation.php" method="POST" id="addForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Matériel à affecter *</label>
                            <select class="form-select" name="materiel_id" required id="selectMateriel">
                                <option value="">Sélectionner un matériel...</option>
                                <?php
                                $materiels = $pdo->query("
                                    SELECT id, code_barre, type, marque, modele 
                                    FROM materiels 
                                    WHERE statut = 'stock' 
                                    ORDER BY type, marque
                                ")->fetchAll();
                                
                                foreach ($materiels as $mat): ?>
                                <option value="<?php echo $mat['id']; ?>" <?php echo $preselectedMaterielId === (int)$mat['id'] ? 'selected' : ''; ?>><!-- preselect materiel -->
                                    <?php echo escape($mat['code_barre'] . ' - ' . $mat['marque'] . ' ' . $mat['modele']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Utilisateur *</label>
                            <select class="form-select" name="utilisateur_id" required id="selectUtilisateur">
                                <option value="">Sélectionner un utilisateur...</option>
                                <?php
                                $utilisateurs = $pdo->query("
                                    SELECT id, nom, prenom, departement 
                                    FROM utilisateurs 
                                    WHERE actif = 1 
                                    ORDER BY nom, prenom
                                ")->fetchAll();
                                
                                foreach ($utilisateurs as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo escape($user['nom'] . ' ' . $user['prenom'] . ' (' . $user['departement'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de début *</label>
                            <input type="date" class="form-control" name="date_debut" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motif de l'affectation *</label>
                        <textarea class="form-control" name="motif" rows="3" 
                                  placeholder="Décrivez la raison de l'affectation..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">État du matériel au départ</label>
                        <select class="form-select" name="etat_depart">
                            <option value="bon">Bon</option>
                            <option value="neuf">Neuf</option>
                            <option value="moyen">Moyen</option>
                            <option value="mauvais">Mauvais</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Justificatif (document)</label>
                        <input type="file" class="form-control" name="justificatif" accept=".pdf,.jpg,.png,.doc,.docx">
                        <small class="text-muted">Formats acceptés: PDF, JPG, PNG, DOC (max 5MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observations</label>
                        <textarea class="form-control" name="observations" rows="2"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" name="action" value="add">
                        <i class="bi bi-send"></i> Soumettre pour approbation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var affectationsTable = null; // DataTable instance
var affectationsFilters = { statut: '', departement: '', dateDebut: '', search: '' }; // Filter state
$(document).ready(function() {
    affectationsTable = $('#affectationsTable').DataTable({ // Init DataTable
        "pageLength": 25, // Page length
        "order": [[0, 'desc']], // Default order
        "language": { // Language config
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json" // Language url
        } // End language config
    }); // End DataTable init
    $('#filterStatut, #filterDepartement, #filterDateDebut').on('change', applyAffectationsFilters); // Bind filter change
    $('#searchInput').on('keyup', applyAffectationsFilters); // Bind search keyup
    applyAffectationsFilters(); // Apply filters on load
}); // End document ready
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) { // Custom filter hook
    if (!settings.nTable || settings.nTable.id !== 'affectationsTable') { // Scope to table
        return true; // Skip other tables
    } // End table guard
    var rowNode = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null; // Row node
    var dataset = rowNode ? rowNode.dataset : {}; // Row dataset
    var rowStatut = (dataset.statut || '').toLowerCase(); // Row status
    var rowDepartement = (dataset.departement || '').toLowerCase(); // Row departement
    var rowDate = dataset.dateDebut || ''; // Row date
    var rowText = rowNode ? rowNode.textContent.toLowerCase() : ''; // Row text
    if (affectationsFilters.statut && rowStatut !== affectationsFilters.statut) { // Apply status filter
        return false; // Exclude row
    } // End status filter
    if (affectationsFilters.departement && !rowDepartement.includes(affectationsFilters.departement)) { // Apply departement filter
        return false; // Exclude row
    } // End departement filter
    if (affectationsFilters.dateDebut && rowDate !== affectationsFilters.dateDebut) { // Apply date filter
        return false; // Exclude row
    } // End date filter
    if (affectationsFilters.search && !rowText.includes(affectationsFilters.search)) { // Apply search filter
        return false; // Exclude row
    } // End search filter
    return true; // Keep row
}); // End custom filter hook

function applyAffectationsFilters() { // Filter table rows
    affectationsFilters.statut = ($('#filterStatut').val() || '').toLowerCase(); // Read status filter
    affectationsFilters.departement = ($('#filterDepartement').val() || '').toLowerCase(); // Read departement filter
    affectationsFilters.dateDebut = $('#filterDateDebut').val() || ''; // Read date filter
    affectationsFilters.search = ($('#searchInput').val() || '').toLowerCase(); // Read search text
    if (affectationsTable) { // Ensure DataTable exists
        affectationsTable.draw(); // Redraw table
        return; // Stop fallback
    } // End DataTable guard
} // End applyAffectationsFilters

function openEditModalById(id) { // Open edit modal by id
    var row = document.querySelector('tr[data-affectation-id="' + id + '"]'); // Locate row
    if (!row) { // Guard missing row
        alert('Affectation introuvable.'); // Alert missing row
        return; // Stop
    } // End guard
    openEditModal(row); // Populate and show modal
} // End openEditModalById
function openEditModal(row) { // Populate edit modal
    var dataset = row.dataset; // Row dataset
    document.getElementById('editAffectationId').value = dataset.affectationId || ''; // Set id
    document.getElementById('editMateriel').value = dataset.materielLabel || ''; // Set materiel label
    document.getElementById('editUtilisateur').value = dataset.utilisateurLabel || ''; // Set user label
    document.getElementById('editDateDebut').value = dataset.dateDebut || ''; // Set start date
    document.getElementById('editDateFin').value = dataset.dateFin || ''; // Set end date
    document.getElementById('editMotif').value = dataset.motif || ''; // Set motif
    document.getElementById('editEtatDepart').value = dataset.etatDepart || 'bon'; // Set start condition
    document.getElementById('editEtatRetour').value = dataset.etatRetour || ''; // Set return condition
    document.getElementById('editObservations').value = dataset.observations || ''; // Set observations
    var modal = new bootstrap.Modal(document.getElementById('editModal')); // Init modal
    modal.show(); // Show modal
} // End openEditModal
function viewAffectation(id) { // View affectation
    window.location.href = 'affectation_details.php?id=' + id; // Redirect to details
} // End viewAffectation
function deleteAffectation(id) { // Delete affectation
    if (confirm('Supprimer cette affectation ?')) { // Confirm deletion
        window.location.href = 'process_affectation.php?action=delete&id=' + id; // Redirect to delete
    } // End confirm
} // End deleteAffectation

function approveAffectation(id) {
    if (confirm('Approuver cette affectation ?')) {
        window.location.href = 'process_affectation.php?action=approve&id=' + id;
    }
}

function rejectAffectation(id) {
    if (confirm('Refuser cette affectation ?')) {
        var raison = prompt('Raison du refus :');
        if (raison !== null) {
            window.location.href = 'process_affectation.php?action=reject&id=' + id + '&raison=' + encodeURIComponent(raison);
        }
    }
}

function returnMaterial(id) {
    if (confirm('Enregistrer le retour de ce matériel ?')) {
        var etat = prompt('État du matériel au retour (bon, moyen, mauvais) :', 'bon');
        if (etat !== null) {
            window.location.href = 'process_affectation.php?action=return&id=' + id + '&etat=' + encodeURIComponent(etat);
        }
    }
}
var importFormAffectations = document.getElementById('importFormAffectations'); // Locate import form
if (importFormAffectations) { // Guard when form exists
    importFormAffectations.addEventListener('submit', function(event) { // Bind import submit
        event.preventDefault(); // Stop default submit
        var form = this; // Capture form
        var fileInput = document.getElementById('importFileAffectations'); // Resolve file input
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
            document.getElementById('importRowsAffectations').value = JSON.stringify(rows); // Fill payload
            form.submit(); // Submit form
        }; // End load handler
        reader.readAsArrayBuffer(file); // Start reading
    }); // End import submit binding
} // End import form guard
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
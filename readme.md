#Affectation
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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Gestion des Affectations</h2>
    
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Nouvelle Affectation
        </button>
    </div>
</div>

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select class="form-select" id="filterStatut" onchange="filterTable()">
                    <option value="">Tous</option>
                    <option value="en_attente">En attente</option>
                    <option value="approuve">Approuvé</option>
                    <option value="refuse">Refusé</option>
                    <option value="retourne">Retourné</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Département</label>
                <select class="form-select" id="filterDepartement" onchange="filterTable()">
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
                <input type="date" class="form-control" id="filterDateDebut" onchange="filterTable()">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" class="form-control" id="searchInput" 
                       placeholder="Utilisateur, matériel, motif..." onkeyup="filterTable()">
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
                    <tr>
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
                    <tr>
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
                                <option value="<?php echo $mat['id']; ?>">
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
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date de retour prévue</label>
                            <input type="date" class="form-control" name="date_retour_prevue">
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
$(document).ready(function() {
    $('#affectationsTable').DataTable({
        "pageLength": 25,
        "order": [[0, 'desc']],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
        }
    });
});

function filterTable() {
    var statut = $('#filterStatut').val().toLowerCase();
    var departement = $('#filterDepartement').val().toLowerCase();
    var dateDebut = $('#filterDateDebut').val();
    var search = $('#searchInput').val().toLowerCase();
    
    $('#affectationsTable tbody tr').each(function() {
        var row = $(this);
        var rowStatut = row.find('td:eq(5)').text().toLowerCase();
        var rowDepartement = row.find('td:eq(2)').text().toLowerCase();
        var rowDate = row.find('td:eq(3)').text();
        var rowText = row.text().toLowerCase();
        
        var show = true;
        
        if (statut && !rowStatut.includes(statut)) show = false;
        if (departement && !rowDepartement.includes(departement)) show = false;
        if (dateDebut && rowDate !== dateDebut) show = false;
        if (search && !rowText.includes(search)) show = false;
        
        show ? row.show() : row.hide();
    });
}

function viewAffectation(id) {
    window.location.href = 'affectation_details.php?id=' + id;
}

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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>



suppression dans rapport

<!-- SUPPRIMEZ CE BLOC COMPLET : -->
<!--
<div class="col-md-6">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Valeur du Parc Informatique</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Valeur Totale</th>
                            <th>Prix Moyen</th>
                        </tr>
                    </thead>
                    <tbody>
                        ...
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
-->
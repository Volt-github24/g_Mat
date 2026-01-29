<?php // Affectation details page
require_once __DIR__ . '/../includes/auth.php'; // Load auth
require_once __DIR__ . '/../includes/functions.php'; // Load helpers
require_once __DIR__ . '/../config/database.php'; // Load database
checkLogin(); // Ensure login
$id = (int)($_GET['id'] ?? 0); // Read id
if ($id <= 0) { // Validate id
    header('Location: affectations.php'); // Redirect invalid id
    exit(); // Stop
} // End id guard
$query = "SELECT a.*, m.code_barre, m.type as materiel_type, m.marque, m.modele, m.numero_serie, CONCAT(u.nom, ' ', u.prenom) as utilisateur_nom, u.departement, u.email, u.telephone, CONCAT(ap.nom, ' ', ap.prenom) as approbateur_nom FROM affectations a JOIN materiels m ON a.materiel_id = m.id JOIN utilisateurs u ON a.utilisateur_id = u.id LEFT JOIN utilisateurs ap ON a.approuve_par = ap.id WHERE a.id = ?"; // Detail query
$stmt = $pdo->prepare($query); // Prepare query
$stmt->execute([$id]); // Execute query
$aff = $stmt->fetch(); // Fetch affectation
if (!$aff) { // Guard missing affectation
    header('Location: affectations.php?error=Affectation+introuvable'); // Redirect missing
    exit(); // Stop
} // End missing guard
$pageTitle = "Detail affectation"; // Page title
require_once __DIR__ . '/../includes/header.php'; // Render header
?> <!-- end php header -->
<div class="d-flex justify-content-between align-items-center mb-4"><!-- header row -->
    <h2><i class="bi bi-eye"></i> Detail affectation #<?php echo $aff['id']; ?></h2><!-- title -->
    <div><!-- header actions -->
        <a href="affectations.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a><!-- back button -->
        <?php if (isGestionnaire()): ?> <!-- edit guard -->
        <a href="#editSection" class="btn btn-warning"><i class="bi bi-pencil"></i> Modifier</a><!-- edit button -->
        <?php endif; ?> <!-- end edit guard -->
    </div><!-- end header actions -->
</div><!-- end header row -->
<div class="card mb-4"><!-- details card -->
    <div class="card-header bg-dark text-white"><!-- details header -->
        <h5 class="mb-0">Informations</h5><!-- details title -->
    </div><!-- end details header -->
    <div class="card-body"><!-- details body -->
        <div class="row"><!-- details row -->
            <div class="col-md-6"><!-- materiel col -->
                <h6>Materiel</h6><!-- materiel title -->
                <p><strong>Code:</strong> <?php echo escape($aff['code_barre']); ?></p><!-- materiel code -->
                <p><strong>Type:</strong> <?php echo escape($aff['materiel_type']); ?></p><!-- materiel type -->
                <p><strong>Marque/Modele:</strong> <?php echo escape($aff['marque'] . ' ' . $aff['modele']); ?></p><!-- materiel model -->
                <p><strong>Numero serie:</strong> <?php echo escape($aff['numero_serie'] ?? '-'); ?></p><!-- materiel serial -->
            </div><!-- end materiel col -->
            <div class="col-md-6"><!-- user col -->
                <h6>Utilisateur</h6><!-- user title -->
                <p><strong>Nom:</strong> <?php echo escape($aff['utilisateur_nom']); ?></p><!-- user name -->
                <p><strong>Departement:</strong> <?php echo escape($aff['departement'] ?? '-'); ?></p><!-- user departement -->
                <p><strong>Email:</strong> <?php echo escape($aff['email'] ?? '-'); ?></p><!-- user email -->
                <p><strong>Telephone:</strong> <?php echo escape($aff['telephone'] ?? '-'); ?></p><!-- user phone -->
            </div><!-- end user col -->
        </div><!-- end details row -->
        <hr><!-- separator -->
        <div class="row"><!-- status row -->
            <div class="col-md-6"><!-- dates col -->
                <p><strong>Date debut:</strong> <?php echo formatDate($aff['date_debut']); ?></p><!-- start date -->
                <p><strong>Date fin:</strong> <?php echo $aff['date_fin'] ? formatDate($aff['date_fin']) : '-'; ?></p><!-- end date -->
                <p><strong>Statut:</strong> <?php displayBadge($aff['statut']); ?></p><!-- status badge -->
            </div><!-- end dates col -->
            <div class="col-md-6"><!-- approval col -->
                <p><strong>Approuve par:</strong> <?php echo escape($aff['approbateur_nom'] ?? '-'); ?></p><!-- approver -->
                <p><strong>Date approbation:</strong> <?php echo $aff['date_approbation'] ? formatDate($aff['date_approbation'], 'd/m/Y H:i') : '-'; ?></p><!-- approval date -->
                <p><strong>Etat depart:</strong> <?php echo escape($aff['etat_depart'] ?? '-'); ?></p><!-- etat depart -->
                <p><strong>Etat retour:</strong> <?php echo escape($aff['etat_retour'] ?? '-'); ?></p><!-- etat retour -->
            </div><!-- end approval col -->
        </div><!-- end status row -->
        <div class="row"><!-- motif row -->
            <div class="col-md-12"><!-- motif col -->
                <p><strong>Motif:</strong> <?php echo escape($aff['motif']); ?></p><!-- motif -->
                <p><strong>Observations:</strong> <?php echo escape($aff['observations'] ?? '-'); ?></p><!-- observations -->
            </div><!-- end motif col -->
        </div><!-- end motif row -->
    </div><!-- end details body -->
</div><!-- end details card -->
<?php if (isGestionnaire()): ?> <!-- edit section guard -->
<div class="card" id="editSection"><!-- edit card -->
    <div class="card-header bg-warning text-white"><!-- edit header -->
        <h5 class="mb-0">Modifier affectation</h5><!-- edit title -->
    </div><!-- end edit header -->
    <div class="card-body"><!-- edit body -->
        <form action="process_affectation.php" method="POST"><!-- edit form -->
            <input type="hidden" name="action" value="edit"><!-- edit action -->
            <input type="hidden" name="id" value="<?php echo $aff['id']; ?>"><!-- edit id -->
            <div class="row"><!-- edit row -->
                <div class="col-md-6 mb-3"><!-- date debut col -->
                    <label class="form-label">Date debut *</label><!-- date debut label -->
                    <input type="date" class="form-control" name="date_debut" value="<?php echo escape($aff['date_debut']); ?>" required><!-- date debut input -->
                </div><!-- end date debut col -->
                <div class="col-md-6 mb-3"><!-- date fin col -->
                    <label class="form-label">Date fin</label><!-- date fin label -->
                    <input type="date" class="form-control" name="date_fin" value="<?php echo escape($aff['date_fin']); ?>"><!-- date fin input -->
                </div><!-- end date fin col -->
            </div><!-- end edit row -->
            <div class="mb-3"><!-- motif group -->
                <label class="form-label">Motif *</label><!-- motif label -->
                <textarea class="form-control" name="motif" rows="3" required><?php echo escape($aff['motif']); ?></textarea><!-- motif input -->
            </div><!-- end motif group -->
            <div class="row"><!-- etat row -->
                <div class="col-md-6 mb-3"><!-- etat depart col -->
                    <label class="form-label">Etat depart</label><!-- etat depart label -->
                    <select class="form-select" name="etat_depart"><!-- etat depart select -->
                        <option value="bon" <?php echo ($aff['etat_depart'] ?? '') === 'bon' ? 'selected' : ''; ?>>Bon</option><!-- etat bon -->
                        <option value="neuf" <?php echo ($aff['etat_depart'] ?? '') === 'neuf' ? 'selected' : ''; ?>>Neuf</option><!-- etat neuf -->
                        <option value="moyen" <?php echo ($aff['etat_depart'] ?? '') === 'moyen' ? 'selected' : ''; ?>>Moyen</option><!-- etat moyen -->
                        <option value="mauvais" <?php echo ($aff['etat_depart'] ?? '') === 'mauvais' ? 'selected' : ''; ?>>Mauvais</option><!-- etat mauvais -->
                    </select><!-- end etat depart select -->
                </div><!-- end etat depart col -->
                <div class="col-md-6 mb-3"><!-- etat retour col -->
                    <label class="form-label">Etat retour</label><!-- etat retour label -->
                    <select class="form-select" name="etat_retour"><!-- etat retour select -->
                        <option value="" <?php echo empty($aff['etat_retour']) ? 'selected' : ''; ?>>-</option><!-- etat empty -->
                        <option value="bon" <?php echo ($aff['etat_retour'] ?? '') === 'bon' ? 'selected' : ''; ?>>Bon</option><!-- etat bon -->
                        <option value="neuf" <?php echo ($aff['etat_retour'] ?? '') === 'neuf' ? 'selected' : ''; ?>>Neuf</option><!-- etat neuf -->
                        <option value="moyen" <?php echo ($aff['etat_retour'] ?? '') === 'moyen' ? 'selected' : ''; ?>>Moyen</option><!-- etat moyen -->
                        <option value="mauvais" <?php echo ($aff['etat_retour'] ?? '') === 'mauvais' ? 'selected' : ''; ?>>Mauvais</option><!-- etat mauvais -->
                    </select><!-- end etat retour select -->
                </div><!-- end etat retour col -->
            </div><!-- end etat row -->
            <div class="mb-3"><!-- observations group -->
                <label class="form-label">Observations</label><!-- observations label -->
                <textarea class="form-control" name="observations" rows="2"><?php echo escape($aff['observations'] ?? ''); ?></textarea><!-- observations input -->
            </div><!-- end observations group -->
            <div class="d-flex justify-content-between"><!-- form actions -->
                <a href="affectations.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a><!-- back link -->
                <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Enregistrer</button><!-- submit button -->
            </div><!-- end form actions -->
        </form><!-- end edit form -->
    </div><!-- end edit body -->
</div><!-- end edit card -->
<?php else: ?> <!-- no edit guard -->
<div class="alert alert-info">Vous n'avez pas les droits pour modifier cette affectation.</div><!-- no edit info -->
<?php endif; ?> <!-- end edit guard -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?> <!-- include footer -->

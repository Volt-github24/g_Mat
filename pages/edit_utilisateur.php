<?php // Edit user page ?>
require_once __DIR__ . '/../includes/auth.php'; // Load auth
require_once __DIR__ . '/../includes/functions.php'; // Load helpers
require_once __DIR__ . '/../config/database.php'; // Load database
checkLogin(); // Ensure login
if (!isGestionnaire()) { // Guard access
    header('Location: dashboard.php?error=Acces non autorise'); // Redirect unauthorized
    exit(); // Stop
} // End access guard
$id = (int)($_GET['id'] ?? 0); // Read id
if ($id <= 0) { // Validate id
    header('Location: utilisateurs.php'); // Redirect invalid id
    exit(); // Stop
} // End id guard
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?"); // Prepare fetch
$stmt->execute([$id]); // Execute fetch
$user = $stmt->fetch(); // Read user
if (!$user) { // Guard missing user
    header('Location: utilisateurs.php?error=Utilisateur+introuvable'); // Redirect missing user
    exit(); // Stop
} // End missing user guard
$pageTitle = "Modifier Utilisateur"; // Page title
require_once __DIR__ . '/../includes/header.php'; // Render header
?> <!-- end php header -->
<div class="container-fluid"> <!-- container -->
    <div class="row"> <!-- row -->
        <div class="col-md-8 offset-md-2"> <!-- column -->
            <div class="card"> <!-- card -->
                <div class="card-header bg-warning text-white"> <!-- card header -->
                    <h4 class="mb-0"><i class="bi bi-pencil"></i> Modifier Utilisateur</h4> <!-- header title -->
                </div> <!-- end card header -->
                <div class="card-body"> <!-- card body -->
                    <form action="process_utilisateur.php" method="POST"> <!-- edit form -->
                        <input type="hidden" name="action" value="edit"> <!-- edit action -->
                        <input type="hidden" name="id" value="<?php echo $id; ?>"> <!-- user id -->
                        <div class="row mb-3"> <!-- name row -->
                            <div class="col-md-6"> <!-- nom col -->
                                <label class="form-label">Nom *</label> <!-- nom label -->
                                <input type="text" class="form-control" name="nom" value="<?php echo escape($user['nom']); ?>" required> <!-- nom input -->
                            </div> <!-- end nom col -->
                            <div class="col-md-6"> <!-- prenom col -->
                                <label class="form-label">Prenom *</label> <!-- prenom label -->
                                <input type="text" class="form-control" name="prenom" value="<?php echo escape($user['prenom']); ?>" required> <!-- prenom input -->
                            </div> <!-- end prenom col -->
                        </div> <!-- end name row -->
                        <div class="mb-3"> <!-- username group -->
                            <label class="form-label">Nom d'utilisateur *</label> <!-- username label -->
                            <input type="text" class="form-control" name="username" value="<?php echo escape($user['username']); ?>" required> <!-- username input -->
                        </div> <!-- end username group -->
                        <div class="mb-3"> <!-- email group -->
                            <label class="form-label">Email</label> <!-- email label -->
                            <input type="email" class="form-control" name="email" value="<?php echo escape($user['email']); ?>"> <!-- email input -->
                        </div> <!-- end email group -->
                        <div class="row mb-3"> <!-- phone role row -->
                            <div class="col-md-6"> <!-- phone col -->
                                <label class="form-label">Telephone</label> <!-- phone label -->
                                <input type="text" class="form-control" name="telephone" value="<?php echo escape($user['telephone']); ?>"> <!-- phone input -->
                            </div> <!-- end phone col -->
                            <div class="col-md-6"> <!-- poste col -->
                                <label class="form-label">Poste</label> <!-- poste label -->
                                <input type="text" class="form-control" name="poste" value="<?php echo escape($user['poste']); ?>"> <!-- poste input -->
                            </div> <!-- end poste col -->
                        </div> <!-- end phone role row -->
                        <div class="row mb-3"> <!-- departement role row -->
                            <div class="col-md-6"> <!-- departement col -->
                                <label class="form-label">Departement</label> <!-- departement label -->
                                <select class="form-select" name="departement"> <!-- departement select -->
                                    <option value="">Selectionner...</option> <!-- departement empty -->
                                    <option value="Direction Générale" <?php echo $user['departement'] === 'Direction Générale' ? 'selected' : ''; ?>>Direction Générale</option> <!-- departement option -->
                                    <option value="Informatique" <?php echo $user['departement'] === 'Informatique' ? 'selected' : ''; ?>>Informatique</option> <!-- departement option -->
                                    <option value="Finances" <?php echo $user['departement'] === 'Finances' ? 'selected' : ''; ?>>Finances</option> <!-- departement option -->
                                    <option value="Ressources Humaines" <?php echo $user['departement'] === 'Ressources Humaines' ? 'selected' : ''; ?>>Ressources Humaines</option> <!-- departement option -->
                                    <option value="Commercial" <?php echo $user['departement'] === 'Commercial' ? 'selected' : ''; ?>>Commercial</option> <!-- departement option -->
                                    <option value="Technique" <?php echo $user['departement'] === 'Technique' ? 'selected' : ''; ?>>Technique</option> <!-- departement option -->
                                    <option value="Administration" <?php echo $user['departement'] === 'Administration' ? 'selected' : ''; ?>>Administration</option> <!-- departement option -->
                                </select> <!-- end departement select -->
                            </div> <!-- end departement col -->
                            <div class="col-md-6"> <!-- role col -->
                                <label class="form-label">Role *</label> <!-- role label -->
                                <select class="form-select" name="role" required> <!-- role select -->
                                    <option value="employe" <?php echo $user['role'] === 'employe' ? 'selected' : ''; ?>>Employe</option> <!-- role option -->
                                    <option value="gestionnaire" <?php echo $user['role'] === 'gestionnaire' ? 'selected' : ''; ?>>Gestionnaire</option> <!-- role option -->
                                    <?php if (isAdmin()): ?> <!-- admin guard -->
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option> <!-- role option -->
                                    <?php endif; ?> <!-- end admin guard -->
                                </select> <!-- end role select -->
                            </div> <!-- end role col -->
                        </div> <!-- end departement role row -->
                        <div class="row mb-3"> <!-- status row -->
                            <div class="col-md-6"> <!-- status col -->
                                <label class="form-label">Statut</label> <!-- status label -->
                                <select class="form-select" name="actif"> <!-- status select -->
                                    <option value="1" <?php echo $user['actif'] ? 'selected' : ''; ?>>Actif</option> <!-- status option -->
                                    <option value="0" <?php echo !$user['actif'] ? 'selected' : ''; ?>>Inactif</option> <!-- status option -->
                                </select> <!-- end status select -->
                            </div> <!-- end status col -->
                        </div> <!-- end status row -->
                        <div class="row mb-3"> <!-- password row -->
                            <div class="col-md-6"> <!-- password col -->
                                <label class="form-label">Nouveau mot de passe</label> <!-- password label -->
                                <input type="password" class="form-control" name="new_password" placeholder="Laisser vide pour ne pas changer"> <!-- password input -->
                            </div> <!-- end password col -->
                            <div class="col-md-6"> <!-- confirm col -->
                                <label class="form-label">Confirmation</label> <!-- confirm label -->
                                <input type="password" class="form-control" name="new_password_confirm" placeholder="Confirmer le mot de passe"> <!-- confirm input -->
                            </div> <!-- end confirm col -->
                        </div> <!-- end password row -->
                        <div class="d-flex justify-content-between"> <!-- action row -->
                            <a href="utilisateurs.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Retour</a> <!-- back link -->
                            <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Mettre a jour</button> <!-- submit button -->
                        </div> <!-- end action row -->
                    </form> <!-- end edit form -->
                </div> <!-- end card body -->
            </div> <!-- end card -->
        </div> <!-- end column -->
    </div> <!-- end row -->
</div> <!-- end container -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?> <!-- include footer -->

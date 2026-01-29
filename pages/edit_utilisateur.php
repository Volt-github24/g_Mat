<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

checkLogin();

// Seuls les gestionnaires et admins
if (!isGestionnaire()) {
    header('Location: dashboard.php?error=Accès non autorisé');
    exit();
}

// Récupération de l'ID
$id = $_GET['id'] ?? 0;
$id = (int)$id;

if ($id <= 0) {
    header('Location: utilisateurs.php');
    exit();
}

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();


if (!$user) {
    header('Location: utilisateurs.php?error=Utilisateur+introuvable');
    exit();
}

$pageTitle = "Modifier Utilisateur";
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil"></i> Modifier Utilisateur
                    </h4>
                </div>

                <div class="card-body">
                    <form action="process_utilisateur.php" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom"
                                       value="<?php echo $user['nom']; ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Prénom *</label>
                                <input type="text" class="form-control" name="prenom"
                                       value="<?php echo $user['prenom']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur *</label>
                            <input type="text" class="form-control" name="username"
                                   value="<?php echo escape($user['username']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?php echo escape($user['email']); ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="text" class="form-control" name="telephone"
                                       value="<?php echo escape($user['telephone']); ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Poste</label>
                                <input type="text" class="form-control" name="poste"
                                       value="<?php echo escape($user['poste']); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Département</label>
                                <select class="form-select" name="departement">
                                    <option value="">Sélectionner...</option>
                                    <?php
                                    $departements = [
                                        'Direction Générale',
                                        'Informatique',
                                        'Finances',
                                        'Ressources Humaines',
                                        'Commercial',
                                        'Technique',
                                        'Administration'
                                    ];
                                    foreach ($departements as $dep):
                                    ?>
                                        <option value="<?php echo $dep; ?>"
                                            <?php echo ($user['departement'] === $dep) ? 'selected' : ''; ?>>
                                            <?php echo $dep; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Rôle *</label>
                                <select class="form-select" name="role" required>
                                    <option value="employe" <?php echo $user['role'] === 'employe' ? 'selected' : ''; ?>>Employé</option>
                                    <option value="gestionnaire" <?php echo $user['role'] === 'gestionnaire' ? 'selected' : ''; ?>>Gestionnaire</option>
                                    <?php if (isAdmin()): ?>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="actif">
                                    <option value="1" <?php echo $user['actif'] ? 'selected' : ''; ?>>Actif</option>
                                    <option value="0" <?php echo !$user['actif'] ? 'selected' : ''; ?>>Inactif</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" name="new_password"
                                       placeholder="Laisser vide pour ne pas changer">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Confirmation</label>
                                <input type="password" class="form-control" name="new_password_confirm"
                                       placeholder="Confirmer le mot de passe">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="utilisateurs.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

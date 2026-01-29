<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

checkLogin();

// Seuls les gestionnaires et admins
if (!isGestionnaire()) {
    header('Location: dashboard.php?error=Accès non autorisé');
    exit();
} 

$pageTitle = "Gestion des Utilisateurs";
require_once __DIR__ . '/../includes/header.php';

// Récupérer les utilisateurs
$query = "SELECT * FROM utilisateurs ORDER BY nom, prenom";
$utilisateurs = $pdo->query($query)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-gear"></i> Gestion des Utilisateurs</h2>
    
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-person-plus"></i> Nouvel Utilisateur
        </button>
    </div>
</div>

<!-- Tableau des utilisateurs -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="utilisateursTable">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom & Prénom</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Département</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <strong><?php echo escape($user['nom'] . ' ' . $user['prenom']); ?></strong><br>
                            <small class="text-muted"><?php echo escape($user['poste'] ?? 'Non défini'); ?></small>
                        </td>
                        <td><?php echo escape($user['username']); ?></td>
                        <td><?php echo escape($user['email'] ?? '-'); ?></td>
                        <td><?php echo escape($user['departement'] ?? '-'); ?></td>
                        <td>
                            <?php displayBadge($user['role'], 'role'); ?>
                        </td>
                        <td>
                            <?php if ($user['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                        onclick="editUser(<?php echo $user['id']; ?>)"
                                        title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-outline-warning" 
                                        onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['actif'] ? '0' : '1'; ?>)"
                                        title="<?php echo $user['actif'] ? 'Désactiver' : 'Activer'; ?>">
                                    <i class="bi bi-power"></i>
                                </button>
                                
                                <button class="btn btn-outline-danger" 
                                        onclick="deleteUser(<?php echo $user['id']; ?>)"
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

<!-- Modal Nouvel Utilisateur -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Nouvel Utilisateur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_utilisateur.php" method="POST" id="addForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" name="username" required>
                        <small class="text-muted">Utilisé pour la connexion</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmation *</label>
                            <input type="password" class="form-control" name="password_confirm" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" name="telephone">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Poste</label>
                            <input type="text" class="form-control" name="poste">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Département</label>
                            <select class="form-select" name="departement">
                                <option value="">Sélectionner...</option>
                                <option value="Direction Générale">Direction Générale</option>
                                <option value="Informatique">Informatique</option>
                                <option value="Finances">Finances</option>
                                <option value="Ressources Humaines">Ressources Humaines</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Technique">Technique</option>
                                <option value="Administration">Administration</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rôle *</label>
                            <select class="form-select" name="role" required>
                                <option value="employe">Employé</option>
                                <option value="gestionnaire">Gestionnaire</option>
                                <?php if (isAdmin()): ?>
                                <option value="admin">Administrateur</option>
                                <?php endif; ?>
                            </select>
                        </div>
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

<script>
$(document).ready(function() {
    $('#utilisateursTable').DataTable({
        "pageLength": 25,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
        }
    });
    
    // Validation du formulaire
    $('#addForm').submit(function(e) {
        var password = $('input[name="password"]').val();
        var confirm = $('input[name="password_confirm"]').val();
        
        if (password !== confirm) {
            alert('Les mots de passe ne correspondent pas !');
            e.preventDefault();
            return false;
        }
        
        if (password.length < 6) {
            alert('Le mot de passe doit contenir au moins 6 caractères !');
            e.preventDefault();
            return false;
        }
    });
});

function editUser(id) {
    window.location.href = 'edit_utilisateur.php?id=' + id;
}

function toggleUserStatus(id, newStatus) {
    var action = newStatus ? 'activer' : 'désactiver';
    if (confirm('Voulez-vous ' + action + ' cet utilisateur ?')) {
        window.location.href = 'process_utilisateur.php?action=toggle_status&id=' + id;
    }
}

function deleteUser(id) {
    if (confirm('Voulez-vous vraiment supprimer cet utilisateur ? Cette action est irréversible.')) {
        window.location.href = 'process_utilisateur.php?action=delete&id=' + id;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
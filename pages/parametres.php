<?php
// pages/parametres.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

checkLogin();

// Seuls les administrateurs peuvent accéder aux paramètres
if (!isAdmin()) {
    header('Location: dashboard.php?error=Accès administrateur requis');
    exit();
}

$pageTitle = "Paramètres du Système";
require_once __DIR__ . '/../includes/header.php';

// Messages de succès/erreur
$message = '';
$error = '';

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = "Tous les champs sont requis";
            } elseif ($new_password !== $confirm_password) {
                $error = "Les nouveaux mots de passe ne correspondent pas";
            } elseif (strlen($new_password) < 6) {
                $error = "Le mot de passe doit contenir au moins 6 caractères";
            } else {
                // Vérifier le mot de passe actuel
                $stmt = $pdo->prepare("SELECT password FROM utilisateurs WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($current_password, $user['password'])) {
                    // Mettre à jour le mot de passe
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $_SESSION['user_id']]);
                    
                    $message = "Mot de passe changé avec succès";
                } else {
                    $error = "Mot de passe actuel incorrect";
                }
            }
            break;
            
        case 'update_settings':
            $nom_site = $_POST['nom_site'] ?? '';
            $email_systeme = $_POST['email_systeme'] ?? '';
            $items_par_page = $_POST['items_par_page'] ?? 25;
            
            // Ici, vous pourriez sauvegarder dans une table de configuration
            $message = "Paramètres mis à jour (fonctionnalité à implémenter)";
            break;
    }
}

// Récupérer les statistiques pour affichage
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM materiels) as total_materiels,
        (SELECT COUNT(*) FROM utilisateurs) as total_utilisateurs,
        (SELECT COUNT(*) FROM affectations WHERE statut = 'en_attente') as demandes_attente,
        (SELECT COUNT(*) FROM materiels WHERE statut = 'maintenance') as en_maintenance
")->fetch();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gear"></i> Paramètres du Système</h2>
        <div class="text-muted">
            <i class="bi bi-person-badge"></i> <?php echo escape($_SESSION['role']); ?>
        </div>
    </div>
    
    <!-- Messages -->
    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?php echo escape($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle"></i> <?php echo escape($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Colonne gauche : Statistiques et informations -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations Système</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Version :</strong> 1.0.0<br>
                        <strong>Dernière mise à jour :</strong> <?php echo date('d/m/Y'); ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-graph-up"></i> Statistiques</h6>
                        <div class="row">
                            <div class="col-6">
                                <small>Matériels :</small><br>
                                <strong><?php echo $stats['total_materiels']; ?></strong>
                            </div>
                            <div class="col-6">
                                <small>Utilisateurs :</small><br>
                                <strong><?php echo $stats['total_utilisateurs']; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-shield-check"></i> Sécurité</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Connexions :</strong><br>
                        <small>Dernière connexion : <?php 
                            $stmt = $pdo->prepare("SELECT derniere_connexion FROM utilisateurs WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $last_login = $stmt->fetchColumn();
                            echo $last_login ? formatDate($last_login, 'd/m/Y H:i') : 'Jamais';
                        ?></small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <small><i class="bi bi-exclamation-triangle"></i> Pensez à changer régulièrement votre mot de passe</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Colonne droite : Formulaires -->
        <div class="col-md-8">
            <!-- Changement de mot de passe -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-key"></i> Changement de Mot de Passe</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel *</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nouveau mot de passe *</label>
                                <input type="password" class="form-control" name="new_password" required>
                                <small class="text-muted">Minimum 6 caractères</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmation *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Paramètres généraux -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-sliders"></i> Paramètres Généraux</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom du système</label>
                            <input type="text" class="form-control" name="nom_site" 
                                   value="Gestion Matériel ONACC" placeholder="Nom de votre système">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email système</label>
                            <input type="email" class="form-control" name="email_systeme" 
                                   placeholder="email@organisation.org">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Éléments par page</label>
                            <select class="form-select" name="items_par_page">
                                <option value="10">10 éléments</option>
                                <option value="25" selected>25 éléments</option>
                                <option value="50">50 éléments</option>
                                <option value="100">100 éléments</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle"></i> 
                                Ces paramètres seront sauvegardés dans une future version avec une table de configuration.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-save"></i> Enregistrer les paramètres
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Actions administratives -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Actions Administratives</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-warning"></i> Zone sensible - Administrateur uniquement</h6>
                        <small>Ces actions peuvent affecter tout le système. Utilisez avec précaution.</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h5><i class="bi bi-database"></i></h5>
                                    <h6>Sauvegarde</h6>
                                    <p class="text-muted small">Créer une sauvegarde de la base de données</p>
                                    <button class="btn btn-outline-warning btn-sm" onclick="alert('Fonctionnalité à implémenter')">
                                        Exécuter
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h5><i class="bi bi-trash"></i></h5>
                                    <h6>Nettoyage</h6>
                                    <p class="text-muted small">Supprimer les anciennes données</p>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="if(confirm('Êtes-vous sûr ? Cette action est irréversible.')) alert('Fonctionnalité à implémenter')">
                                        Exécuter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Journaux système :</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Utilisateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $logs = $pdo->query("
                                        SELECT h.date_action, h.action, CONCAT(u.nom, ' ', u.prenom) as utilisateur
                                        FROM historique h
                                        LEFT JOIN utilisateurs u ON h.utilisateur_id = u.id
                                        ORDER BY h.date_action DESC
                                        LIMIT 5
                                    ")->fetchAll();
                                    
                                    foreach ($logs as $log):
                                    ?>
                                    <tr>
                                        <td><?php echo formatDate($log['date_action'], 'd/m H:i'); ?></td>
                                        <td><?php echo escape($log['action']); ?></td>
                                        <td><?php echo escape($log['utilisateur'] ?? 'Système'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="rapports.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-clock-history"></i> Voir tous les journaux
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once '../includes/auth.php';
checkLogin();

$pageTitle = "Tableau de bord";
require_once '../includes/header.php';

require_once '../config/database.php';

// Récupérer les statistiques
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_materiels,
        SUM(CASE WHEN statut = 'stock' THEN 1 ELSE 0 END) as en_stock,
        SUM(CASE WHEN statut = 'affecte' THEN 1 ELSE 0 END) as affectes,
        SUM(CASE WHEN statut = 'maintenance' THEN 1 ELSE 0 END) as maintenance,
        (SELECT COUNT(*) FROM utilisateurs) as total_users,
        (SELECT COUNT(*) FROM affectations WHERE date_fin IS NULL) as affectations_actives
    FROM materiels
")->fetch();

// Dernières affectations
$affectations = $pdo->query("
    SELECT a.*, m.type, m.marque, m.modele, 
           CONCAT(u.nom, ' ', u.prenom) as utilisateur,
           u.departement
    FROM affectations a
    JOIN materiels m ON a.materiel_id = m.id
    JOIN utilisateurs u ON a.utilisateur_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<!-- Contenu de la page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-speedometer2"></i> Tableau de Bord</h2>
    <div class="text-muted">
        <?php echo date('d/m/Y'); ?>
    </div>
</div>

<!-- Métriques -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Matériels</h5>
                        <h2><?php echo $stats['total_materiels']; ?></h2>
                    </div>
                    <div class="display-4">
                        <i class="bi bi-pc-display"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">En Stock</h5>
                        <h2><?php echo $stats['en_stock']; ?></h2>
                    </div>
                    <div class="display-4">
                        <i class="bi bi-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Affectés</h5>
                        <h2><?php echo $stats['affectes']; ?></h2>
                    </div>
                    <div class="display-4">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Utilisateurs</h5>
                        <h2><?php echo $stats['total_users']; ?></h2>
                    </div>
                    <div class="display-4">
                        <i class="bi bi-person-badge"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dernières affectations -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Dernières Affectations</h5>
                <a href="affectations.php" class="btn btn-sm btn-primary">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Matériel</th>
                                <th>Utilisateur</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($affectations as $aff): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($aff['type']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($aff['marque'] . ' ' . $aff['modele']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($aff['utilisateur']); ?><br>
                                    <small class="text-muted"><?php echo escape($aff['departement']); ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($aff['date_debut'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = [
                                        'en_attente' => 'warning',
                                        'approuve' => 'success',
                                        'refuse' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class[$aff['statut']]; ?>">
                                        <?php echo $aff['statut']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="affectation_details.php?id=<?php echo $aff['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Alertes</h5>
            </div>
            <div class="card-body">
                <?php
                // Alertes de maintenance
                $alertes = $pdo->query("
                    SELECT m.*, DATEDIFF(CURDATE(), date_acquisition) as age_jours
                    FROM materiels m
                    WHERE etat = 'mauvais' 
                       OR statut = 'maintenance'
                    LIMIT 5
                ")->fetchAll();
                
                if (count($alertes) > 0): 
                    foreach ($alertes as $alerte): 
                ?>
                <div class="alert alert-warning alert-dismissible fade show mb-2" role="alert">
                    <strong><?php echo htmlspecialchars($alerte['type']); ?></strong><br>
                    <small>
                        <?php echo $alerte['etat'] == 'mauvais' ? 'État critique' : 'En maintenance'; ?>
                    </small>
                </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Aucune alerte
                </div>
                <?php endif; ?>
                
                <!-- Actions rapides -->
                <div class="mt-4">
                    <h6>Actions rapides</h6>
                    <div class="d-grid gap-2">
                        <?php if (isGestionnaire()): ?>
                        <a href="materiels.php?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Ajouter matériel
                        </a>
                        <a href="affectations.php?action=new" class="btn btn-success">
                            <i class="bi bi-person-plus"></i> Nouvelle affectation
                        </a>
                        <?php endif; ?>
                        <a href="rapports.php" class="btn btn-info">
                            <i class="bi bi-printer"></i> Imprimer rapport
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
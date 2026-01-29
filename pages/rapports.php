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

$pageTitle = "Rapports et Statistiques";
require_once __DIR__ . '/../includes/header.php';

// Récupérer les statistiques
$stats = $pdo->query("SELECT * FROM vue_statistiques")->fetch();

// Matériels par type
$materiels_par_type = $pdo->query("
    SELECT type, COUNT(*) as count, 
           SUM(CASE WHEN statut = 'stock' THEN 1 ELSE 0 END) as en_stock,
           SUM(CASE WHEN statut = 'affecte' THEN 1 ELSE 0 END) as affectes
    FROM materiels 
    GROUP BY type 
    ORDER BY count DESC
")->fetchAll();


// Affectations par département
$affectations_par_dept = $pdo->query("
    SELECT 
        COALESCE(u.departement, 'Non attribué') as departement,
        COUNT(DISTINCT a.id) as nombre_affectations,
        COUNT(DISTINCT CASE WHEN a.statut = 'approuve' AND a.date_fin IS NULL THEN a.id END) as affectations_actives,
        COUNT(DISTINCT CASE WHEN a.statut = 'en_attente' THEN a.id END) as en_attente
    FROM affectations a
    LEFT JOIN utilisateurs u ON a.utilisateur_id = u.id
    GROUP BY u.departement
    HAVING COUNT(DISTINCT a.id) > 0
    ORDER BY nombre_affectations DESC
")->fetchAll();

// Utilisation
$totals = calculateTotalAffectations($affectations_par_dept);
$total_affectations = $totals['total_affectations'];
$total_actives = $totals['total_actives'];
$total_attente = $totals['total_attente'];

// Valeur du parc par catégorie
$valeur_parc = $pdo->query("
    SELECT type, 
           COUNT(*) as quantite, 
           SUM(prix) as valeur_totale,
           AVG(prix) as prix_moyen
    FROM materiels 
    WHERE prix IS NOT NULL
    GROUP BY type
    ORDER BY valeur_totale DESC
")->fetchAll();
$total_affectations = 0;
$total_actives = 0;
$total_attente = 0;
// Dernières activités
$activites = $pdo->query("
    SELECT * FROM vue_historique_complet 
    ORDER BY date_action DESC 
    LIMIT 10
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> Rapports et Statistiques</h2>
    
    <div>
        <button type="button" class="btn btn-primary" onclick="exportToPDF()">
            <i class="bi bi-file-pdf"></i> PDF
        </button>
        <button type="button" class="btn btn-success" onclick="exportToExcel()">
            <i class="bi bi-file-excel"></i> Excel
        </button>
        <button type="button" class="btn btn-info" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimer
        </button>
    </div>
</div>

<!-- Filtres période -->
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Période d'analyse</h5>
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Date début</label>
                <input type="date" class="form-control" id="dateDebut" 
                       value="<?php echo date('Y-01-01'); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Date fin</label>
                <input type="date" class="form-control" id="dateFin" 
                       value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Type de rapport</label>
                <select class="form-select" id="typeRapport">
                    <option value="global">Global</option>
                    <option value="materiels">Matériels</option>
                    <option value="affectations">Affectations</option>
                    <option value="valeur">Valeur du parc</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" onclick="generateReport()">
                    <i class="bi bi-filter"></i> Générer le rapport
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques principales -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Matériels</h5>
                <h2><?php echo $stats['total_materiels']; ?></h2>
                <div class="small">
                    <?php echo $stats['en_stock']; ?> en stock
                    | <?php echo $stats['affectes']; ?> affectés
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Utilisateurs Actifs</h5>
                <h2><?php echo $stats['utilisateurs_actifs']; ?></h2>
                <div class="small">
                    <?php echo $stats['demandes_en_attente']; ?> demandes en attente
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">En Maintenance</h5>
                <h2><?php echo $stats['maintenance']; ?></h2>
                <div class="small">
                    <?php 
                    $pourcentage = $stats['total_materiels'] > 0 ? 
                        round(($stats['maintenance'] / $stats['total_materiels']) * 100, 1) : 0;
                    echo $pourcentage; ?>% du parc
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Période</h5>
                <h2><?php echo date('Y'); ?></h2>
                <div class="small">
                    Rapport au <?php echo date('d/m/Y'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques et tableaux -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Répartition des Matériels par Type</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Total</th>
                                <th>En stock</th>
                                <th>Affectés</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiels_par_type as $row): 
                                $pourcentage = $stats['total_materiels'] > 0 ? 
                                    round(($row['count'] / $stats['total_materiels']) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo escape(ucfirst($row['type'])); ?></td>
                                <td><?php echo $row['count']; ?></td>
                                <td><?php echo $row['en_stock']; ?></td>
                                <td><?php echo $row['affectes']; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo $pourcentage; ?>%">
                                            <?php echo $pourcentage; ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
   

<div class="row">
    <div class="col-md-8">
       <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Affectations par Département</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th>Total Affectations</th>
                            <th>Actives</th>
                            <th>En attente</th>
                            <th>Pourcentage</th>
                            <th>Graphique</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($affectations_par_dept as $row): 
                            $pourcentage = $total_affectations > 0 ? 
                                round(($row['nombre_affectations'] / $total_affectations) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?php echo escape($row['departement']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo $row['nombre_affectations']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?php echo $row['affectations_actives']; ?></span>
                            </td>
                            <td>
                                <span class="badge bg-warning"><?php echo $row['en_attente']; ?></span>
                            </td>
                            <td><?php echo $pourcentage; ?>%</td>
                            <td>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-info" 
                                         style="width: <?php echo min($pourcentage, 100); ?>%"
                                         title="<?php echo $pourcentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td>TOTAL</td>
                            <td><?php echo $total_affectations; ?></td>
                            <td>
                                <?php 
                                $total_actives = array_sum(array_column($affectations_par_dept, 'affectations_actives'));
                                echo $total_actives;
                                ?>
                            </td>
                            <td>
                                <?php 
                                $total_attente = array_sum(array_column($affectations_par_dept, 'en_attente'));
                                echo $total_attente;
                                ?>
                            </td>
                            <td>100%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
    
    <div class="col-md-4
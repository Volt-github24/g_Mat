<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Vérifier la connexion
checkLogin();

// Définir le titre de la page
$pageTitle = $pageTitle ?? 'Tableau de bord';

// Récupérer les infos utilisateur
$user = getUserInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONACC Gestion Matériel - <?php echo $pageTitle ?? 'Tableau de bord'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Style personnalisé -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .sidebar {
            background-color: #2c3e50;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover {
            background-color: #34495e;
            color: #3498db;
        }
        .sidebar .nav-link.active {
            background-color: #3498db;
            color: white;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .user-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation principale -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-pc-display-horizontal"></i> ONACC Gestion Matériel
            </a>
            
            <div class="d-flex align-items-center">
                <div class="user-info me-3">
                    <span class="text-white">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($user['nom']); ?>
                        <small class="badge bg-info"><?php echo $user['role']; ?></small>
                    </span>
                </div>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h5 class="text-center mb-4">
                        <i class="bi bi-menu-button-wide"></i> Menu
                    </h5>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Tableau de bord
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'materiels.php' ? 'active' : ''; ?>" 
                               href="materiels.php">
                                <i class="bi bi-pc-display"></i> Matériels
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'affectations.php' ? 'active' : ''; ?>" 
                               href="affectations.php">
                                <i class="bi bi-people"></i> Affectations
                            </a>
                        </li>
                        
                        <?php if (isGestionnaire()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'utilisateurs.php' ? 'active' : ''; ?>" 
                               href="utilisateurs.php">
                                <i class="bi bi-person-gear"></i> Utilisateurs
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rapports.php' ? 'active' : ''; ?>" 
                               href="rapports.php">
                                <i class="bi bi-graph-up"></i> Rapports
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'parametres.php' ? 'active' : ''; ?>" 
                               href="parametres.php">
                                <i class="bi bi-gear"></i> Paramètres
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <hr class="bg-light">
                    
                    <div class="mt-4">
                        <h6>Statistiques rapides</h6>
                        <?php
                        require_once '../config/database.php';
                        $stats = $pdo->query("
                            SELECT 
                                (SELECT COUNT(*) FROM materiels WHERE statut = 'stock') as en_stock,
                                (SELECT COUNT(*) FROM affectations WHERE statut = 'en_attente') as demandes
                        ")->fetch();
                        ?>
                        <div class="small">
                            <div class="d-flex justify-content-between">
                                <span>En stock:</span>
                                <span class="badge bg-info"><?php echo $stats['en_stock']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Demandes:</span>
                                <span class="badge bg-warning"><?php echo $stats['demandes']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="col-md-10 p-4">
                <!-- Le contenu spécifique de chaque page vient ici -->
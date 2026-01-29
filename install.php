<?php
// Fichier d'installation automatique
if (file_exists('config/database.php')) {
    die('Le système semble déjà être installé.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'];
    $dbname = $_POST['dbname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        
        // Lire et exécuter le script SQL
        $sql = file_get_contents('database.sql');
        $pdo->exec($sql);
        
        // Créer le fichier de configuration
        $config = <<<EOT
<?php
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$username');
define('DB_PASS', '$password');

try {
    \$pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException \$e) {
    die("Erreur de connexion : " . \$e->getMessage());
}
?>
EOT;
        
        file_put_contents('config/database.php', $config);
        
        // Créer un fichier de succès
        file_put_contents('install_complete.txt', date('Y-m-d H:i:s'));
        
        echo '<div class="alert alert-success">Installation réussie ! <a href="index.php">Accéder au système</a></div>';
        
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Erreur : ' . $e->getMessage() . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation - Gestion Matériel ONACC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Installation du Système</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Veuillez configurer la connexion à la base de données</p>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Hôte MySQL</label>
                                <input type="text" class="form-control" name="host" value="localhost" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nom de la base</label>
                                <input type="text" class="form-control" name="dbname" value="gestion_materiel_onacc" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" name="username" value="root" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" name="password">
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <strong>Identifiants par défaut après installation :</strong><br>
                                    Administrateur : admin / password<br>
                                    Gestionnaire : gestionnaire / password<br>
                                    Employé : dupont / password
                                </small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Installer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'g_materiel_onacc');
define('DB_USER', 'gm_user');
define('DB_PASS', 'gm_pass_2024');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}


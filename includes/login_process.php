<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Rechercher l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ? AND actif = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nom'] = $user['nom'] . ' ' . $user['prenom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['departement'] = $user['departement'];
            $_SESSION['email'] = $user['email'];
            
            // Mettre à jour la date de dernière connexion
            $update_stmt = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            
            // Rediriger vers le dashboard
            header('Location: ../pages/dashboard.php');
            exit();
            
        } else {
            // Identifiants incorrects
            header('Location: ../index.php?error=Nom d\'utilisateur ou mot de passe incorrect');
            exit();
        }
        
    } catch (PDOException $e) {
        header('Location: ../index.php?error=Erreur de connexion à la base de données');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
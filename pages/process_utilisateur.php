<?php
// pages/process_utilisateur.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

checkLogin();

if (!isGestionnaire()) {
    header('Location: dashboard.php?error=Accès non autorisé');
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validation
            $errors = [];
            
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['password_confirm'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $departement = trim($_POST['departement'] ?? '');
            $poste = trim($_POST['poste'] ?? '');
            $role = $_POST['role'] ?? 'employe';
            
            // Validation
            if (empty($nom)) $errors[] = "Le nom est requis";
            if (empty($prenom)) $errors[] = "Le prénom est requis";
            if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
            if (empty($password)) $errors[] = "Le mot de passe est requis";
            if ($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";
            if (strlen($password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
            
            // Vérifier si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Ce nom d'utilisateur existe déjà";
            }
            
            if (empty($errors)) {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO utilisateurs 
                        (username, password, nom, prenom, email, departement, poste, role)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $username,
                        $hash,
                        $nom,
                        $prenom,
                        $email,
                        $departement,
                        $poste,
                        $role
                    ]);
                    
                    $_SESSION['notification'] = [
                        'type' => 'success',
                        'message' => 'Utilisateur ajouté avec succès'
                    ];
                    
                } catch (PDOException $e) {
                    $_SESSION['notification'] = [
                        'type' => 'error',
                        'message' => 'Erreur: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => implode('<br>', $errors)
                ];
            }
            
            header('Location: utilisateurs.php');
            exit();
        }
        break;
        
    // ... autres actions (edit, delete, toggle_status)
}
?>
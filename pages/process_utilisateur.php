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
    case 'edit': // Edit user
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Only allow POST
            $errors = []; // Init errors
            $id = (int)($_POST['id'] ?? 0); // Read id
            $nom = trim($_POST['nom'] ?? ''); // Read nom
            $prenom = trim($_POST['prenom'] ?? ''); // Read prenom
            $username = trim($_POST['username'] ?? ''); // Read username
            $email = trim($_POST['email'] ?? ''); // Read email
            $telephone = trim($_POST['telephone'] ?? ''); // Read telephone
            $departement = trim($_POST['departement'] ?? ''); // Read departement
            $poste = trim($_POST['poste'] ?? ''); // Read poste
            $role = $_POST['role'] ?? 'employe'; // Read role
            $actif = isset($_POST['actif']) ? (int)$_POST['actif'] : 1; // Read status
            $new_password = $_POST['new_password'] ?? ''; // Read new password
            $confirm_password = $_POST['new_password_confirm'] ?? ''; // Read confirmation
            if ($id <= 0) { // Validate id
                $errors[] = "Utilisateur invalide"; // Add error
            } // End id validation
            if (empty($nom)) $errors[] = "Le nom est requis"; // Validate nom
            if (empty($prenom)) $errors[] = "Le prenom est requis"; // Validate prenom
            if (empty($username)) $errors[] = "Le nom d'utilisateur est requis"; // Validate username
            if ($new_password !== '') { // Validate new password
                if ($new_password !== $confirm_password) { // Check confirmation
                    $errors[] = "Les mots de passe ne correspondent pas"; // Add error
                } // End confirmation check
                if (strlen($new_password) < 6) { // Check length
                    $errors[] = "Le mot de passe doit contenir au moins 6 caracteres"; // Add error
                } // End length check
            } // End password validation
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE username = ? AND id <> ?"); // Check username unique
            $stmt->execute([$username, $id]); // Execute check
            if ($stmt->fetch()) { // Username exists
                $errors[] = "Ce nom d'utilisateur existe deja"; // Add error
            } // End username check
            if (empty($errors)) { // No validation errors
                try { // Start update try
                    if ($new_password !== '') { // Update with password
                        $hash = password_hash($new_password, PASSWORD_DEFAULT); // Hash password
                        $stmt = $pdo->prepare("UPDATE utilisateurs SET username = ?, password = ?, nom = ?, prenom = ?, email = ?, telephone = ?, departement = ?, poste = ?, role = ?, actif = ? WHERE id = ?"); // Prepare update with password
                        $stmt->execute([$username, $hash, $nom, $prenom, $email, $telephone, $departement, $poste, $role, $actif, $id]); // Execute update
                    } else { // Update without password
                        $stmt = $pdo->prepare("UPDATE utilisateurs SET username = ?, nom = ?, prenom = ?, email = ?, telephone = ?, departement = ?, poste = ?, role = ?, actif = ? WHERE id = ?"); // Prepare update without password
                        $stmt->execute([$username, $nom, $prenom, $email, $telephone, $departement, $poste, $role, $actif, $id]); // Execute update
                    } // End password update
                    $_SESSION['notification'] = [ // Build success notice
                        'type' => 'success', // Success type
                        'message' => 'Utilisateur mis a jour avec succes' // Success message
                    ]; // End notice
                } catch (PDOException $e) { // Handle update error
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Erreur: ' . $e->getMessage() // Error message
                    ]; // End notice
                } // End update try
            } else { // Validation errors
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => implode('<br>', $errors) // Error message
                ]; // End notice
            } // End validation branch
            header('Location: utilisateurs.php'); // Back to list
            exit(); // Stop
        } // End POST guard
        break; // End edit case
    default: // Fallback action
        header('Location: utilisateurs.php'); // Back to list
        exit(); // Stop
}
?>
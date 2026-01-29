<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();

if (!isGestionnaire()) {
    header('Location: dashboard.php?error=Accès non autorisé');
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Récupérer les données
                $type = $_POST['type'];
                $marque = $_POST['marque'];
                $modele = $_POST['modele'];
                $numero_serie = $_POST['numero_serie'] ?? null;
                $caracteristiques = $_POST['caracteristiques'] ?? null;
                $date_acquisition = $_POST['date_acquisition'] ?? null;
                $prix = $_POST['prix'] ?? null;
                $etat = $_POST['etat'] ?? 'bon';
                $statut = $_POST['statut'] ?? 'stock';
                $localisation = $_POST['localisation'] ?? null;
                $commentaires = $_POST['commentaires'] ?? null;
                
                // Générer le code barre
                $code_barre = generateBarcode($type);
                
                // Insérer dans la base
                $stmt = $pdo->prepare("
                    INSERT INTO materiels 
                    (code_barre, type, marque, modele, numero_serie, caracteristiques, 
                     date_acquisition, prix, etat, statut, localisation, commentaires)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $code_barre,
                    $type,
                    $marque,
                    $modele,
                    $numero_serie,
                    $caracteristiques,
                    $date_acquisition,
                    $prix,
                    $etat,
                    $statut,
                    $localisation,
                    $commentaires
                ]);
                
                $materiel_id = $pdo->lastInsertId();
                
                // Logger l'action
                logAction($materiel_id, 'CREATION', "Matériel créé: $marque $modele");
                
                // Notification
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Matériel ajouté avec succès'
                ];
                
                header('Location: materiels.php');
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
                header('Location: materiels.php');
                exit();
            }
        }
        break;
    case 'import': // Handle Excel/CSV import
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Only allow POST
            $rowsJson = $_POST['rows'] ?? ''; // Read JSON payload
            $rows = json_decode($rowsJson, true); // Decode rows
            if (!is_array($rows)) { // Validate JSON
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Fichier import invalide' // Error message
                ]; // End notice
                header('Location: materiels.php'); // Back to list
                exit(); // Stop
            } // End JSON validation
            $normalizeDate = function ($value) { // Normalize date helper
                if ($value === null || $value === '') { // Empty date guard
                    return null; // Keep null
                } // End empty date guard
                $timestamp = strtotime((string)$value); // Parse date
                return $timestamp ? date('Y-m-d', $timestamp) : null; // Format date
            }; // End normalizeDate
            $inserted = 0; // Count inserted rows
            $skipped = 0; // Count skipped rows
            try { // Start import transaction
                $pdo->beginTransaction(); // Open transaction
                $stmt = $pdo->prepare("INSERT INTO materiels (code_barre, type, marque, modele, numero_serie, caracteristiques, date_acquisition, prix, etat, statut, localisation, commentaires) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); // Prepare insert
                foreach ($rows as $row) { // Loop rows
                    $row = array_change_key_case($row, CASE_LOWER); // Normalize keys
                    $type = trim((string)($row['type'] ?? '')); // Read type
                    $marque = trim((string)($row['marque'] ?? '')); // Read marque
                    $modele = trim((string)($row['modele'] ?? '')); // Read modele
                    if ($type === '' || $marque === '' || $modele === '') { // Require core fields
                        $skipped++; // Increment skipped
                        continue; // Skip row
                    } // End required fields check
                    $numero_serie = trim((string)($row['numero_serie'] ?? '')); // Read serial
                    $caracteristiques = trim((string)($row['caracteristiques'] ?? '')); // Read specs
                    $date_acquisition = $normalizeDate($row['date_acquisition'] ?? null); // Normalize date
                    $prix = $row['prix'] ?? null; // Read price
                    $prix = is_numeric($prix) ? $prix : null; // Validate price
                    $etat = strtolower(trim((string)($row['etat'] ?? 'bon'))); // Read condition
                    $etat = in_array($etat, ['neuf', 'bon', 'moyen', 'mauvais'], true) ? $etat : 'bon'; // Validate condition
                    $statut = strtolower(trim((string)($row['statut'] ?? 'stock'))); // Read status
                    $statut = in_array($statut, ['stock', 'affecte', 'maintenance', 'perdu'], true) ? $statut : 'stock'; // Validate status
                    $localisation = trim((string)($row['localisation'] ?? '')); // Read location
                    $commentaires = trim((string)($row['commentaires'] ?? '')); // Read comments
                    $code_barre = generateBarcode($type); // Generate barcode
                    $stmt->execute([ // Execute insert
                        $code_barre, // Barcode
                        $type, // Type
                        $marque, // Marque
                        $modele, // Modele
                        $numero_serie !== '' ? $numero_serie : null, // Serial
                        $caracteristiques !== '' ? $caracteristiques : null, // Specs
                        $date_acquisition, // Acquisition date
                        $prix, // Price
                        $etat, // Condition
                        $statut, // Status
                        $localisation !== '' ? $localisation : null, // Location
                        $commentaires !== '' ? $commentaires : null // Comments
                    ]); // End execute
                    $inserted++; // Increment inserted
                } // End loop
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => "Import termine: $inserted ajoute(s), $skipped ignore(s)" // Success message
                ]; // End success notice
                header('Location: materiels.php'); // Back to list
                exit(); // Stop
            } catch (PDOException $e) { // Handle import error
                $pdo->rollBack(); // Rollback
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur import: ' . $e->getMessage() // Error message
                ]; // End error notice
                header('Location: materiels.php'); // Back to list
                exit(); // Stop
            } // End import try
        } // End POST guard
        header('Location: materiels.php'); // Fallback redirect
        exit(); // Stop
        break; // End import case
    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            
            try {
                // Récupérer les données
                $type = $_POST['type'];
                $marque = $_POST['marque'];
                $modele = $_POST['modele'];
                $numero_serie = $_POST['numero_serie'] ?? null;
                $caracteristiques = $_POST['caracteristiques'] ?? null;
                $date_acquisition = $_POST['date_acquisition'] ?? null;
                $prix = $_POST['prix'] ?? null;
                $etat = $_POST['etat'];
                $statut = $_POST['statut'];
                $localisation = $_POST['localisation'] ?? null;
                $commentaires = $_POST['commentaires'] ?? null;
                
                // Mettre à jour
                $stmt = $pdo->prepare("
                    UPDATE materiels SET
                    type = ?, marque = ?, modele = ?, numero_serie = ?, 
                    caracteristiques = ?, date_acquisition = ?, prix = ?,
                    etat = ?, statut = ?, localisation = ?, commentaires = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $type, $marque, $modele, $numero_serie,
                    $caracteristiques, $date_acquisition, $prix,
                    $etat, $statut, $localisation, $commentaires,
                    $id
                ]);
                
                // Logger l'action
                logAction($id, 'MODIFICATION', "Matériel mis à jour");
                
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Matériel mis à jour avec succès'
                ];
                
                header('Location: materiels.php');
                exit();
                
            } catch (PDOException $e) {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
                header('Location: edit_materiel.php?id=' . $id);
                exit();
            }
        }
        break;
        
    case 'delete':
        $id = $_GET['id'] ?? 0;
        
        if ($id > 0) {
            try {
                // Vérifier si le matériel est affecté
                $check_stmt = $pdo->prepare("SELECT statut FROM materiels WHERE id = ?");
                $check_stmt->execute([$id]);
                $materiel = $check_stmt->fetch();
                
                if ($materiel && $materiel['statut'] === 'affecte') {
                    $_SESSION['notification'] = [
                        'type' => 'error',
                        'message' => 'Impossible de supprimer un matériel affecté'
                    ];
                    header('Location: materiels.php');
                    exit();
                }
                
                // Supprimer
                $stmt = $pdo->prepare("DELETE FROM materiels WHERE id = ?");
                $stmt->execute([$id]);
                
                // Logger l'action
                logAction($id, 'SUPPRESSION', "Matériel supprimé");
                
                $_SESSION['notification'] = [
                    'type' => 'success',
                    'message' => 'Matériel supprimé avec succès'
                ];
                
            } catch (PDOException $e) {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => 'Erreur: ' . $e->getMessage()
                ];
            }
        }
        
        header('Location: materiels.php');
        exit();
        break;
        
    default:
        header('Location: materiels.php');
        exit();
}
?>
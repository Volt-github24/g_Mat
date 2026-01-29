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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Only allow POST
            $id = (int)$_POST['id']; // Read id
            try { // Start edit try
                $pdo->beginTransaction(); // Open transaction
                $current_stmt = $pdo->prepare("SELECT statut FROM materiels WHERE id = ?"); // Fetch current status
                $current_stmt->execute([$id]); // Execute fetch
                $current = $current_stmt->fetch(); // Read current row
                $previousStatut = $current['statut'] ?? null; // Store previous status
                // Récupérer les données
                $type = $_POST['type']; // Read type
                $marque = $_POST['marque']; // Read marque
                $modele = $_POST['modele']; // Read modele
                $numero_serie = $_POST['numero_serie'] ?? null; // Read serial
                $caracteristiques = $_POST['caracteristiques'] ?? null; // Read specs
                $date_acquisition = $_POST['date_acquisition'] ?? null; // Read acquisition date
                $prix = $_POST['prix'] ?? null; // Read price
                $etat = $_POST['etat']; // Read condition
                $statut = $_POST['statut']; // Read status
                $localisation = $_POST['localisation'] ?? null; // Read location
                $commentaires = $_POST['commentaires'] ?? null; // Read comments
                // Mettre à jour
                $stmt = $pdo->prepare("UPDATE materiels SET type = ?, marque = ?, modele = ?, numero_serie = ?, caracteristiques = ?, date_acquisition = ?, prix = ?, etat = ?, statut = ?, localisation = ?, commentaires = ? WHERE id = ?"); // Prepare update
                $stmt->execute([ // Execute update
                    $type, $marque, $modele, $numero_serie, // Core fields
                    $caracteristiques, $date_acquisition, $prix, // Extra fields
                    $etat, $statut, $localisation, $commentaires, // Status fields
                    $id // Material id
                ]); // End execute
                if ($previousStatut === 'affecte' && $statut !== 'affecte') { // Status changed away from affecte
                    $delete_stmt = $pdo->prepare("DELETE FROM affectations WHERE materiel_id = ? AND statut IN ('en_attente', 'approuve') AND date_fin IS NULL"); // Delete active affectations
                    $delete_stmt->execute([$id]); // Execute delete
                } // End status change guard
                logAction($id, 'MODIFICATION', "Matériel mis à jour"); // Log action
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => 'Matériel mis à jour avec succès' // Success message
                ]; // End notice
                header('Location: materiels.php'); // Back to list
                exit(); // Stop
            } catch (PDOException $e) { // Handle edit error
                $pdo->rollBack(); // Rollback transaction
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur: ' . $e->getMessage() // Error message
                ]; // End notice
                header('Location: edit_materiel.php?id=' . $id); // Back to edit
                exit(); // Stop
            } // End edit try
        } // End POST guard
        break;
        
    case 'delete':
        $id = $_GET['id'] ?? 0;
        
        if ($id > 0) { // Validate id
            try { // Start delete try
                $pdo->beginTransaction(); // Open transaction
                $check_stmt = $pdo->prepare("SELECT statut FROM materiels WHERE id = ?"); // Fetch material
                $check_stmt->execute([$id]); // Execute fetch
                $materiel = $check_stmt->fetch(); // Read material
                $active_stmt = $pdo->prepare("SELECT COUNT(*) FROM affectations WHERE materiel_id = ? AND statut IN ('en_attente', 'approuve') AND date_fin IS NULL"); // Count active affectations
                $active_stmt->execute([$id]); // Execute active check
                $activeCount = (int)$active_stmt->fetchColumn(); // Read active count
                if ($materiel && ($materiel['statut'] === 'affecte' || $activeCount > 0)) { // Block active delete
                    $pdo->rollBack(); // Rollback transaction
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Impossible de supprimer un matériel avec affectations actives' // Error message
                    ]; // End notice
                    header('Location: materiels.php'); // Back to list
                    exit(); // Stop
                } // End active guard
                $delete_aff = $pdo->prepare("DELETE FROM affectations WHERE materiel_id = ?"); // Delete related affectations
                $delete_aff->execute([$id]); // Execute affectations delete
                $stmt = $pdo->prepare("DELETE FROM materiels WHERE id = ?"); // Delete material
                $stmt->execute([$id]); // Execute delete
                logAction($id, 'SUPPRESSION', "Matériel supprimé"); // Log action
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => 'Matériel supprimé avec succès' // Success message
                ]; // End notice
            } catch (PDOException $e) { // Handle delete error
                $pdo->rollBack(); // Rollback transaction
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur: ' . $e->getMessage() // Error message
                ]; // End notice
            } // End delete try
        } // End id guard
        
        header('Location: materiels.php');
        exit();
        break;
        
    default:
        header('Location: materiels.php');
        exit();
}
?>
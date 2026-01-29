<?php
// pages/process_affectation.php
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
            $materiel_id = (int)$_POST['materiel_id'];
            $utilisateur_id = (int)$_POST['utilisateur_id'];
            $date_debut = $_POST['date_debut'];
            $motif = trim($_POST['motif']);
            $etat_depart = $_POST['etat_depart'] ?? 'bon';
            
            if ($materiel_id && $utilisateur_id && $date_debut && $motif) {
                try {
                    // Démarrer une transaction
                    $pdo->beginTransaction();
                    
                    // 1. Créer l'affectation
                    $stmt = $pdo->prepare("
                        INSERT INTO affectations 
                        (materiel_id, utilisateur_id, date_debut, motif, etat_depart, statut)
                        VALUES (?, ?, ?, ?, ?, 'en_attente')
                    ");
                    
                    $stmt->execute([
                        $materiel_id,
                        $utilisateur_id,
                        $date_debut,
                        $motif,
                        $etat_depart
                    ]);
                    
                    // 2. Mettre à jour le statut du matériel
                    $stmt = $pdo->prepare("
                        UPDATE materiels 
                        SET statut = 'affecte', updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$materiel_id]);
                    
                    // 3. Logger l'action
                    logAction($materiel_id, 'NOUVELLE_AFFECTATION', "Motif: $motif");
                    
                    $pdo->commit();
                    
                    $_SESSION['notification'] = [
                        'type' => 'success',
                        'message' => 'Affectation créée avec succès'
                    ];
                    
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['notification'] = [
                        'type' => 'error',
                        'message' => 'Erreur: ' . $e->getMessage()
                    ];
                }
            } else {
                $_SESSION['notification'] = [
                    'type' => 'error',
                    'message' => 'Tous les champs requis doivent être remplis'
                ];
            }
            
            header('Location: affectations.php');
            exit();
        }
        break;
    case 'edit': // Edit affectation
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Only allow POST
            $id = (int)($_POST['id'] ?? 0); // Read id
            $date_debut = $_POST['date_debut'] ?? ''; // Read start date
            $date_fin = $_POST['date_fin'] ?? null; // Read end date
            $motif = trim($_POST['motif'] ?? ''); // Read motif
            $etat_depart = $_POST['etat_depart'] ?? 'bon'; // Read start condition
            $etat_retour = $_POST['etat_retour'] ?? null; // Read return condition
            $observations = trim($_POST['observations'] ?? ''); // Read observations
            if ($id > 0 && $date_debut !== '' && $motif !== '') { // Validate required
                try { // Start update try
                    $stmt = $pdo->prepare("UPDATE affectations SET date_debut = ?, date_fin = ?, motif = ?, etat_depart = ?, etat_retour = ?, observations = ? WHERE id = ?"); // Prepare update
                    $stmt->execute([ // Execute update
                        $date_debut, // Start date
                        $date_fin ?: null, // End date
                        $motif, // Motif
                        $etat_depart, // Condition start
                        $etat_retour ?: null, // Condition return
                        $observations !== '' ? $observations : null, // Observations
                        $id // Affectation id
                    ]); // End execute
                    $_SESSION['notification'] = [ // Build success notice
                        'type' => 'success', // Success type
                        'message' => 'Affectation modifiee avec succes' // Success message
                    ]; // End success notice
                } catch (PDOException $e) { // Handle error
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Erreur: ' . $e->getMessage() // Error message
                    ]; // End error notice
                } // End update try
            } else { // Missing required data
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Champs requis manquants' // Error message
                ]; // End error notice
            } // End validation
            header('Location: affectations.php'); // Back to list
            exit(); // Stop
        } // End POST guard
        break; // End edit case
    case 'delete': // Delete affectation
        $id = (int)($_GET['id'] ?? 0); // Read id
        if ($id > 0) { // Validate id
            try { // Start delete try
                $pdo->beginTransaction(); // Open transaction
                $stmt = $pdo->prepare("SELECT materiel_id, statut, date_fin FROM affectations WHERE id = ?"); // Fetch affectation
                $stmt->execute([$id]); // Execute fetch
                $aff = $stmt->fetch(); // Read affectation
                if (!$aff) { // Not found
                    $pdo->rollBack(); // Rollback
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Affectation introuvable' // Error message
                    ]; // End error notice
                    header('Location: affectations.php'); // Back to list
                    exit(); // Stop
                } // End not found
                $materiel_id = (int)$aff['materiel_id']; // Materiel id
                $stmt = $pdo->prepare("DELETE FROM affectations WHERE id = ?"); // Prepare delete
                $stmt->execute([$id]); // Execute delete
                if ($materiel_id > 0) { // Validate materiel id
                    $check = $pdo->prepare("SELECT COUNT(*) FROM affectations WHERE materiel_id = ? AND statut IN ('en_attente', 'approuve') AND date_fin IS NULL"); // Check active
                    $check->execute([$materiel_id]); // Execute count
                    $activeCount = (int)$check->fetchColumn(); // Read count
                    if ($activeCount === 0) { // No active affectation
                        $update = $pdo->prepare("UPDATE materiels SET statut = 'stock', updated_at = NOW() WHERE id = ?"); // Update materiel
                        $update->execute([$materiel_id]); // Execute update
                    } // End active check
                } // End materiel check
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => 'Affectation supprimee avec succes' // Success message
                ]; // End success notice
            } catch (PDOException $e) { // Handle delete error
                $pdo->rollBack(); // Rollback
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur: ' . $e->getMessage() // Error message
                ]; // End error notice
            } // End delete try
        } else { // Invalid id
            $_SESSION['notification'] = [ // Build error notice
                'type' => 'error', // Error type
                'message' => 'ID invalide' // Error message
            ]; // End error notice
        } // End id guard
        header('Location: affectations.php'); // Back to list
        exit(); // Stop
        break; // End delete case
    case 'approve': // Approve affectation
        $id = (int)($_GET['id'] ?? 0); // Read id
        if ($id > 0) { // Validate id
            try { // Start approve try
                $pdo->beginTransaction(); // Open transaction
                $stmt = $pdo->prepare("SELECT materiel_id FROM affectations WHERE id = ?"); // Fetch materiel
                $stmt->execute([$id]); // Execute fetch
                $aff = $stmt->fetch(); // Read affectation
                if (!$aff) { // Not found
                    $pdo->rollBack(); // Rollback
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Affectation introuvable' // Error message
                    ]; // End error notice
                    header('Location: affectations.php'); // Back to list
                    exit(); // Stop
                } // End not found
                $materiel_id = (int)$aff['materiel_id']; // Materiel id
                $stmt = $pdo->prepare("UPDATE affectations SET statut = 'approuve', approuve_par = ?, date_approbation = NOW() WHERE id = ?"); // Update affectation
                $stmt->execute([$_SESSION['user_id'] ?? null, $id]); // Execute update
                if ($materiel_id > 0) { // Validate materiel id
                    $stmt = $pdo->prepare("UPDATE materiels SET statut = 'affecte', updated_at = NOW() WHERE id = ?"); // Update materiel
                    $stmt->execute([$materiel_id]); // Execute update
                } // End materiel update
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => 'Affectation approuvee' // Success message
                ]; // End success notice
            } catch (PDOException $e) { // Handle approve error
                $pdo->rollBack(); // Rollback
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur: ' . $e->getMessage() // Error message
                ]; // End error notice
            } // End approve try
        } else { // Invalid id
            $_SESSION['notification'] = [ // Build error notice
                'type' => 'error', // Error type
                'message' => 'ID invalide' // Error message
            ]; // End error notice
        } // End id guard
        header('Location: affectations.php'); // Back to list
        exit(); // Stop
        break; // End approve case
    case 'reject': // Reject affectation
        $id = (int)($_GET['id'] ?? 0); // Read id
        $raison = trim($_GET['raison'] ?? ''); // Read reason
        if ($id > 0) { // Validate id
            try { // Start reject try
                $pdo->beginTransaction(); // Open transaction
                $stmt = $pdo->prepare("SELECT materiel_id FROM affectations WHERE id = ?"); // Fetch materiel
                $stmt->execute([$id]); // Execute fetch
                $aff = $stmt->fetch(); // Read affectation
                if (!$aff) { // Not found
                    $pdo->rollBack(); // Rollback
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Affectation introuvable' // Error message
                    ]; // End error notice
                    header('Location: affectations.php'); // Back to list
                    exit(); // Stop
                } // End not found
                $materiel_id = (int)$aff['materiel_id']; // Materiel id
                $suffix = $raison !== '' ? ' Refus: ' . $raison : ''; // Build reason suffix
                $stmt = $pdo->prepare("UPDATE affectations SET statut = 'refuse', approuve_par = ?, date_approbation = NOW(), observations = CONCAT(IFNULL(observations, ''), ?) WHERE id = ?"); // Update affectation
                $stmt->execute([$_SESSION['user_id'] ?? null, $suffix, $id]); // Execute update
                if ($materiel_id > 0) { // Validate materiel id
                    $stmt = $pdo->prepare("UPDATE materiels SET statut = 'stock', updated_at = NOW() WHERE id = ?"); // Update materiel
                    $stmt->execute([$materiel_id]); // Execute update
                } // End materiel update
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => 'Affectation refusee' // Success message
                ]; // End success notice
            } catch (PDOException $e) { // Handle reject error
                $pdo->rollBack(); // Rollback
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur: ' . $e->getMessage() // Error message
                ]; // End error notice
            } // End reject try
        } else { // Invalid id
            $_SESSION['notification'] = [ // Build error notice
                'type' => 'error', // Error type
                'message' => 'ID invalide' // Error message
            ]; // End error notice
        } // End id guard
        header('Location: affectations.php'); // Back to list
        exit(); // Stop
        break; // End reject case
    case 'return': // Return material
        $id = (int)($_GET['id'] ?? 0); // Read id
        $etat = strtolower(trim($_GET['etat'] ?? 'bon')); // Read return condition
        $etat = in_array($etat, ['neuf', 'bon', 'moyen', 'mauvais'], true) ? $etat : 'bon'; // Validate condition
        if ($id > 0) { // Validate id
            try { // Start return try
                $pdo->beginTransaction(); // Open transaction
                $stmt = $pdo->prepare("SELECT materiel_id FROM affectations WHERE id = ?"); // Fetch materiel
                $stmt->execute([$id]); // Execute fetch
                $aff = $stmt->fetch(); // Read affectation
                if (!$aff) { // Not found
                    $pdo->rollBack(); // Rollback
                    $_SESSION['notification'] = [ // Build error notice
                        'type' => 'error', // Error type
                        'message' => 'Affectation introuvable' // Error message
                    ]; // End error notice
                    header('Location: affectations.php'); // Back to list
                    exit(); // Stop
                } // End not found
                $materiel_id = (int)$aff['materiel_id']; // Materiel id
                $date_retour = date('Y-m-d'); // Return date
                $stmt = $pdo->prepare("UPDATE affectations SET statut = 'retourne', date_fin = ?, etat_retour = ? WHERE id = ?"); // Update affectation
                $stmt->execute([$date_retour, $etat, $id]); // Execute update
                if ($materiel_id > 0) { // Validate materiel id
                    $stmt = $pdo->prepare("UPDATE materiels SET statut = 'stock', etat = ?, updated_at = NOW() WHERE id = ?"); // Update materiel
                    $stmt->execute([$etat, $materiel_id]); // Execute update
                } // End materiel update
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => 'Retour enregistre' // Success message
                ]; // End success notice
            } catch (PDOException $e) { // Handle return error
                $pdo->rollBack(); // Rollback
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur: ' . $e->getMessage() // Error message
                ]; // End error notice
            } // End return try
        } else { // Invalid id
            $_SESSION['notification'] = [ // Build error notice
                'type' => 'error', // Error type
                'message' => 'ID invalide' // Error message
            ]; // End error notice
        } // End id guard
        header('Location: affectations.php'); // Back to list
        exit(); // Stop
        break; // End return case
    case 'import': // Import affectations
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Only allow POST
            $rowsJson = $_POST['rows'] ?? ''; // Read JSON payload
            $rows = json_decode($rowsJson, true); // Decode rows
            if (!is_array($rows)) { // Validate JSON
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Fichier import invalide' // Error message
                ]; // End error notice
                header('Location: affectations.php'); // Back to list
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
            try { // Start import try
                $pdo->beginTransaction(); // Open transaction
                $insert = $pdo->prepare("INSERT INTO affectations (materiel_id, utilisateur_id, date_debut, date_fin, motif, etat_depart, etat_retour, observations, statut, approuve_par, date_approbation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); // Prepare insert
                $findMateriel = $pdo->prepare("SELECT id FROM materiels WHERE code_barre = ?"); // Lookup materiel
                $findUserByUsername = $pdo->prepare("SELECT id FROM utilisateurs WHERE username = ?"); // Lookup user by username
                $findUserByEmail = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?"); // Lookup user by email
                $updateMateriel = $pdo->prepare("UPDATE materiels SET statut = ?, updated_at = NOW() WHERE id = ?"); // Prepare materiel update
                foreach ($rows as $row) { // Loop rows
                    $row = array_change_key_case($row, CASE_LOWER); // Normalize keys
                    $materiel_id = (int)($row['materiel_id'] ?? 0); // Read materiel id
                    if ($materiel_id === 0 && !empty($row['code_barre'])) { // Lookup by code
                        $findMateriel->execute([trim((string)$row['code_barre'])]); // Execute lookup
                        $materiel_id = (int)$findMateriel->fetchColumn(); // Read lookup result
                    } // End materiel lookup
                    $utilisateur_id = (int)($row['utilisateur_id'] ?? 0); // Read user id
                    if ($utilisateur_id === 0 && !empty($row['username'])) { // Lookup by username
                        $findUserByUsername->execute([trim((string)$row['username'])]); // Execute lookup
                        $utilisateur_id = (int)$findUserByUsername->fetchColumn(); // Read lookup result
                    } // End username lookup
                    if ($utilisateur_id === 0 && !empty($row['email'])) { // Lookup by email
                        $findUserByEmail->execute([trim((string)$row['email'])]); // Execute lookup
                        $utilisateur_id = (int)$findUserByEmail->fetchColumn(); // Read lookup result
                    } // End email lookup
                    $date_debut = $normalizeDate($row['date_debut'] ?? null); // Normalize start date
                    $date_fin = $normalizeDate($row['date_fin'] ?? null); // Normalize end date
                    $motif = trim((string)($row['motif'] ?? '')); // Read motif
                    if ($materiel_id === 0 || $utilisateur_id === 0 || !$date_debut || $motif === '') { // Validate required
                        $skipped++; // Increment skipped
                        continue; // Skip row
                    } // End validation
                    $etat_depart = strtolower(trim((string)($row['etat_depart'] ?? 'bon'))); // Read start condition
                    $etat_depart = in_array($etat_depart, ['neuf', 'bon', 'moyen', 'mauvais'], true) ? $etat_depart : 'bon'; // Validate start condition
                    $etat_retour = strtolower(trim((string)($row['etat_retour'] ?? ''))); // Read return condition
                    $etat_retour = in_array($etat_retour, ['neuf', 'bon', 'moyen', 'mauvais'], true) ? $etat_retour : null; // Validate return condition
                    $statut = strtolower(trim((string)($row['statut'] ?? 'en_attente'))); // Read status
                    $statut = in_array($statut, ['en_attente', 'approuve', 'refuse', 'retourne'], true) ? $statut : 'en_attente'; // Validate status
                    $observations = trim((string)($row['observations'] ?? '')); // Read observations
                    $approuve_par = in_array($statut, ['approuve', 'refuse'], true) ? ($_SESSION['user_id'] ?? null) : null; // Set approver
                    $date_approbation = in_array($statut, ['approuve', 'refuse'], true) ? date('Y-m-d H:i:s') : null; // Set approval date
                    $insert->execute([ // Execute insert
                        $materiel_id, // Materiel id
                        $utilisateur_id, // User id
                        $date_debut, // Start date
                        $date_fin, // End date
                        $motif, // Motif
                        $etat_depart, // Start condition
                        $etat_retour, // Return condition
                        $observations !== '' ? $observations : null, // Observations
                        $statut, // Status
                        $approuve_par, // Approver
                        $date_approbation // Approval date
                    ]); // End insert
                    $newMaterielStatus = in_array($statut, ['en_attente', 'approuve'], true) ? 'affecte' : 'stock'; // Compute materiel status
                    $updateMateriel->execute([$newMaterielStatus, $materiel_id]); // Update materiel status
                    $inserted++; // Increment inserted
                } // End loop
                $pdo->commit(); // Commit transaction
                $_SESSION['notification'] = [ // Build success notice
                    'type' => 'success', // Success type
                    'message' => "Import termine: $inserted ajoute(s), $skipped ignore(s)" // Success message
                ]; // End success notice
                header('Location: affectations.php'); // Back to list
                exit(); // Stop
            } catch (PDOException $e) { // Handle import error
                $pdo->rollBack(); // Rollback
                $_SESSION['notification'] = [ // Build error notice
                    'type' => 'error', // Error type
                    'message' => 'Erreur import: ' . $e->getMessage() // Error message
                ]; // End error notice
                header('Location: affectations.php'); // Back to list
                exit(); // Stop
            } // End import try
        } // End POST guard
        header('Location: affectations.php'); // Fallback redirect
        exit(); // Stop
        break; // End import case
    default: // Fallback action
        header('Location: affectations.php'); // Back to list
        exit(); // Stop
}
?>
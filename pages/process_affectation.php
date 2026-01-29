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
}
?>
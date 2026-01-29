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
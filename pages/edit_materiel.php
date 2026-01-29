<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

checkLogin();

if (!isGestionnaire()) {
    header('Location: dashboard.php?error=Accès non autorisé');
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id == 0) {
    header('Location: materiels.php');
    exit();
}

// Récupérer le matériel
$stmt = $pdo->prepare("SELECT * FROM materiels WHERE id = ?");
$stmt->execute([$id]);
$materiel = $stmt->fetch();

if (!$materiel) {
    header('Location: materiels.php?error=Matériel non trouvé');
    exit();
}

$pageTitle = "Modifier Matériel";
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil"></i> Modifier Matériel
                    </h4>
                </div>
                <div class="card-body">
                    <form action="process_materiel.php" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Code Barre</label>
                                <input type="text" class="form-control" value="<?php echo escape($materiel['code_barre']); ?>" readonly>
                                <small class="text-muted">Code généré automatiquement</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type de matériel *</label>
                                <select class="form-select" name="type" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="ordinateur" <?php echo $materiel['type'] == 'ordinateur' ? 'selected' : ''; ?>>Ordinateur portable</option>
                                    <option value="ordinateur_bureau" <?php echo $materiel['type'] == 'ordinateur_bureau' ? 'selected' : ''; ?>>Ordinateur bureau</option>
                                    <option value="ecran" <?php echo $materiel['type'] == 'ecran' ? 'selected' : ''; ?>>Écran</option>
                                    <option value="imprimante" <?php echo $materiel['type'] == 'imprimante' ? 'selected' : ''; ?>>Imprimante</option>
                                    <option value="scanner" <?php echo $materiel['type'] == 'scanner' ? 'selected' : ''; ?>>Scanner</option>
                                    <option value="ventilateur" <?php echo $materiel['type'] == 'ventilateur' ? 'selected' : ''; ?>>Ventilateur</option>
                                    <option value="clavier" <?php echo $materiel['type'] == 'clavier' ? 'selected' : ''; ?>>Clavier</option>
                                    <option value="souris" <?php echo $materiel['type'] == 'souris' ? 'selected' : ''; ?>>Souris</option>
                                    <option value="onduleur" <?php echo $materiel['type'] == 'onduleur' ? 'selected' : ''; ?>>Onduleur</option>
                                    <option value="serveur" <?php echo $materiel['type'] == 'serveur' ? 'selected' : ''; ?>>Serveur</option>
                                    <option value="autre" <?php echo $materiel['type'] == 'autre' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Marque *</label>
                                <input type="text" class="form-control" name="marque" 
                                       value="<?php echo escape($materiel['marque']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Modèle *</label>
                                <input type="text" class="form-control" name="modele" 
                                       value="<?php echo escape($materiel['modele']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Numéro de série</label>
                                <input type="text" class="form-control" name="numero_serie" 
                                       value="<?php echo escape($materiel['numero_serie']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'acquisition</label>
                                <input type="date" class="form-control" name="date_acquisition" 
                                       value="<?php echo $materiel['date_acquisition']; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Caractéristiques</label>
                            <textarea class="form-control" name="caracteristiques" rows="3"><?php echo escape($materiel['caracteristiques']); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Prix (FCFA)</label>
                                <input type="number" class="form-control" name="prix" step="0.01"
                                       value="<?php echo $materiel['prix']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">État</label>
                                <select class="form-select" name="etat">
                                    <option value="neuf" <?php echo $materiel['etat'] == 'neuf' ? 'selected' : ''; ?>>Neuf</option>
                                    <option value="bon" <?php echo $materiel['etat'] == 'bon' ? 'selected' : ''; ?>>Bon</option>
                                    <option value="moyen" <?php echo $materiel['etat'] == 'moyen' ? 'selected' : ''; ?>>Moyen</option>
                                    <option value="mauvais" <?php echo $materiel['etat'] == 'mauvais' ? 'selected' : ''; ?>>Mauvais</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="statut">
                                    <option value="stock" <?php echo $materiel['statut'] == 'stock' ? 'selected' : ''; ?>>Stock</option>
                                    <option value="affecte" <?php echo $materiel['statut'] == 'affecte' ? 'selected' : ''; ?>>Affecté</option>
                                    <option value="maintenance" <?php echo $materiel['statut'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="perdu" <?php echo $materiel['statut'] == 'perdu' ? 'selected' : ''; ?>>Perdu</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Localisation actuelle</label>
                            <input type="text" class="form-control" name="localisation" 
                                   value="<?php echo escape($materiel['localisation']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Commentaires</label>
                            <textarea class="form-control" name="commentaires" rows="2"><?php echo escape($materiel['commentaires']); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="materiels.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
// create_views_simple.php - Version simplifiée
require_once 'config/database.php';

echo "<h2>Création des vues (version simplifiée)</h2>";

// Supprimer d'abord les vues existantes pour éviter les erreurs
$views_to_drop = ['vue_statistiques', 'vue_historique_complet', 'vue_materiel_stock', 'vue_affectations_cours'];

foreach ($views_to_drop as $view) {
    try {
        $pdo->exec("DROP VIEW IF EXISTS $view");
        echo "ℹ️ Vue $view supprimée si elle existait<br>";
    } catch (Exception $e) {
        // Ignorer les erreurs
    }
}

echo "<hr>";

// 1. Vue statistiques SIMPLIFIÉE
try {
    $sql = "CREATE VIEW vue_statistiques AS
            SELECT 
                (SELECT COUNT(*) FROM materiels) as total_materiels,
                (SELECT COUNT(*) FROM materiels WHERE statut = 'stock') as en_stock,
                (SELECT COUNT(*) FROM materiels WHERE statut = 'affecte') as affectes,
                (SELECT COUNT(*) FROM materiels WHERE statut = 'maintenance') as maintenance";
    
    $pdo->exec($sql);
    echo "✅ Vue créée : vue_statistiques<br>";
} catch (Exception $e) {
    echo "❌ Erreur vue_statistiques: " . $e->getMessage() . "<br>";
}

// 2. Vue historique COMPLÈTE mais SIMPLE
try {
    $sql = "CREATE VIEW vue_historique_complet AS
            SELECT 
                h.date_action,
                h.action,
                m.code_barre,
                h.details
            FROM historique h
            LEFT JOIN materiels m ON h.materiel_id = m.id
            ORDER BY h.date_action DESC";
    
    $pdo->exec($sql);
    echo "✅ Vue créée : vue_historique_complet<br>";
} catch (Exception $e) {
    echo "❌ Erreur vue_historique_complet: " . $e->getMessage() . "<br>";
    echo "<div style='background:#ffcccc;padding:10px;'>";
    echo "<strong>Débogage :</strong><br>";
    echo "1. Vérifiez que la table 'historique' existe<br>";
    echo "2. Vérifiez que la table 'materiels' existe<br>";
    echo "3. Erreur détaillée: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h3>Test immédiat :</h3>";

// Tester la vue
try {
    $result = $pdo->query("SELECT * FROM vue_historique_complet LIMIT 5");
    $rows = $result->fetchAll();
    
    if (empty($rows)) {
        echo "✅ Vue fonctionne mais aucune donnée<br>";
    } else {
        echo "✅ Vue fonctionne avec " . count($rows) . " ligne(s)<br>";
        echo "<pre>";
        print_r($rows);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ La vue ne fonctionne pas: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Maintenant testez :</strong></p>";
echo "<a href='pages/rapports.php' style='
    background: #28a745;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    display: inline-block;
    margin: 10px 0;
'>📊 Tester la page Rapports</a>";

// Correction alternative pour rapports.php
echo "<hr><h3>Option alternative :</h3>";
echo "<p>Si ça ne marche toujours pas, modifiez directement rapports.php :</p>";
echo "<button onclick=\"copyToClipboard()\" style='background:#007bff;color:white;padding:8px 15px;border:none;border-radius:5px;'>
📋 Copier le code de correction
</button>";

echo "<script>
function copyToClipboard() {
    const code = `<?php
// Dans pages/rapports.php, remplacez la ligne 53 par ceci :
try {
    // Utiliser une requête directe au lieu de la vue
    \\$activites = \\$pdo->query(\\\"
        SELECT 
            h.date_action,
            h.action,
            m.code_barre,
            CONCAT(m.marque, ' ', m.modele) as materiel,
            CONCAT(u.nom, ' ', u.prenom) as utilisateur,
            h.details
        FROM historique h
        LEFT JOIN materiels m ON h.materiel_id = m.id
        LEFT JOIN utilisateurs u ON h.utilisateur_id = u.id
        ORDER BY h.date_action DESC 
        LIMIT 10
    \\\")->fetchAll();
} catch (PDOException \\$e) {
    \\$activites = [];
    echo '<div class=\\\"alert alert-warning\\\">⚠️ L\\\\'historique n\\\\'est pas encore disponible</div>';
}
?>`;
    
    navigator.clipboard.writeText(code).then(() => {
        alert('Code copié ! Collez-le dans rapports.php à la ligne 53');
    });
}
</script>";
?>
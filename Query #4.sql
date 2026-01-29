-- Créer la vue vue_historique_complet
CREATE OR REPLACE VIEW vue_historique_complet AS
SELECT 
    h.id,
    h.date_action,
    h.action,
    h.details,
    m.code_barre,
    m.type as materiel_type,
    m.marque,
    m.modele,
    CONCAT(u.nom, ' ', u.prenom) as utilisateur_nom,
    u.departement
FROM historique h
LEFT JOIN materiels m ON h.materiel_id = m.id
LEFT JOIN utilisateurs u ON h.utilisateur_id = u.id
ORDER BY h.date_action DESC;vue_historique_complet
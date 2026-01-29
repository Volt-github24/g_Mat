-- Création de la base de données
CREATE DATABASE IF NOT EXISTS g_materiel_onacc;
USE g_materiel_onacc;

-- Table des utilisateurs
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telephone VARCHAR(20),
    departement VARCHAR(100),
    poste VARCHAR(100),
    role ENUM('admin', 'gestionnaire', 'employe') DEFAULT 'employe',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME,
    actif BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_departement (departement)
);

-- Table des matériels
CREATE TABLE materiels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code_barre VARCHAR(50) UNIQUE,
    type VARCHAR(50) NOT NULL,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    numero_serie VARCHAR(100),
    caracteristiques TEXT,
    date_acquisition DATE,
    prix DECIMAL(12,2),
    etat ENUM('neuf', 'bon', 'moyen', 'mauvais') DEFAULT 'bon',
    statut ENUM('stock', 'affecte', 'maintenance', 'perdu') DEFAULT 'stock',
    localisation VARCHAR(100),
    commentaires TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_statut (statut),
    INDEX idx_localisation (localisation)
);

-- Table des affectations
CREATE TABLE affectations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    materiel_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    motif TEXT NOT NULL,
    justificatif VARCHAR(255),
    approuve_par INT,
    date_approbation DATETIME,
    statut ENUM('en_attente', 'approuve', 'refuse', 'retourne') DEFAULT 'en_attente',
    etat_depart VARCHAR(50),
    etat_retour VARCHAR(50),
    observations TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materiel_id) REFERENCES materiels(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (approuve_par) REFERENCES utilisateurs(id),
    INDEX idx_statut (statut),
    INDEX idx_date_debut (date_debut)
);

-- Table historique
CREATE TABLE historique (
    id INT PRIMARY KEY AUTO_INCREMENT,
    materiel_id INT,
    utilisateur_id INT,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (materiel_id) REFERENCES materiels(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_action (action),
    INDEX idx_date_action (date_action)
);

-- Table des départements
CREATE TABLE departements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    responsable_id INT,
    localisation VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES utilisateurs(id)
);

-- Table des catégories de matériel
CREATE TABLE categories_materiel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insertion des données par défaut
INSERT INTO utilisateurs (username, password, nom, prenom, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 'admin@onacc.org', 'admin'),
('gestionnaire', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gestionnaire', 'Matériel', 'gestionnaire@onacc.org', 'gestionnaire'),
('dupont', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dupont', 'Jean', 'jean.dupont@onacc.org', 'employe');

INSERT INTO departements (nom, localisation) VALUES
('Direction Générale', 'Bâtiment A, 3ème étage'),
('Informatique', 'Bâtiment B, 2ème étage'),
('Finances', 'Bâtiment A, 1er étage'),
('Ressources Humaines', 'Bâtiment C, 1er étage');

INSERT INTO categories_materiel (nom, description) VALUES
('Ordinateurs', 'PC fixes et portables'),
('Périphériques', 'Écrans, claviers, souris'),
('Imprimantes', 'Imprimantes et scanners'),
('Réseau', 'Routeurs, switchs, câbles'),
('Mobilier', 'Bureaux, chaises, armoires');

INSERT INTO materiels (code_barre, type, marque, modele, numero_serie, caracteristiques, date_acquisition, prix, etat, statut, localisation) VALUES
('ONACC-LAP-001', 'ordinateur', 'Dell', 'Latitude 5420', 'CN-0RXYZ1-64180', 'i7-1165G7, 16GB RAM, 512GB SSD', '2023-01-15', 1200000, 'neuf', 'stock', 'Entrepôt IT'),
('ONACC-ECR-001', 'ecran', 'HP', '24mh', 'CN12345678', '24" Full HD IPS', '2023-02-20', 250000, 'bon', 'affecte', 'Bureau 201'),
('ONACC-IMP-001', 'imprimante', 'Canon', 'PIXMA TS3350', 'CN98765432', 'Multifonction couleur', '2023-03-10', 150000, 'moyen', 'maintenance', 'Atelier'),
('ONACC-VENT-001', 'ventilateur', 'Rowenta', 'VU5660', 'RV2023001', 'Turbo Silence Extreme', '2023-04-05', 75000, 'bon', 'stock', 'Entrepôt général');

-- Créer un trigger pour l'historique
DELIMITER $$
CREATE TRIGGER after_materiel_update
AFTER UPDATE ON materiels
FOR EACH ROW
BEGIN
    INSERT INTO historique (materiel_id, action, details)
    VALUES (
        NEW.id,
        'MODIFICATION',
        CONCAT('Statut: ', OLD.statut, ' → ', NEW.statut, ' | État: ', OLD.etat, ' → ', NEW.etat)
    );
END$$
DELIMITER ;

-- Créer une vue pour les statistiques
CREATE VIEW vue_statistiques AS
SELECT 
    (SELECT COUNT(*) FROM materiels) as total_materiels,
    (SELECT COUNT(*) FROM materiels WHERE statut = 'stock') as en_stock,
    (SELECT COUNT(*) FROM materiels WHERE statut = 'affecte') as affectes,
    (SELECT COUNT(*) FROM materiels WHERE statut = 'maintenance') as maintenance,
    (SELECT COUNT(*) FROM utilisateurs WHERE actif = 1) as utilisateurs_actifs,
    (SELECT COUNT(*) FROM affectations WHERE statut = 'en_attente') as demandes_en_attente;
<?php
// includes/functions.php 
if (!function_exists('isLoggedIn')):

// 1. FONCTIONS D'AUTHENTIFICATION
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isGestionnaire() { return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'gestionnaire']); }
function isEmploye() { return isset($_SESSION['role']) && $_SESSION['role'] === 'employe'; }
function getUserInfo() {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nom' => $_SESSION['nom'] ?? '',
        'prenom' => $_SESSION['prenom'] ?? '',
        'role' => $_SESSION['role'] ?? 'employe',
        'departement' => $_SESSION['departement'] ?? ''
    ];
}

// 2. FONCTIONS MANQUANTES (CAUSE DE L'ERREUR)
function escape($data, $default = '') {
    // Gérer null explicitement
    if ($data === null) {
        return $default;
    }
    
    // Gérer les booléens
    if (is_bool($data)) {
        return $data ? 'true' : 'false';
    }
    
    // Gérer les tableaux
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = escape($value, $default);
        }
        return $data;
    }
    
    // Gérer les objets avec __toString
    if (is_object($data) && method_exists($data, '__toString')) {
        $data = $data->__toString();
    }
    
    // Tout convertir en string
    $string = (string)$data;
    
    // Échapper avec gestion d'erreur
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getBadgeClass($status, $type = 'statut') {
    $classes = [
        'role' => ['admin' => 'bg-danger', 'gestionnaire' => 'bg-warning', 'employe' => 'bg-info']
    ];
    return $classes[$type][$status] ?? 'bg-secondary';
}

function getStatusText($status) {
    $texts = ['admin' => 'Admin', 'gestionnaire' => 'Gestionnaire', 'employe' => 'Employé'];
    return $texts[$status] ?? ucfirst($status);
}

function displayBadge($status, $type = 'statut') {
    $class = getBadgeClass($status, $type);
    $text = getStatusText($status);
    echo '<span class="badge ' . $class . '">' . escape($text) . '</span>';
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date == '0000-00-00') return '-';
    try { $dateObj = new DateTime($date); return $dateObj->format($format); } 
    catch (Exception $e) { return '-'; }
}
function generateBarcode($type) { // Generate a unique barcode string
    $prefixMap = [ // Map material type to prefix
        'ordinateur' => 'LAP', // Laptop prefix
        'ordinateur_bureau' => 'DESK', // Desktop prefix
        'ecran' => 'SCR', // Screen prefix
        'imprimante' => 'PRN', // Printer prefix
        'scanner' => 'SCN', // Scanner prefix
        'ventilateur' => 'FAN', // Fan prefix
        'clavier' => 'KBD', // Keyboard prefix
        'souris' => 'MSE', // Mouse prefix
        'onduleur' => 'UPS', // Ups prefix
        'serveur' => 'SRV', // Server prefix
        'autre' => 'MAT' // Generic prefix
    ]; // End prefix map
    $prefix = $prefixMap[$type] ?? 'MAT'; // Resolve prefix
    $timestamp = date('YmdHis'); // Add timestamp for uniqueness
    $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); // Add random suffix
    return 'ONACC-' . $prefix . '-' . $timestamp . '-' . $random; // Build barcode
} // End generateBarcode
 #Formate un montant en devise
function formatCurrency($amount, $currency = 'FCFA') {
    if (is_null($amount) || $amount === '' || $amount == 0) {
        return '-';
    }
    return number_format((float)$amount, 0, ',', ' ') . ' ' . $currency;
}

 # Formate un pourcentage
function formatPercent($value, $decimals = 1) {
    if (is_null($value) || $value === '') {
        return '-';
    }
    return number_format((float)$value, $decimals, ',', ' ') . ' %';
}



function logAction($userId, $action, $details = null) {
    global $pdo; // Utilise la connexion PDO globale
    
    try {
        $sql = "INSERT INTO logs_actions (user_id, action, details, ip_address, user_agent) 
                VALUES (:user_id, :action, :details, :ip_address, :user_agent)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu'
        ]);
        
        return true;
    } catch (Exception $e) {
        // Ne pas bloquer le processus principal
        error_log("Erreur de log: " . $e->getMessage());
        return false;
    }
}

// Fonction pour calculer les totaux en toute sécurité
function getAffectationsStats($affectations_par_dept = []) {
    // $affectations_par_dept est maintenant passé en paramètre
    if (empty($affectations_par_dept)) {
        return [
            'total' => 0,
            'actives' => 0,
            'attente' => 0
        ];
    }
    
    $total_affectations = 0;
    $total_actives = 0;
    $total_attente = 0;
    
    foreach ($affectations_par_dept as $row) {
        $total_affectations += $row['nombre_affectations'] ?? 0;
        $total_actives += $row['affectations_actives'] ?? 0;
        $total_attente += $row['en_attente'] ?? 0;
    }
    
    return [
        'total' => $total_affectations,
        'actives' => $total_actives,
        'attente' => $total_attente
    ];
}
function calculateTotalAffectations($affectations_par_dept) {
    $totals = [
        'total_affectations' => 0,
        'total_actives' => 0,
        'total_attente' => 0
    ];
    
    if (is_array($affectations_par_dept) && !empty($affectations_par_dept)) {
        foreach ($affectations_par_dept as $row) {
            $totals['total_affectations'] += $row['nombre_affectations'] ?? 0;
            $totals['total_actives'] += $row['affectations_actives'] ?? 0;
            $totals['total_attente'] += $row['en_attente'] ?? 0;
        }
    }
    
    return $totals;
    
}


endif; // FIN
?>
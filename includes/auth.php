<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php?error=Veuillez vous connecter');
        exit();
    }
}

function checkRole($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        if (!in_array($_SESSION['role'], ['admin', 'gestionnaire'])) {
            header('Location: ../pages/dashboard.php?error=Accès non autorisé');
            exit();
        }
    }
}

?>
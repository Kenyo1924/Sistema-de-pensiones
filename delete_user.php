<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/user.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id == 0) {
    header("Location: users.php");
    exit();
}

// Prevenir que el superadmin se elimine a sí mismo
if ($user_id == $_SESSION['user_id']) {
    header("Location: users.php?error=self_delete");
    exit();
}

// Verificar que el usuario existe
$user_data = $user->getById($user_id);
if (!$user_data) {
    header("Location: users.php");
    exit();
}

// Eliminar usuario
if ($user->delete($user_id)) {
    // Redireccionar con mensaje de éxito
    header("Location: users.php?deleted=1");
} else {
    // Redireccionar con mensaje de error
    header("Location: users.php?error=delete");
}
exit();
?>
<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Pago no válido.";
    exit();
}
$payment_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT student_id FROM payments WHERE id = ?");
$stmt->execute([$payment_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "Pago no encontrado.";
    exit();
}
$student_id = $row['student_id'];
$db->prepare("DELETE FROM payments WHERE id = ?")->execute([$payment_id]);
if (isset($_GET['from_student']) && $student_id) {
    header("Location: view_student.php?id=".$student_id);
    exit();
} else {
    header("Location: payments.php");
    exit();
}
?>

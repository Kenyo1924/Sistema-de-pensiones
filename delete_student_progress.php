<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Avance curricular no válido.";
    exit();
}
$progress_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Necesitamos también el student_id para volver a la ficha después
$stmt = $db->prepare("SELECT student_id, semester_id, program_type FROM student_semester_progress WHERE id = ?");
$stmt->execute([$progress_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "Registro no encontrado.";
    exit();
}
$student_id = $row['student_id'];
$semester_id = $row['semester_id'];
$program_type = $row['program_type'];

// Eliminar pagos ligados a este avance (student_id, semester_id, program_type si existe en payments):
if ($db->query("SHOW COLUMNS FROM payments LIKE 'program_type'")->rowCount() > 0) {
    $del_pagos = $db->prepare("DELETE FROM payments WHERE student_id=? AND semester_id=? AND program_type=?");
    $del_pagos->execute([$student_id, $semester_id, $program_type]);
} else {
    $del_pagos = $db->prepare("DELETE FROM payments WHERE student_id=? AND semester_id=?");
    $del_pagos->execute([$student_id, $semester_id]);
}

$stmt_del = $db->prepare("DELETE FROM student_semester_progress WHERE id = ?");
if ($stmt_del->execute([$progress_id])) {
    header("Location: view_student.php?id=".$student_id);
    exit();
} else {
    echo "Error al eliminar avance.";
}
?>

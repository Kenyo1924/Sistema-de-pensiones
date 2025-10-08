<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $student_id = $_GET['id'];

    // 1. Eliminar pagos relacionados
    $stmt1 = $db->prepare("DELETE FROM payments WHERE student_id = ?");
    $stmt1->execute([$student_id]);

    // 2. Eliminar progresos académicos
    $stmt2 = $db->prepare("DELETE FROM student_semester_progress WHERE student_id = ?");
    $stmt2->execute([$student_id]);

    // 3. Eliminar programas asociados
    $stmt3 = $db->prepare("DELETE FROM student_programs WHERE student_id = ?");
    $stmt3->execute([$student_id]);

    // 4. Finalmente, eliminar el estudiante
    $student = new Student($db);
    if ($student->delete($student_id)) {
        header("Location: students.php");
        exit();
    } else {
        echo "Failed to delete student.";
    }
} else {
    header("Location: students.php");
    exit();
}
?>

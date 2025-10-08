<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'editor') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $student = new Student($db);

    if ($student->delete($_GET['id'])) {
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
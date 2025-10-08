<?php
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';
include_once 'backend/core/payment.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);
$payment = new Payment($db);

$student_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['student_id'];

$student_data = $student->getById($student_id);
$payments = $payment->getByStudentId($student_id);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=payment_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, array('Student:', $student_data['first_name'].' '.$student_data['last_name']));
fputcsv($output, array('Student Code:', $student_data['student_code']));
fputcsv($output, array('')); // Empty line

fputcsv($output, array('Semester', 'Payment Type', 'Amount', 'Payment Date'));

foreach ($payments as $row) {
    fputcsv($output, array($row['semester_name'], $row['payment_type'], $row['amount'], $row['payment_date']));
}

fclose($output);
?>
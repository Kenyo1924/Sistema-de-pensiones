<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/payment.php';

$database = new Database();
$db = $database->getConnection();
$payment = new Payment($db);

$payments = $payment->getByStudentId($_SESSION['student_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?php echo $_SESSION['student_name']; ?></h1>
            <nav>
                <a href="student_dashboard.php">Dashboard</a>
                <a href="student_add_payment.php">Add Payment</a>
                <a href="generate_pdf_report.php" target="_blank">Download PDF Report</a>
                <a href="generate_excel_report.php" target="_blank">Download Excel Report</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        <main>
            <h2>Your Payment History</h2>
            <table>
                <thead>
                    <tr>
                        <th>Semester</th>
                        <th>Payment Type</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $row): ?>
                        <tr>
                            <td><?php echo $row['semester_name']; ?></td>
                            <td><?php echo $row['payment_type']; ?></td>
                            <td><?php echo $row['amount']; ?></td>
                            <td><?php echo $row['payment_date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/payment.php';
include_once 'backend/core/semester.php';

$database = new Database();
$db = $database->getConnection();

$semester = new Semester($db);
$semesters = $semester->getAll();

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment = new Payment($db);

    $payment->student_id = $_SESSION['student_id'];
    $payment->semester_id = $_POST['semester_id'];
    $payment->payment_type = $_POST['payment_type'];
    $payment->amount = $_POST['amount'];
    $payment->payment_date = $_POST['payment_date'];
    $payment->payment_code = $_POST['payment_code'];

    if ($payment->create()) {
        header("Location: student_dashboard.php");
        exit();
    } else {
        $message = "Failed to add payment.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Payment</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Add Payment</h1>
            <nav>
                <a href="student_dashboard.php">Dashboard</a>
                <a href="student_add_payment.php">Add Payment</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>
        <main>
            <h2>Add New Payment</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="student_add_payment.php" method="post">
                <div class="form-group">
                    <label for="semester_id">Semester</label>
                    <select name="semester_id" id="semester_id" required>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem['id']; ?>"><?php echo $sem['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_type">Payment Type</label>
                    <select name="payment_type" id="payment_type" required>
                        <option value="matricula">Matricula</option>
                        <option value="pension_1">Pension 1</option>
                        <option value="pension_2">Pension 2</option>
                        <option value="pension_3">Pension 3</option>
                        <option value="pension_4">Pension 4</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" step="0.01" name="amount" id="amount" required>
                </div>
                <div class="form-group">
                    <label for="payment_date">Payment Date</label>
                    <input type="date" name="payment_date" id="payment_date" required>
                </div>
                <div class="form-group">
                    <label for="payment_code">Payment Code</label>
                    <input type="text" name="payment_code" id="payment_code" required>
                </div>
                <button type="submit">Add Payment</button>
            </form>
        </main>
    </div>
</body>
</html>
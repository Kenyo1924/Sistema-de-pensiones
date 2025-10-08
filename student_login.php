<?php
session_start();
include_once 'backend/config/database.php';
include_once 'backend/core/student.php';

if (isset($_SESSION['student_id'])) {
    header("Location: student_dashboard.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, student_code, password, first_name, last_name FROM students WHERE student_code = :student_code";
    $stmt = $db->prepare($query);
    
    $student_code = htmlspecialchars(strip_tags($_POST['student_code']));
    $stmt->bindParam(':student_code', $student_code);
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($_POST['password'], $row['password'])) {
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['student_name'] = $row['first_name'] . ' ' . $row['last_name'];
            header("Location: student_dashboard.php");
            exit();
        }
    }
    $message = "Invalid student code or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Login</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Student Login</h2>
        <?php if ($message): ?>
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="student_login.php" method="post">
            <div class="form-group">
                <label for="student_code">Student Code</label>
                <input type="text" name="student_code" id="student_code" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p style="text-align: center; margin-top: 15px;">
            <a href="login.php">Admin Login</a>
        </p>
    </div>
</body>
</html>
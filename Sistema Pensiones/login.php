<?php
session_start();
include_once 'backend/config/database.php';
include_once 'backend/core/user.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $user->username = $_POST['username'];
    $password = $_POST['password'];

    $user_data = $user->login($password);

    if ($user_data) {
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['role'] = $user_data['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "Usuario o contraseña no válidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="logo" style="text-align: center; margin-bottom: 20px;">
            <img src="https://placehold.co/200x80/00c6ff/white?text=Logo" alt="Logo">
        </div>
        <h2>Iniciar Sesión</h2>
        <?php if ($message): ?>
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>
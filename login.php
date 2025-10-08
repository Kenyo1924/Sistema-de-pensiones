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
        <div class="logos-container" style="justify-content: center; margin-bottom: 30px;">
            <div class="logo">
                <img src="logo_posgrado.jpg" alt="Logo Posgrado">
                <span class="logo-text">Unidad de Posgrado</span>
            </div>
            <div class="logo">
                <img src="logo_educacion.jpg" alt="Logo Facultad de Educación">
                <span class="logo-text">Facultad de Educación</span>
            </div>
        </div>
        <h2>Sistema de Pensiones - Posgrado Educación</h2>
        <h3 style="text-align: center; margin-bottom: 30px;">Iniciar Sesión</h3>
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
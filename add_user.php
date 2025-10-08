<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/user.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validaciones
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($username) < 3) {
        $error = "El nombre de usuario debe tener al menos 3 caracteres.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif ($user->existsByUsername($username)) {
        $error = "El nombre de usuario ya existe. Por favor, elija otro.";
    } else {
        $user->username = $username;
        $user->password = $password;
        $user->role = $role;

        if ($user->create()) {
            $message = "Usuario creado exitosamente.";
            // Limpiar formulario
            $username = $password = $confirm_password = $role = '';
        } else {
            $error = "Error al crear el usuario. Inténtelo de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Usuario</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Agregar Usuario</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor'): ?>
                    <a href="add_semester.php">Administrar Semestres</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                    <a href="users.php" class="active">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2><span class="emoji">➕</span> Crear Nuevo Usuario</h2>
            
            <?php if ($message): ?>
                <div class="success-message" style="color: #27ae60; background-color: #d5f4e6; border: 1px solid #27ae60; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="add_user.php" method="post">
                <div class="form-group">
                    <label for="username">
                        <span class="emoji">👤</span> Nombre de Usuario
                    </label>
                    <input type="text" name="username" id="username" required 
                           value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                           placeholder="Ingrese el nombre de usuario (mínimo 3 caracteres)">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <span class="emoji">🔒</span> Contraseña
                    </label>
                    <input type="password" name="password" id="password" required 
                           placeholder="Ingrese la contraseña (mínimo 6 caracteres)">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <span class="emoji">🔒</span> Confirmar Contraseña
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                           placeholder="Confirme la contraseña">
                </div>
                
                <div class="form-group">
                    <label for="role">
                        <span class="emoji">🎭</span> Rol del Usuario
                    </label>
                    <select name="role" id="role" required>
                        <option value="">Seleccione un rol</option>
                        <option value="superadmin" <?php echo (isset($role) && $role == 'superadmin') ? 'selected' : ''; ?>>
                            🔑 Superadmin (Acceso completo)
                        </option>
                        <option value="editor" <?php echo (isset($role) && $role == 'editor') ? 'selected' : ''; ?>>
                            ✏️ Editor (Puede crear y modificar)
                        </option>
                        <option value="viewer" <?php echo (isset($role) && $role == 'viewer') ? 'selected' : ''; ?>>
                            👁️ Viewer (Solo lectura)
                        </option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" style="flex: 1;">
                        <span class="emoji">💾</span> Crear Usuario
                    </button>
                    <a href="users.php" class="button" style="flex: 1; background: #6c757d;">
                        <span class="emoji">↩️</span> Volver
                    </a>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Validación en tiempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ccc';
            }
        });

        document.getElementById('username').addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 3) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ccc';
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 6) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ccc';
            }
        });
    </script>
</body>
</html>
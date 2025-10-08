<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/user.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
$error = '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id == 0) {
    header("Location: users.php");
    exit();
}

// Permitir edición de otros usuarios, pero con restricciones especiales para auto-edición
$is_self_edit = ($user_id == $_SESSION['user_id']);

$user_data = $user->getById($user_id);
if (!$user_data) {
    header("Location: users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validaciones
    if (empty($username) || empty($role)) {
        $error = "El nombre de usuario y el rol son obligatorios.";
    } elseif (strlen($username) < 3) {
        $error = "El nombre de usuario debe tener al menos 3 caracteres.";
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el username ya existe (excepto para el usuario actual)
        $existing_user = $user->existsByUsername($username);
        if ($existing_user && $username !== $user_data['username']) {
            $error = "El nombre de usuario ya existe. Por favor, elija otro.";
        } else {
            // Validación especial: si es auto-edición, no permitir cambio de rol a algo inferior
            if ($is_self_edit && $role != 'superadmin') {
                $error = "No puede cambiar su propio rol de superadmin. Esto podría bloquear el acceso al sistema.";
            } else {
                $user->username = $username;
                $user->password = $password; // Solo se actualiza si no está vacío
                $user->role = $role;

                if ($user->update($user_id)) {
                    $message = "Usuario actualizado exitosamente.";
                    // Actualizar datos para mostrar en el formulario
                    $user_data = $user->getById($user_id);
                    
                    // Si se editó a sí mismo, actualizar la sesión
                    if ($is_self_edit) {
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                    }
                } else {
                    $error = "Error al actualizar el usuario. Inténtelo de nuevo.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Editar Usuario</h1>
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
            <h2><span class="emoji">✏️</span> Editar Usuario: <?php echo htmlspecialchars($user_data['username']); ?>
                <?php if ($is_self_edit): ?>
                    <span style="color: #f39c12; font-size: 0.8em;">(Tu perfil)</span>
                <?php endif; ?>
            </h2>
            
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
            
            <form action="edit_user.php?id=<?php echo $user_id; ?>" method="post">
                <div class="form-group">
                    <label for="username">
                        <span class="emoji">👤</span> Nombre de Usuario
                    </label>
                    <input type="text" name="username" id="username" required 
                           value="<?php echo htmlspecialchars($user_data['username']); ?>"
                           placeholder="Ingrese el nombre de usuario (mínimo 3 caracteres)">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <span class="emoji">🔒</span> Nueva Contraseña
                    </label>
                    <input type="password" name="password" id="password" 
                           placeholder="Deje vacío para mantener la contraseña actual">
                    <small style="color: #666; font-size: 0.9em;">
                        Solo complete este campo si desea cambiar la contraseña (mínimo 6 caracteres)
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <span class="emoji">🔒</span> Confirmar Nueva Contraseña
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" 
                           placeholder="Confirme la nueva contraseña">
                </div>
                
                <div class="form-group">
                    <label for="role">
                        <span class="emoji">🎭</span> Rol del Usuario
                        <?php if ($is_self_edit): ?>
                            <small style="color: #e74c3c; font-weight: normal;">(¡Cuidado! Cambiar tu rol puede afectar tus permisos)</small>
                        <?php endif; ?>
                    </label>
                    <select name="role" id="role" required <?php echo $is_self_edit ? 'onchange="confirmRoleChange(this)"' : ''; ?>>
                        <option value="superadmin" <?php echo $user_data['role'] == 'superadmin' ? 'selected' : ''; ?>>
                            🔑 Superadmin (Acceso completo)
                        </option>
                        <option value="editor" <?php echo $user_data['role'] == 'editor' ? 'selected' : ''; ?>>
                            ✏️ Editor (Puede crear y modificar)
                        </option>
                        <option value="viewer" <?php echo $user_data['role'] == 'viewer' ? 'selected' : ''; ?>>
                            👁️ Viewer (Solo lectura)
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <span class="emoji">📅</span> Fecha de Creación
                    </label>
                    <input type="text" value="<?php echo date('d/m/Y H:i', strtotime($user_data['created_at'])); ?>" 
                           readonly style="background-color: #f8f9fa; color: #666;">
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" style="flex: 1;">
                        <span class="emoji">💾</span> Actualizar Usuario
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
            
            if (password !== confirmPassword && (password.length > 0 || confirmPassword.length > 0)) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ccc';
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            
            if (this.value.length > 0 && this.value.length < 6) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ccc';
            }
            
            // Limpiar confirmación si se cambia la contraseña
            if (confirmPassword.value.length > 0) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });

        document.getElementById('username').addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 3) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ccc';
            }
        });

        <?php if ($is_self_edit): ?>
        // Confirmación especial para cambio de rol propio
        function confirmRoleChange(select) {
            const originalValue = '<?php echo $user_data['role']; ?>';
            if (select.value !== 'superadmin' && originalValue === 'superadmin') {
                if (!confirm('⚠️ ADVERTENCIA: Está cambiando su propio rol de Superadmin a ' + select.options[select.selectedIndex].text + '.\n\nEsto puede limitar su acceso al sistema. ¿Está seguro?')) {
                    select.value = originalValue;
                }
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>
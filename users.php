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

$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';

// PAGINACIÓN
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$all_users = $user->search($search, $role_filter);
$total_users = count($all_users);
$total_pages = max(1, ceil($total_users / $per_page));
$users = array_slice($all_users, ($page - 1) * $per_page, $per_page);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logos-container">
                <div class="logo">
                    <img src="logo_posgrado.jpg" alt="Logo Posgrado">
                    <span class="logo-text">Unidad de Posgrado</span>
                </div>
                <div class="logo">
                    <img src="logo_educacion.jpg" alt="Logo Facultad de Educación">
                    <span class="logo-text">Facultad de Educación</span>
                </div>
            </div>
            <h1>Gestión de Usuarios</h1>
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
            <h2><span class="emoji">👥</span> Usuarios del Sistema</h2>
            
            <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                <div class="success-message" style="color: #27ae60; background-color: #d5f4e6; border: 1px solid #27ae60; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    ✅ Usuario eliminado exitosamente.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error">
                    <?php if ($_GET['error'] == 'delete'): ?>
                        ❌ Error al eliminar el usuario. Inténtelo de nuevo.
                    <?php elseif ($_GET['error'] == 'self_delete'): ?>
                        ⚠️ No puede eliminar su propio usuario.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <a href="add_user.php" class="button">
                <span class="emoji">➕</span> Agregar Usuario
            </a>
            
            <form method="GET" action="users.php" class="search-form">
                <input type="text" name="search" placeholder="Buscar por nombre de usuario..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="role_filter">
                    <option value="">Todos los Roles</option>
                    <option value="superadmin" <?php echo $role_filter == 'superadmin' ? 'selected' : ''; ?>>Superadmin</option>
                    <option value="editor" <?php echo $role_filter == 'editor' ? 'selected' : ''; ?>>Editor</option>
                    <option value="viewer" <?php echo $role_filter == 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                </select>
                <button type="submit">Filtrar</button>
            </form>
            
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <h2 style="margin:0;">Lista de Usuarios (<?php echo $total_users; ?>)</h2>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                    <?php
                    $query_params = $_GET;
                    $window = 2;
                    $show_pages = [];
                    if ($total_pages <= 7) {
                        for ($i = 1; $i <= $total_pages; $i++) $show_pages[] = $i;
                    } else {
                        $show_pages[] = 1;
                        if ($page - $window > 2) $show_pages[] = '...';
                        for ($i = max(2, $page - $window); $i <= min($total_pages - 1, $page + $window); $i++) $show_pages[] = $i;
                        if ($page + $window < $total_pages - 1) $show_pages[] = '...';
                        $show_pages[] = $total_pages;
                    }

                    foreach ($show_pages as $i):
                        if ($i === '...') {
                            echo '<span style="padding:0 6px;color:var(--secondary-color);font-weight:bold;">...</span>';
                        } else {
                            $query_params['page'] = $i;
                            $link = '?' . http_build_query($query_params);
                            $active = ($i == $page) ? 'active' : '';
                            echo '<a href="' . htmlspecialchars($link) . '" class="page-link ' . $active . '">' . $i . '</a>';
                        }
                    endforeach;
                    ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Rol</th>
                        <th>Fecha de Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No se encontraron usuarios</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user_row): ?>
                            <tr>
                                <td><?php echo $user_row['id']; ?></td>
                                <td><?php echo htmlspecialchars($user_row['username']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user_row['role']; ?>">
                                        <?php 
                                        switch($user_row['role']) {
                                            case 'superadmin': echo '🔑 Superadmin'; break;
                                            case 'editor': echo '✏️ Editor'; break;
                                            case 'viewer': echo '👁️ Viewer'; break;
                                            default: echo $user_row['role'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user_row['created_at'])); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user_row['id']; ?>" style="color: var(--primary-color); margin-right: 10px;">
                                        ✏️ Editar
                                    </a>
                                    <?php if ($user_row['id'] != $_SESSION['user_id']): ?>
                                        <a href="delete_user.php?id=<?php echo $user_row['id']; ?>" 
                                           onclick="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.');"
                                           style="color: #e74c3c;">
                                            🗑️ Eliminar
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #f39c12; font-size: 0.9em; font-weight: bold;">
                                            👤 Tu perfil
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
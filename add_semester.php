<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'editor' && $_SESSION['role'] !== 'superadmin')) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
include_once 'backend/core/semester.php';

$database = new Database();
$db = $database->getConnection();
$semester = new Semester($db);

$message = '';
if (isset($_GET['edit_success']) && $_GET['edit_success'] == 1) {
    $message = 'Semestre editado correctamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    if ($name && $start_date && $end_date) {
        // Insertar semestre
        $query = "INSERT INTO semesters (name, start_date, end_date) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$name, $start_date, $end_date])) {
            $message = 'Semestre agregado correctamente.';
        } else {
            $message = 'Error al agregar el semestre. Puede que el nombre ya exista.';
        }
    } else {
        $message = 'Todos los campos son obligatorios.';
    }
}
// Listar los semestres existentes, ordenados (más reciente primero)
$semesters = $db->query("SELECT * FROM semesters ORDER BY start_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Semestres</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Administrar Semestres</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                    <a href="add_semester.php" class="active">Administrar Semestres</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                    <a href="users.php">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Semestres Existentes</h2>
            <?php if ($message): ?>
                <div class="message"> <?php echo htmlspecialchars($message); ?> </div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Fecha de Inicio</th>
                        <th>Fecha de Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($semesters)): ?>
                        <tr><td colspan="4">No hay semestres registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($semesters as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['start_date']); ?></td>
                                <td><?php echo htmlspecialchars($s['end_date']); ?></td>
                                <td>
                                    <a href="edit_semester.php?id=<?php echo $s['id']; ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <h2 style="margin-top:40px">Crear Nuevo Semestre</h2>
            <form method="POST" action="add_semester.php" class="form">
                <div class="form-group">
                    <label for="name">Nombre del Semestre</label>
                    <input type="text" name="name" id="name" required maxlength="20">
                </div>
                <div class="form-group">
                    <label for="start_date">Fecha de Inicio</label>
                    <input type="date" name="start_date" id="start_date" required>
                </div>
                <div class="form-group">
                    <label for="end_date">Fecha de Fin</label>
                    <input type="date" name="end_date" id="end_date" required>
                </div>
                <button type="submit">Agregar Semestre</button>
            </form>
        </main>
    </div>
</body>
</html>

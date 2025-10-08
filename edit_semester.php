<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
$database = new Database();
$db = $database->getConnection();
$semester_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

if ($semester_id <= 0) {
    echo "ID de semestre no válido.";
    exit();
}

// Cargar datos actuales
$semester_data = $db->prepare("SELECT * FROM semesters WHERE id = ?");
$semester_data->execute([$semester_id]);
$semester = $semester_data->fetch(PDO::FETCH_ASSOC);
if (!$semester) {
    echo "Semestre no encontrado.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($new_name === '' || !$start_date || !$end_date) {
        $message = 'Todos los campos son obligatorios.';
    } else if (strtotime($start_date) > strtotime($end_date)) {
        $message = 'La fecha de inicio no puede ser posterior a la de fin.';
    } else {
        // Comprobar que no exista otro semestre con ese nombre (ignorando este mismo)
        $check = $db->prepare("SELECT id FROM semesters WHERE name = ? AND id != ?");
        $check->execute([$new_name, $semester_id]);
        if ($check->rowCount() > 0) {
            $message = 'Ya existe otro semestre con ese nombre.';
        } else {
            $stmt = $db->prepare("UPDATE semesters SET name = ?, start_date = ?, end_date = ? WHERE id = ?");
            if ($stmt->execute([$new_name, $start_date, $end_date, $semester_id])) {
                header("Location: add_semester.php?edit_success=1");
                exit();
            } else {
                $message = 'Error al actualizar el semestre.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Semestre</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Editar Semestre</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                    <a href="add_semester.php" class="active">Agregar Semestre</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                    <a href="users.php">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Editar Semestre</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="name">Nombre del Semestre</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($semester['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="start_date">Fecha de Inicio</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($semester['start_date']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">Fecha de Fin</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($semester['end_date']); ?>" required>
                </div>
                <button type="submit">Guardar Cambios</button>
            </form>
        </main>
    </div>
</body>
</html>

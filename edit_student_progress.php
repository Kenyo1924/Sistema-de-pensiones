<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Avance curricular no válido.";
    exit();
}
$progress_id = (int)$_GET['id'];
$stmt = $db->prepare("SELECT * FROM student_semester_progress WHERE id = ?");
$stmt->execute([$progress_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$progress) {
    echo "Avance curricular no encontrado.";
    exit();
}

$student_id = $progress['student_id'];
$program_type = $progress['program_type'];
$semester_number = $progress['semester_number'];
$semester_id = $progress['semester_id'];

// Listar semestres académicos
$semesters = $db->query("SELECT * FROM semesters ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_program_type = $_POST['program_type'];
    $edit_semester_number = $_POST['semester_number'];
    $edit_semester_id = $_POST['semester_id'];

    // Restricción: solo puede existir un avance para ese estudiante, tipo, semestre académico (que no sea el mismo id)
    $stmt_exist = $db->prepare("SELECT id FROM student_semester_progress WHERE student_id=? AND program_type=? AND semester_id=? AND id<>? LIMIT 1");
    $stmt_exist->execute([$student_id, $edit_program_type, $edit_semester_id, $progress_id]);
    if ($stmt_exist->fetch()) {
        $message = 'Ya existe un avance para este estudiante, programa y semestre académico.';
    } else {
        $stmt_update = $db->prepare("UPDATE student_semester_progress SET program_type=?, semester_number=?, semester_id=? WHERE id=?");
        if ($stmt_update->execute([$edit_program_type, $edit_semester_number, $edit_semester_id, $progress_id])) {
            header("Location: view_student.php?id=$student_id");
            exit();
        } else {
            $message = 'Error al actualizar avance.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Avance Académico</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <script>
      function updateSemesterOptions() {
        var prog = document.getElementById('program_type').value;
        var sem = document.getElementById('semester_number');
        sem.innerHTML = '<option value="">Selecciona...</option>';
        var max = prog === 'maestria' ? 3 : (prog === 'doctorado' ? 6 : 0);
        for (var i = 1; i <= max; i++) {
          sem.innerHTML += '<option value="'+i+'">'+i+'</option>';
        }
      }
      window.addEventListener('DOMContentLoaded', function() {
        updateSemesterOptions();
        document.getElementById('semester_number').value = '<?php echo $semester_number; ?>';
      });
    </script>
</head>
<body>
<div class="dashboard-container">
<header>
    <div class="logo"><img src="logo.jpg" alt="Logo"></div>
    <h1>Editar Avance Académico</h1>
    <nav>
        <a href="dashboard.php">Inicio</a>
        <a href="students.php">Estudiantes</a>
        <a href="payments.php">Pagos</a>
        <a href="reports.php">Reportes</a>
        <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
            <a href="add_semester.php">Administrar Semestres</a>
        <?php endif; ?>
        <?php if ($_SESSION['role'] == 'superadmin'): ?>
            <a href="users.php">Gestión de Usuarios</a>
        <?php endif; ?>
        <a href="view_student.php?id=<?php echo $student_id; ?>" class="active">Regresar</a>
        <a href="logout.php">Cerrar Sesión</a>
    </nav>
</header>
<main>
    <h2>Editar Avance Académico</h2>
    <?php if ($message): ?><p class="error"><?php echo $message; ?></p><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="program_type">Tipo de Programa</label>
            <select name="program_type" id="program_type" required onchange="updateSemesterOptions()">
                <option value="maestria" <?php if ($program_type=='maestria') echo 'selected'; ?>>Maestría</option>
                <option value="doctorado" <?php if ($program_type=='doctorado') echo 'selected'; ?>>Doctorado</option>
            </select>
        </div>
        <div class="form-group">
            <label for="semester_number">Nº de Semestre Curricular</label>
            <select name="semester_number" id="semester_number" required>
                <option value="">Selecciona...</option>
            </select>
        </div>
        <div class="form-group">
            <label for="semester_id">Semestre Académico</label>
            <select name="semester_id" id="semester_id" required>
                <option value="">Selecciona...</option>
                <?php foreach ($semesters as $sem): ?>
                <option value="<?php echo $sem['id']; ?>" <?php if ($semester_id==$sem['id']) echo 'selected'; ?>><?php echo htmlspecialchars($sem['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Guardar Cambios</button>
    </form>
</main>
</div>
</body>
</html>

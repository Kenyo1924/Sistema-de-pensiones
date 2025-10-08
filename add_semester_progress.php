<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'editor' && $_SESSION['role'] != 'superadmin')) {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);

// Obtener lista de estudiantes
$students = $student->getAll();
// Obtener programas de un estudiante vía AJAX o en PHP (por simplicidad se hace en el backend)
$selected_student_id = isset($_POST['student_id']) ? $_POST['student_id'] : (isset($_GET['student_id']) ? $_GET['student_id'] : '');
$student_programs = [];
if ($selected_student_id) {
    $student_programs = $student->getProgramsByStudentId($selected_student_id);
}
// Obtener semestres desde la base
$semesters = $db->query("SELECT * FROM semesters ORDER BY start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'], $_POST['program_type'], $_POST['semester_number'], $_POST['semester_id'])) {
    $stmt = $db->prepare("INSERT INTO student_semester_progress (student_id, program_type, semester_number, semester_id) VALUES (?, ?, ?, ?)");
    $ok = $stmt->execute([
        $_POST['student_id'],
        $_POST['program_type'],
        $_POST['semester_number'],
        $_POST['semester_id']
    ]);
    $message = $ok ? 'Progreso registrado correctamente.' : 'Error al registrar el progreso.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Progreso de Semestre</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Registrar Progreso de Semestre</h1>
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
                <a href="add_semester_progress.php" class="active">Progreso</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Anclar Semestre Curricular a Semestre Académico</h2>
            <?php if ($message): ?>
                <p class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>"><?php echo $message; ?></p>
            <?php endif; ?>
            <form method="post" action="add_semester_progress.php">
                <div class="form-group">
                    <label for="student_id">Estudiante</label>
                    <select name="student_id" id="student_id" required onchange="this.form.submit()">
                        <option value="">Selecciona un estudiante...</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php if ($selected_student_id == $s['id']) echo 'selected'; ?>><?php echo $s['first_name'] . ' ' . $s['last_name'] . ' (DNI: ' . $s['dni'] . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selected_student_id && $student_programs): ?>
                <div class="form-group">
                    <label for="program_type">Programa</label>
                    <select name="program_type" id="program_type" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($student_programs as $prog): ?>
                            <option value="<?php echo $prog['program_type']; ?>"><?php echo ucfirst($prog['program_type']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_number">Nº de Semestre Curricular</label>
                    <select name="semester_number" id="semester_number" required>
                        <option value="">Selecciona...</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_id">Semestre Académico</label>
                    <select name="semester_id" id="semester_id" required>
                        <option value="">Selecciona...</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Registrar Progreso</button>
                <?php endif; ?>
            </form>
        </main>
    </div>
</body>
</html>

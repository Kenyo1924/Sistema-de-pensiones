<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'editor') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $student = new Student($db);

    $student->first_name = $_POST['first_name'];
    $student->last_name = $_POST['last_name'];
    $student->dni = $_POST['dni'];
    $program_type = $_POST['program_type'];
    $is_scholarship_holder = isset($_POST['is_scholarship_holder']) ? 1 : 0;

    if ($student->existsByDni($student->dni)) {
        $message = "El DNI ingresado ya existe. Por favor, ingrese un DNI diferente.";
    } else {
        $student_id = $student->create();
        if ($student_id) {
            // Insertar programa en student_programs
            $stmt_prog = $db->prepare("INSERT INTO student_programs (student_id, program_type, is_scholarship_holder) VALUES (?, ?, ?)");
            $stmt_prog->execute([$student_id, $program_type, $is_scholarship_holder]);
            header("Location: students.php");
            exit();
        } else {
            $message = "Error al agregar estudiante.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Estudiante</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Agregar Estudiante</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php" class="active">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor'): ?>
                    <a href="add_semester.php">Agregar Semestre</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Agregar Nuevo Estudiante</h2>
            <?php if ($message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="add_student.php" method="post">
                <div class="form-group">
                    <label for="first_name">Nombres</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Apellidos</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
                <div class="form-group">
                    <label for="dni">DNI de Estudiante</label>
                    <input type="text" name="dni" id="dni" required maxlength="8">
                </div>
                <!-- Eliminado campo Código de Estudiante, solo se usará DNI -->
                <div class="form-group">
                    <label for="program_type">Tipo de Programa</label>
                    <select name="program_type" id="program_type" required>
                        <option value="maestria">Maestría</option>
                        <option value="doctorado">Doctorado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="is_scholarship_holder">
                        <input type="checkbox" name="is_scholarship_holder" id="is_scholarship_holder" value="1">
                        Becado
                    </label>
                </div>
                <button type="submit">Agregar Estudiante</button>
            </form>
        </main>
    </div>
</body>
</html>
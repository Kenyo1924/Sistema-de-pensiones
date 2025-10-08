<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'editor') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';
include_once 'backend/core/student.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);

$student_data = $student->getById($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student->id = $_POST['id'];
    $student->first_name = $_POST['first_name'];
    $student->last_name = $_POST['last_name'];
    $student->dni = $_POST['dni'];
    $student->program_type = $_POST['program_type'];
    $student->is_scholarship_holder = isset($_POST['is_scholarship_holder']) ? 1 : 0;

    if ($student->update()) {
        header("Location: students.php");
        exit();
    } else {
        $message = "Error al actualizar el estudiante.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Estudiante</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Editar Estudiante</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php" class="active">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Editar Estudiante</h2>
            <?php if (isset($message)): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="edit_student.php?id=<?php echo $_GET['id']; ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $student_data['id']; ?>">
                <div class="form-group">
                    <label for="first_name">Nombres</label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo $student_data['first_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Apellidos</label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo $student_data['last_name']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="dni">DNI</label>
                    <input type="text" name="dni" id="dni" value="<?php echo $student_data['dni']; ?>" required maxlength="8">
                </div>
                <div class="form-group">
                    <label for="program_type">Tipo de Programa</label>
                    <select name="program_type" id="program_type" required>
                        <option value="maestria" <?php echo $student_data['program_type'] == 'maestria' ? 'selected' : ''; ?>>Maestría</option>
                        <option value="doctorado" <?php echo $student_data['program_type'] == 'doctorado' ? 'selected' : ''; ?>>Doctorado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="is_scholarship_holder">
                        <input type="checkbox" name="is_scholarship_holder" id="is_scholarship_holder" value="1" <?php echo $student_data['is_scholarship_holder'] ? 'checked' : ''; ?>>
                        Becado
                    </label>
                </div>
                <button type="submit">Actualizar Estudiante</button>
            </form>
        </main>
    </div>
</body>
</html>
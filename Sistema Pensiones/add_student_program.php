<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'editor') {
    header("Location: login.php");
    exit();
}

include_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();
$message = '';
// Traer lista de estudiantes (puedes paginarla o hacer búsqueda AJAX para muchos)
$students = [];
$stmt = $db->query("SELECT id, first_name, last_name, dni FROM students ORDER BY last_name, first_name");
if ($stmt) $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $program_type = $_POST['program_type'];
    $is_scholarship_holder = isset($_POST['is_scholarship_holder']) ? 1 : 0;

    // Insertar nuevo programa
    $stmt = $db->prepare("INSERT INTO student_programs (student_id, program_type, is_scholarship_holder) VALUES (?, ?, ?)");
    if ($stmt->execute([$student_id, $program_type, $is_scholarship_holder])) {
        $message = "Posgrado añadido correctamente al estudiante.";
    } else {
        $message = "Error al insertar el posgrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Nuevo Posgrado</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Agregar Nuevo Posgrado a Estudiante</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="students.php">Estudiantes</a>
                <a href="payments.php">Pagos</a>
                <a href="reports.php">Reportes</a>
                <?php if ($_SESSION['role'] == 'editor'): ?>
                    <a href="add_semester.php">Agregar Semestre</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Registrar un nuevo programa (Doctorado/Maestría) para un estudiante</h2>
            <?php if ($message): ?>
                <p class="success"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="add_student_program.php" method="post">
                <div class="form-group">
                    <label for="student_id">Seleccionar estudiante (por DNI):</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">-- Selecciona estudiante --</option>
                        <?php foreach ($students as $st): ?>
                            <option value="<?php echo $st['id']; ?>"> <?php echo $st['dni'] . ' - ' . $st['last_name'] . ', ' . $st['first_name']; ?> </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="program_type">Tipo de Posgrado</label>
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
                                <button type="submit">Agregar Posgrado</button>
            </form>
        </main>
    </div>
</body>
</html>

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

$student_data = $student->getById($_GET['id']);
$message = '';
$student_id = $_GET['id'];

// Traer TODOS los programas del estudiante
$stmt_progs = $db->prepare("SELECT * FROM student_programs WHERE student_id = ? ORDER BY id");
$stmt_progs->execute([$student_id]);
$programs = $stmt_progs->fetchAll(PDO::FETCH_ASSOC);

// Determinar cuál programa se edita (por default el primero, o el seleccionado por GET/POST)
$program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : (isset($_POST['program_id']) ? intval($_POST['program_id']) : (isset($programs[0])?$programs[0]['id']:0));
$current_program = null;
foreach ($programs as $prog) {
    if ($prog['id'] == $program_id) {
        $current_program = $prog;
        break;
    }
}
// fallback si no existe
if (!$current_program && count($programs)>0) $current_program = $programs[0];

$current_program_type = $current_program ? $current_program['program_type'] : '';
$current_scholarship = $current_program ? (int)$current_program['is_scholarship_holder'] : 0;
$current_program_id = $current_program ? (int)$current_program['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student->id = $_POST['id'];
    $student->first_name = $_POST['first_name'];
    $student->last_name = $_POST['last_name'];
    $student->dni = $_POST['dni'];
    $edit_program_type = $_POST['program_type'];
    $edit_scholarship = isset($_POST['is_scholarship_holder']) ? 1 : 0;
    $update_program_id = intval($_POST['program_id']);
    $success = true;

    // Actualizar datos del estudiante
    if (!$student->getById($student->id)) {
        $message = 'Estudiante no encontrado';
        $success = false;
    } else {
        $base_query = $db->prepare("UPDATE students SET first_name = ?, last_name = ?, dni = ? WHERE id = ?");
        $base_query->execute([$student->first_name, $student->last_name, $student->dni, $student->id]);
    }
    // Actualizar SOLO el programa seleccionado
    if ($update_program_id > 0) {
        $stmt_update = $db->prepare("UPDATE student_programs SET program_type = ?, is_scholarship_holder = ? WHERE id = ? AND student_id = ?");
        $stmt_update->execute([$edit_program_type, $edit_scholarship, $update_program_id, $student->id]);
    }
    if ($success) {
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
    <script>
    function submitProgramChange() {
        // Cambia el programa seleccionado: recarga el form GET
        var sel = document.getElementById('program_id');
        var val = sel.options[sel.selectedIndex].value;
        var url = window.location.href.split('?')[0]+"?id=<?php echo $student_id; ?>&program_id="+val;
        window.location.href = url;
    }
    </script>
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
                <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                    <a href="add_semester.php">Administrar Semestres</a>
                <?php endif; ?>
                <?php if ($_SESSION['role'] == 'superadmin'): ?>
                    <a href="users.php">Gestión de Usuarios</a>
                <?php endif; ?>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>
        <main>
            <h2>Editar Estudiante</h2>
            <?php if (isset($message) && $message): ?>
                <p class="error"><?php echo $message; ?></p>
            <?php endif; ?>
            <form action="edit_student.php?id=<?php echo $student_id; ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $student_data['id']; ?>">
                <?php if (count($programs) > 1): ?>
                <div class="form-group">
                    <label for="program_id">Programa a editar</label>
                    <select name="program_id" id="program_id" onchange="submitProgramChange()" required>
                        <?php foreach($programs as $prog): ?>
                            <option value="<?php echo $prog['id']; ?>" <?php if($prog['id']==$current_program_id) echo 'selected'; ?>>
                                <?php echo ucfirst($prog['program_type']) . ($prog['is_scholarship_holder'] ? " (Becado)" : " (No Becado)"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="program_id" value="<?php echo $current_program_id; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="first_name">Nombres</label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($student_data['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Apellidos</label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($student_data['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="dni">DNI</label>
                    <input type="text" name="dni" id="dni" value="<?php echo htmlspecialchars($student_data['dni']); ?>" required maxlength="8">
                </div>
                <div class="form-group">
                    <label for="program_type">Tipo de Programa</label>
                    <select name="program_type" id="program_type" required>
                        <option value="maestria" <?php echo ($current_program_type == 'maestria') ? 'selected' : ''; ?>>Maestría</option>
                        <option value="doctorado" <?php echo ($current_program_type == 'doctorado') ? 'selected' : ''; ?>>Doctorado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="is_scholarship_holder">
                        <input type="checkbox" name="is_scholarship_holder" id="is_scholarship_holder" value="1" <?php echo ($current_scholarship) ? 'checked' : ''; ?>>
                        Becado
                    </label>
                </div>
                <button type="submit">Actualizar Estudiante</button>
            </form>
        </main>
    </div>
</body>
</html>

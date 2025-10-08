<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include_once 'backend/config/database.php';
include_once 'backend/core/student.php';
include_once 'backend/core/semester.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);
$semester = new Semester($db);

$semesters = $semester->getAll();

$name_search = isset($_GET['name_search']) ? $_GET['name_search'] : '';
$code_search = isset($_GET['code_search']) ? $_GET['code_search'] : '';
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : null;
// Usar el nuevo método que retorna estudiantes junto a programas
$students = $student->getAllWithPrograms();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estudiantes</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="logo">
                <img src="logo.jpg" alt="Logo">
            </div>
            <h1>Gestión de Estudiantes</h1>
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
            <h2>Estudiantes</h2>
            <?php if ($_SESSION['role'] == 'editor'): ?>
                <a href="add_student.php" class="button">Agregar Estudiante</a>
                <a href="add_student_program.php" class="button">Agregar Posgrado a Estudiante</a>
            <?php endif; ?>
            <form method="GET" action="students.php" class="filter-form">
                <input type="text" name="name_search" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($name_search); ?>">
                <input type="text" name="code_search" placeholder="Buscar por código..." value="<?php echo htmlspecialchars($code_search); ?>">
                <select name="semester_id">
                    <option value="">Todos los Semestres</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo $sem['id']; ?>" <?php if ($semester_id == $sem['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($sem['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Buscar</button>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>DNI</th>
                        <th>Tipo de Programa</th>
                        <th>Becado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $row): ?>
                        <tr>
                            <td><?php echo $row['first_name']; ?></td>
                            <td><?php echo $row['last_name']; ?></td>
                            <td><?php echo $row['dni']; ?></td>
                            <td>
                                <?php 
                                // Mostrar todas las trayectorias: convertir a nombres legibles
                                $program_names = [];
                                if ($row['programs']) {
                                    foreach (explode(',', $row['programs']) as $program) {
                                        if ($program === 'maestria') $program_names[] = 'Maestría';
                                        else if ($program === 'doctorado') $program_names[] = 'Doctorado';
                                    }
                                    echo implode(', ', $program_names);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                // Becado si hay algún programa con beca
                                if ($row['scholarships']) {
                                    $scholar_list = explode(',', $row['scholarships']);
                                    echo (in_array('1', $scholar_list)) ? 'Sí' : 'No';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="view_student.php?id=<?php echo $row['id']; ?>">Ver</a>
                                <?php if ($_SESSION['role'] == 'editor'): ?>
                                    <a href="edit_student.php?id=<?php echo $row['id']; ?>">Editar</a>
                                    <a href="delete_student.php?id=<?php echo $row['id']; ?>">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
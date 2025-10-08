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
$dni_search = isset($_GET['dni_search']) ? $_GET['dni_search'] : '';
$semester_id = isset($_GET['semester_id']) ? $_GET['semester_id'] : null;

// PAGINACIÓN PARA ESTUDIANTES
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$all_students = $student->getAllWithPrograms();

// Aplicar filtros si existen
if ($name_search || $dni_search) {
    $all_students = array_filter($all_students, function($student) use ($name_search, $dni_search) {
        $name_match = empty($name_search) || 
                     (stripos($student['first_name'], $name_search) !== false || 
                      stripos($student['last_name'], $name_search) !== false);
        $dni_match = empty($dni_search) || 
                     (stripos($student['dni'], $dni_search) !== false);
        return $name_match && $dni_match;
    });
}

$total_students = count($all_students);
$total_pages = max(1, ceil($total_students / $per_page));
$students = array_slice($all_students, ($page - 1) * $per_page, $per_page);
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
            <h1>Gestión de Estudiantes</h1>
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
            <h2>Estudiantes</h2>
            <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                <a href="add_student.php" class="button">Agregar Estudiante</a>
                <a href="add_student_program.php" class="button">Agregar Posgrado a Estudiante</a>
            <?php endif; ?>
            <form method="GET" action="students.php" class="filter-form">
                <input type="text" name="name_search" placeholder="Buscar por nombre..." value="<?php echo htmlspecialchars($name_search); ?>">
                <input type="text" name="dni_search" placeholder="Buscar por DNI..." value="<?php echo htmlspecialchars($dni_search); ?>">
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
            
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <h2 style="margin:0;">Lista de Estudiantes</h2>
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
                                        switch($program) {
                                            case 'maestria_gestion_educativa': $program_names[] = 'Maestría: Gestión Educativa'; break;
                                            case 'maestria_educacion_superior': $program_names[] = 'Maestría: Educación Superior'; break;
                                            case 'maestria_psicologia_educativa': $program_names[] = 'Maestría: Psicología Educativa'; break;
                                            case 'maestria_ensenanza_estrategica': $program_names[] = 'Maestría: Enseñanza Estratégica'; break;
                                            case 'doctorado': $program_names[] = 'Doctorado'; break;
                                            default: $program_names[] = $program;
                                        }
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
                                    echo 'No';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="view_student.php?id=<?php echo $row['id']; ?>">Ver</a>
                                <?php if ($_SESSION['role'] == 'editor' || $_SESSION['role'] == 'superadmin'): ?>
                                    <a href="edit_student.php?id=<?php echo $row['id']; ?>">Editar</a>
                                    <a href="add_semester_progress.php?student_id=<?php echo $row['id']; ?>">Registrar Avance</a>
                                    <a href="delete_student.php?id=<?php echo $row['id']; ?>" onclick="return confirm('¿Está seguro de eliminar a este estudiante? Se eliminarán también todos sus pagos y avances académicos.');">Eliminar</a>
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
<?php
include_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Database connected successfully.<br>";
} else {
    echo "Failed to connect to database.<br>";
    exit();
}

$query_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('editor', 'viewer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$query_students = "CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    student_code VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Nueva tabla para soportar múltiples programas por estudiante
$query_student_programs = "CREATE TABLE IF NOT EXISTS student_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_type ENUM('maestria','doctorado') NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_scholarship_holder BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
)";

$query_semesters = "CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL
)";

// Nueva tabla para llevar el progreso de semestre curricular anclado a un semestre académico
$query_student_semester_progress = "CREATE TABLE IF NOT EXISTS student_semester_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_type ENUM('maestria','doctorado') NOT NULL,
    semester_number INT NOT NULL,
    semester_id INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id)
)";

// Borrar payments para crearla siempre desde cero
$db->exec("DROP TABLE IF EXISTS payments");
$query_payments = "CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    semester_id INT NOT NULL,
    program_type ENUM('maestria', 'doctorado') NOT NULL,
    payment_type ENUM('matricula', 'pension_1', 'pension_2', 'pension_3', 'pension_4') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    voucher_path VARCHAR(255) NOT NULL,
    payment_code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id)
)";

try {
    $db->exec($query_users);
    echo "Table 'users' created successfully.<br>";
    $db->exec($query_students);
    echo "Table 'students' created successfully.<br>";
    $db->exec($query_student_programs);
    echo "Table 'student_programs' created successfully.<br>";
    $db->exec($query_semesters);
    echo "Table 'semesters' created successfully.<br>";
    $db->exec($query_payments);
    echo "Table 'payments' created successfully.<br>";
    $db->exec($query_student_semester_progress);
    echo "Table 'student_semester_progress' created successfully.<br>";

    // Migrar datos antiguos a student_programs si students tiene program_type
    try {
        $fields = $db->query("SHOW COLUMNS FROM students LIKE 'program_type'");
        if ($fields && $fields->rowCount() > 0) {
            $db->exec("INSERT INTO student_programs (student_id, program_type, is_scholarship_holder)
              SELECT id, program_type, is_scholarship_holder FROM students WHERE program_type IS NOT NULL");
            echo "Datos migrados de students a student_programs.<br>";
            // Eliminar columnas antiguas
            $db->exec("ALTER TABLE students DROP COLUMN program_type");
            $field2 = $db->query("SHOW COLUMNS FROM students LIKE 'is_scholarship_holder'");
            if ($field2 && $field2->rowCount() > 0) {
                $db->exec("ALTER TABLE students DROP COLUMN is_scholarship_holder");
            }
            echo "Campos antiguos eliminados de students.<br>";
        }
        // Eliminar columna student_code si existe
        $field3 = $db->query("SHOW COLUMNS FROM students LIKE 'student_code'");
        if ($field3 && $field3->rowCount() > 0) {
            $db->exec("ALTER TABLE students DROP COLUMN student_code");
            echo "Columna student_code eliminada de students.<br>";
        }
        // Agregar columna program_type en payments si no existe
        $field4 = $db->query("SHOW COLUMNS FROM payments LIKE 'program_type'");
        if ($field4 && $field4->rowCount() == 0) {
            $db->exec("ALTER TABLE payments ADD COLUMN program_type ENUM('maestria','doctorado') NOT NULL DEFAULT 'maestria' AFTER semester_id");
            echo "Columna program_type agregada en payments.<br>";
        }
    } catch (Exception $e) {
        echo "Error migrando programas: ".$e->getMessage()."<br>";
    }

    // Insert default users
    $editor_password = password_hash('editor123', PASSWORD_BCRYPT);
    $viewer_password = password_hash('viewer123', PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES ('editor', :password, 'editor') ON DUPLICATE KEY UPDATE password=:password");
    $stmt->bindParam(':password', $editor_password);
    $stmt->execute();
    
    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES ('viewer', :password, 'viewer') ON DUPLICATE KEY UPDATE password=:password");
    $stmt->bindParam(':password', $viewer_password);
    $stmt->execute();
    echo "Default users inserted successfully.<br>";


} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "<br>";
}

?>
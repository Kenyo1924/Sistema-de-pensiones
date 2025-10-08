-- Crear tabla intermedia para soportar historiales múltiples de programa-académico por estudiante
CREATE TABLE IF NOT EXISTS student_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_type ENUM('maestria','doctorado') NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_scholarship_holder BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Ejemplo de migración de datos inicial (si fuera necesario):
-- INSERT INTO student_programs (student_id, program_type, is_scholarship_holder)
-- SELECT id, program_type, is_scholarship_holder FROM students;

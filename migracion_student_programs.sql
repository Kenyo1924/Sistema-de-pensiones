-- Crear tabla intermedia para soportar historiales múltiples de programa-académico por estudiante
CREATE TABLE IF NOT EXISTS student_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_scholarship_holder BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- Migración de datos existentes de maestría a maestría_gestion_educativa
UPDATE student_programs SET program_type = 'maestria_gestion_educativa' WHERE program_type = 'maestria';
UPDATE payments SET program_type = 'maestria_gestion_educativa' WHERE program_type = 'maestria';
UPDATE student_semester_progress SET program_type = 'maestria_gestion_educativa' WHERE program_type = 'maestria';

-- Actualizar tabla student_programs para incluir nuevos tipos de maestría
ALTER TABLE student_programs MODIFY COLUMN program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL;

-- Actualizar tabla payments para incluir nuevos tipos de maestría
ALTER TABLE payments MODIFY COLUMN program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL;

-- Eliminar columna voucher_path de payments
ALTER TABLE payments DROP COLUMN voucher_path;

-- Actualizar tabla student_semester_progress para incluir nuevos tipos de maestría
ALTER TABLE student_semester_progress MODIFY COLUMN program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL;

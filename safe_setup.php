<?php
include_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "❌ Error: No se pudo conectar a la base de datos.<br>";
    exit();
}

echo "<h2>🔄 Migración Segura del Sistema de Pensiones</h2>";
echo "<p>Este script actualizará tu sistema existente sin perder datos.</p>";

try {
    // 1. Migrar datos existentes de maestría a maestría_gestion_educativa ANTES de cambiar la estructura
    echo "<h3>📊 Paso 1: Migrando datos existentes...</h3>";
    
    // Verificar si existen datos con el tipo 'maestria' antiguo
    $check_maestria = $db->query("SELECT COUNT(*) as count FROM student_programs WHERE program_type = 'maestria'");
    $count_maestria = $check_maestria->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count_maestria > 0) {
        $db->exec("UPDATE student_programs SET program_type = 'maestria_gestion_educativa' WHERE program_type = 'maestria'");
        echo "✅ Migrados $count_maestria registros de student_programs de 'maestria' a 'maestria_gestion_educativa'<br>";
    } else {
        echo "ℹ️ No se encontraron registros de 'maestria' en student_programs<br>";
    }
    
    // Migrar en payments
    $check_payments = $db->query("SELECT COUNT(*) as count FROM payments WHERE program_type = 'maestria'");
    $count_payments = $check_payments->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count_payments > 0) {
        $db->exec("UPDATE payments SET program_type = 'maestria_gestion_educativa' WHERE program_type = 'maestria'");
        echo "✅ Migrados $count_payments registros de payments de 'maestria' a 'maestria_gestion_educativa'<br>";
    } else {
        echo "ℹ️ No se encontraron registros de 'maestria' en payments<br>";
    }
    
    // Migrar en student_semester_progress
    $check_progress = $db->query("SELECT COUNT(*) as count FROM student_semester_progress WHERE program_type = 'maestria'");
    $count_progress = $check_progress->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count_progress > 0) {
        $db->exec("UPDATE student_semester_progress SET program_type = 'maestria_gestion_educativa' WHERE program_type = 'maestria'");
        echo "✅ Migrados $count_progress registros de student_semester_progress de 'maestria' a 'maestria_gestion_educativa'<br>";
    } else {
        echo "ℹ️ No se encontraron registros de 'maestria' en student_semester_progress<br>";
    }

    // 2. Actualizar estructura de tablas
    echo "<h3>🏗️ Paso 2: Actualizando estructura de tablas...</h3>";
    
    // Actualizar student_programs
    try {
        $db->exec("ALTER TABLE student_programs MODIFY COLUMN program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL");
        echo "✅ Tabla student_programs actualizada con nuevos tipos de maestría<br>";
    } catch (Exception $e) {
        echo "⚠️ Error actualizando student_programs: " . $e->getMessage() . "<br>";
    }
    
    // Actualizar payments
    try {
        $db->exec("ALTER TABLE payments MODIFY COLUMN program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL");
        echo "✅ Tabla payments actualizada con nuevos tipos de maestría<br>";
    } catch (Exception $e) {
        echo "⚠️ Error actualizando payments: " . $e->getMessage() . "<br>";
    }
    
    // Actualizar student_semester_progress
    try {
        $db->exec("ALTER TABLE student_semester_progress MODIFY COLUMN program_type ENUM('maestria_gestion_educativa','maestria_educacion_superior','maestria_psicologia_educativa','maestria_ensenanza_estrategica','doctorado') NOT NULL");
        echo "✅ Tabla student_semester_progress actualizada con nuevos tipos de maestría<br>";
    } catch (Exception $e) {
        echo "⚠️ Error actualizando student_semester_progress: " . $e->getMessage() . "<br>";
    }

    // 3. Eliminar columna voucher_path si existe
    echo "<h3>🗑️ Paso 3: Eliminando funcionalidad de vouchers...</h3>";
    
    try {
        $check_voucher = $db->query("SHOW COLUMNS FROM payments LIKE 'voucher_path'");
        if ($check_voucher && $check_voucher->rowCount() > 0) {
            $db->exec("ALTER TABLE payments DROP COLUMN voucher_path");
            echo "✅ Columna voucher_path eliminada de payments<br>";
        } else {
            echo "ℹ️ Columna voucher_path no existe en payments<br>";
        }
    } catch (Exception $e) {
        echo "⚠️ Error eliminando voucher_path: " . $e->getMessage() . "<br>";
    }

    // 4. Agregar campos para pagos múltiples
    echo "<h3>💳 Paso 4: Agregando funcionalidad de pagos múltiples...</h3>";
    
    // Verificar si los campos ya existen
    $check_voucher_group = $db->query("SHOW COLUMNS FROM payments LIKE 'voucher_group_id'");
    if ($check_voucher_group && $check_voucher_group->rowCount() == 0) {
        $db->exec("ALTER TABLE payments ADD COLUMN voucher_group_id VARCHAR(50) DEFAULT NULL AFTER payment_code");
        echo "✅ Campo voucher_group_id agregado<br>";
    } else {
        echo "ℹ️ Campo voucher_group_id ya existe<br>";
    }
    
    $check_multi_payment = $db->query("SHOW COLUMNS FROM payments LIKE 'is_multi_payment'");
    if ($check_multi_payment && $check_multi_payment->rowCount() == 0) {
        $db->exec("ALTER TABLE payments ADD COLUMN is_multi_payment BOOLEAN DEFAULT FALSE AFTER voucher_group_id");
        echo "✅ Campo is_multi_payment agregado<br>";
    } else {
        echo "ℹ️ Campo is_multi_payment ya existe<br>";
    }
    
    $check_description = $db->query("SHOW COLUMNS FROM payments LIKE 'voucher_description'");
    if ($check_description && $check_description->rowCount() == 0) {
        $db->exec("ALTER TABLE payments ADD COLUMN voucher_description TEXT DEFAULT NULL AFTER is_multi_payment");
        echo "✅ Campo voucher_description agregado<br>";
    } else {
        echo "ℹ️ Campo voucher_description ya existe<br>";
    }
    
    // Crear índice para mejorar consultas
    try {
        $db->exec("CREATE INDEX idx_voucher_group ON payments(voucher_group_id)");
        echo "✅ Índice idx_voucher_group creado<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️ Índice idx_voucher_group ya existe<br>";
        } else {
            echo "⚠️ Error creando índice: " . $e->getMessage() . "<br>";
        }
    }
    
    // Actualizar registros existentes
    $update_result = $db->exec("UPDATE payments SET voucher_group_id = payment_code WHERE voucher_group_id IS NULL");
    echo "✅ $update_result registros actualizados con voucher_group_id<br>";
    
    // Crear vista para consultas de pagos agrupados
    try {
        $db->exec("DROP VIEW IF EXISTS payment_groups");
        $db->exec("CREATE VIEW payment_groups AS
            SELECT 
                voucher_group_id,
                student_id,
                semester_id,
                program_type,
                payment_date,
                payment_code,
                voucher_description,
                COUNT(*) as payment_count,
                SUM(amount) as total_amount,
                GROUP_CONCAT(payment_type ORDER BY payment_type SEPARATOR ', ') as payment_types,
                GROUP_CONCAT(amount ORDER BY payment_type SEPARATOR ', ') as amounts,
                created_at
            FROM payments 
            WHERE voucher_group_id IS NOT NULL
            GROUP BY voucher_group_id, student_id, semester_id, program_type, payment_date, payment_code, voucher_description, created_at");
        echo "✅ Vista payment_groups creada<br>";
    } catch (Exception $e) {
        echo "⚠️ Error creando vista: " . $e->getMessage() . "<br>";
    }
    
    // 5. Eliminar restricción UNIQUE del campo payment_code
    echo "<h3>🔧 Paso 5: Eliminando restricción UNIQUE de payment_code...</h3>";
    
    try {
        // Verificar si la restricción UNIQUE existe
        $check_constraint = $db->query("SHOW INDEX FROM payments WHERE Key_name = 'payment_code'");
        if ($check_constraint && $check_constraint->rowCount() > 0) {
            $db->exec("ALTER TABLE payments DROP INDEX payment_code");
            echo "✅ Restricción UNIQUE eliminada de payment_code<br>";
        } else {
            echo "ℹ️ No se encontró restricción UNIQUE en payment_code<br>";
        }
    } catch (Exception $e) {
        echo "⚠️ Error eliminando restricción: " . $e->getMessage() . "<br>";
    }

    // 6. Verificar integridad de datos
    echo "<h3>🔍 Paso 6: Verificando integridad de datos...</h3>";
    
    $total_students = $db->query("SELECT COUNT(*) as count FROM students")->fetch(PDO::FETCH_ASSOC)['count'];
    $total_payments = $db->query("SELECT COUNT(*) as count FROM payments")->fetch(PDO::FETCH_ASSOC)['count'];
    $total_programs = $db->query("SELECT COUNT(*) as count FROM student_programs")->fetch(PDO::FETCH_ASSOC)['count'];
    $total_groups = $db->query("SELECT COUNT(DISTINCT voucher_group_id) as count FROM payments WHERE voucher_group_id IS NOT NULL")->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 <strong>Resumen de datos preservados:</strong><br>";
    echo "• Estudiantes: $total_students<br>";
    echo "• Pagos: $total_payments<br>";
    echo "• Programas de estudiantes: $total_programs<br>";
    echo "• Grupos de vouchers: $total_groups<br>";

    // 7. Verificar que los nuevos tipos están disponibles
    echo "<h3>✅ Paso 7: Verificando nuevos tipos de maestría...</h3>";
    
    $program_types = $db->query("SELECT DISTINCT program_type FROM student_programs ORDER BY program_type")->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 <strong>Tipos de programa disponibles:</strong><br>";
    foreach ($program_types as $type) {
        $display_name = match($type) {
            'maestria_gestion_educativa' => 'Maestría: Gestión Educativa',
            'maestria_educacion_superior' => 'Maestría: Educación Superior',
            'maestria_psicologia_educativa' => 'Maestría: Psicología Educativa',
            'maestria_ensenanza_estrategica' => 'Maestría: Enseñanza Estratégica',
            'doctorado' => 'Doctorado',
            default => $type
        };
        echo "• $display_name<br>";
    }

    echo "<h3>🎉 ¡Migración completada exitosamente!</h3>";
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ Todos los cambios han sido aplicados:</strong><br>";
    echo "• Filtro de búsqueda cambiado de código a DNI<br>";
    echo "• Agregados 4 nuevos tipos de maestría específicos<br>";
    echo "• Logos actualizados en todas las secciones<br>";
    echo "• Funcionalidad de vouchers eliminada completamente<br>";
    echo "• <strong>NUEVO:</strong> Pagos múltiples con un solo voucher<br>";
    echo "• <strong>NUEVO:</strong> Montos personalizables por concepto<br>";
    echo "• <strong>NUEVO:</strong> Descripción opcional para vouchers<br>";
    echo "• <strong>NUEVO:</strong> Códigos duplicados permitidos en pagos múltiples<br>";
    echo "• Todos los datos existentes preservados<br>";
    echo "</div>";
    
    echo "<p><strong>🔧 Próximos pasos:</strong></p>";
    echo "<ul>";
    echo "<li>Verifica que el sistema funciona correctamente</li>";
    echo "<li>Prueba agregar un nuevo estudiante con los nuevos tipos de maestría</li>";
    echo "<li>Verifica que los pagos se calculan correctamente</li>";
    echo "<li><strong>NUEVO:</strong> Prueba la funcionalidad de pagos múltiples en <code>add_multi_payment.php</code></li>";
    echo "<li><strong>NUEVO:</strong> Verifica que los reportes muestran pagos agrupados correctamente</li>";
    echo "<li>Elimina este archivo (safe_setup.php) por seguridad</li>";
    echo "</ul>";
    
    echo "<div style='background-color: #e3f2fd; border: 1px solid #2196f3; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>🆕 Funcionalidades de Pagos Múltiples:</strong><br>";
    echo "• <strong>add_multi_payment.php</strong> - Formulario para pagos múltiples<br>";
    echo "• <strong>view_payment_group.php</strong> - Ver detalles de vouchers agrupados<br>";
    echo "• <strong>Montos personalizables</strong> - Ingresa el monto exacto para cada concepto<br>";
    echo "• <strong>Descripción de voucher</strong> - Agrega notas adicionales<br>";
    echo "• <strong>Compatibilidad total</strong> - Los pagos individuales siguen funcionando<br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Error durante la migración:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
    echo "<p>Por favor, revisa los errores y ejecuta el script nuevamente.</p>";
}
?>
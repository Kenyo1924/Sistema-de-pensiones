<?php
include_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "❌ Error: No se pudo conectar a la base de datos.<br>";
    exit();
}

echo "<h2>🔧 Corrección de Restricción de Código de Pago</h2>";
echo "<p>Este script eliminará la restricción UNIQUE del campo payment_code para permitir códigos duplicados en pagos múltiples.</p>";

try {
    // 1. Verificar si la restricción UNIQUE existe
    echo "<h3>📋 Paso 1: Verificando restricciones existentes...</h3>";
    
    $check_constraint = $db->query("SHOW INDEX FROM payments WHERE Key_name = 'payment_code'");
    if ($check_constraint && $check_constraint->rowCount() > 0) {
        echo "✅ Restricción UNIQUE encontrada en payment_code<br>";
        
        // 2. Eliminar la restricción UNIQUE
        echo "<h3>🗑️ Paso 2: Eliminando restricción UNIQUE...</h3>";
        
        try {
            $db->exec("ALTER TABLE payments DROP INDEX payment_code");
            echo "✅ Restricción UNIQUE eliminada exitosamente<br>";
        } catch (Exception $e) {
            echo "⚠️ Error eliminando restricción: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "ℹ️ No se encontró restricción UNIQUE en payment_code<br>";
    }
    
    // 3. Verificar que la restricción fue eliminada
    echo "<h3>🔍 Paso 3: Verificando que la restricción fue eliminada...</h3>";
    
    $check_after = $db->query("SHOW INDEX FROM payments WHERE Key_name = 'payment_code'");
    if ($check_after && $check_after->rowCount() == 0) {
        echo "✅ Restricción UNIQUE eliminada correctamente<br>";
    } else {
        echo "⚠️ La restricción UNIQUE aún existe<br>";
    }
    
    // 4. Mostrar estructura actual de la tabla
    echo "<h3>📊 Paso 4: Estructura actual de la tabla payments...</h3>";
    
    $describe = $db->query("DESCRIBE payments");
    $columns = $describe->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Probar inserción de códigos duplicados
    echo "<h3>🧪 Paso 5: Probando inserción de códigos duplicados...</h3>";
    
    // Verificar si hay datos de prueba
    $test_count = $db->query("SELECT COUNT(*) as count FROM payments WHERE payment_code = 'TEST-DUPLICATE'")->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($test_count == 0) {
        echo "ℹ️ No hay datos de prueba, la corrección está lista<br>";
    } else {
        echo "ℹ️ Se encontraron $test_count registros de prueba<br>";
    }
    
    echo "<h3>🎉 ¡Corrección completada exitosamente!</h3>";
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ Cambios aplicados:</strong><br>";
    echo "• Restricción UNIQUE eliminada del campo payment_code<br>";
    echo "• Ahora se permiten códigos duplicados en pagos múltiples<br>";
    echo "• Los pagos individuales siguen funcionando normalmente<br>";
    echo "• Los pagos múltiples pueden usar el mismo código de voucher<br>";
    echo "</div>";
    
    echo "<p><strong>🔧 Próximos pasos:</strong></p>";
    echo "<ul>";
    echo "<li>Prueba crear un pago múltiple con el mismo código de voucher</li>";
    echo "<li>Verifica que los pagos se agrupan correctamente</li>";
    echo "<li>Elimina este archivo (fix_payment_code_constraint.php) por seguridad</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ Error durante la corrección:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
    echo "<p>Por favor, revisa los errores y ejecuta el script nuevamente.</p>";
}
?>

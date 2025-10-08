<?php
session_start();
include_once 'backend/config/database.php';

echo "<h1>🔧 Arreglar Usuario Superadmin - Diagnóstico Detallado</h1>";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<h2>✅ Conexión a Base de Datos: EXITOSA</h2>";
    
    // Verificar la estructura de la tabla
    echo "<h3>📋 Estructura de la tabla users:</h3>";
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al verificar estructura: " . $e->getMessage() . "</p>";
    }
    
    // Verificar el usuario actual
    echo "<h3>👤 Usuario superadmin actual:</h3>";
    try {
        $stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al consultar usuario: " . $e->getMessage() . "</p>";
    }
    
    // Intentar actualización con más detalles
    echo "<h3>🔧 Intentando actualización...</h3>";
    try {
        // Primero, verificar si el campo role existe y su tipo
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
        $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>Información del campo 'role':</strong></p>";
        echo "<pre>";
        print_r($roleColumn);
        echo "</pre>";
        
        // Intentar la actualización
        $updateQuery = "UPDATE users SET role = 'superadmin' WHERE username = 'superadmin'";
        echo "<p><strong>Query a ejecutar:</strong> " . $updateQuery . "</p>";
        
        $stmt = $db->prepare($updateQuery);
        $result = $stmt->execute();
        
        if ($result) {
            $affectedRows = $stmt->rowCount();
            echo "<p style='color: green;'>✅ Query ejecutado. Filas afectadas: " . $affectedRows . "</p>";
            
            // Verificar el resultado
            $stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>👤 Usuario superadmin después de la actualización:</h3>";
            echo "<pre>";
            print_r($user);
            echo "</pre>";
            
            if ($user['role'] == 'superadmin') {
                echo "<p style='color: green; font-size: 18px;'>🎉 ¡ÉXITO! El rol se actualizó correctamente</p>";
                echo "<p style='color: blue;'>🔄 Ahora cierra sesión y vuelve a iniciar sesión como superadmin</p>";
            } else {
                echo "<p style='color: red;'>❌ El rol sigue vacío. Intentando solución alternativa...</p>";
                
                // Solución alternativa: eliminar y recrear el usuario
                echo "<h3>🔄 Solución alternativa: Recrear usuario</h3>";
                $db->exec("DELETE FROM users WHERE username = 'superadmin'");
                
                $password = password_hash('superadmin123', PASSWORD_BCRYPT);
                $insertQuery = "INSERT INTO users (username, password, role) VALUES ('superadmin', ?, 'superadmin')";
                $stmt = $db->prepare($insertQuery);
                $result = $stmt->execute([$password]);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Usuario superadmin recreado correctamente</p>";
                    
                    $stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<pre>";
                    print_r($user);
                    echo "</pre>";
                } else {
                    echo "<p style='color: red;'>❌ Error al recrear el usuario</p>";
                }
            }
            
        } else {
            echo "<p style='color: red;'>❌ Error al ejecutar la actualización</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<h2>❌ Conexión a Base de Datos: FALLIDA</h2>";
}
?>

<p><a href="logout.php">🚪 Cerrar Sesión</a></p>
<p><a href="login.php">🔑 Ir al Login</a></p>
<p><a href="debug_session.php">🔍 Ver Diagnóstico</a></p>

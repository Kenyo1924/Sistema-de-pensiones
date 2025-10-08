<?php
session_start();
include_once 'backend/config/database.php';

echo "<h1>🔧 Agregar Rol Superadmin a la Base de Datos</h1>";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<h2>✅ Conexión a Base de Datos: EXITOSA</h2>";
    
    // Verificar la estructura actual de la tabla
    echo "<h3>📋 Estructura actual de la tabla users:</h3>";
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Buscar la columna role
        $roleColumn = null;
        foreach ($columns as $column) {
            if ($column['Field'] == 'role') {
                $roleColumn = $column;
                break;
            }
        }
        
        if ($roleColumn) {
            echo "<h3>🔍 Información del campo 'role':</h3>";
            echo "<pre>";
            print_r($roleColumn);
            echo "</pre>";
            
            // Verificar si 'superadmin' está en los valores permitidos
            if (strpos($roleColumn['Type'], 'superadmin') === false) {
                echo "<h3>🔧 Modificando la tabla para permitir 'superadmin':</h3>";
                
                // Modificar la columna role para incluir 'superadmin'
                $alterQuery = "ALTER TABLE users MODIFY COLUMN role ENUM('superadmin', 'editor', 'viewer') NOT NULL";
                echo "<p><strong>Query a ejecutar:</strong> " . $alterQuery . "</p>";
                
                $result = $db->exec($alterQuery);
                
                if ($result !== false) {
                    echo "<p style='color: green;'>✅ Tabla modificada correctamente</p>";
                    
                    // Verificar la nueva estructura
                    $stmt = $db->query("DESCRIBE users");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "<h3>📋 Nueva estructura de la tabla users:</h3>";
                    echo "<pre>";
                    print_r($columns);
                    echo "</pre>";
                    
                    // Ahora actualizar el usuario superadmin
                    echo "<h3>👤 Actualizando usuario superadmin:</h3>";
                    $updateQuery = "UPDATE users SET role = 'superadmin' WHERE username = 'superadmin'";
                    $stmt = $db->prepare($updateQuery);
                    $result = $stmt->execute();
                    
                    if ($result) {
                        $affectedRows = $stmt->rowCount();
                        echo "<p style='color: green;'>✅ Usuario superadmin actualizado. Filas afectadas: " . $affectedRows . "</p>";
                        
                        // Verificar el resultado
                        $stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        echo "<h3>👤 Usuario superadmin después de la actualización:</h3>";
                        echo "<pre>";
                        print_r($user);
                        echo "</pre>";
                        
                        if ($user['role'] == 'superadmin') {
                            echo "<p style='color: green; font-size: 18px;'>🎉 ¡ÉXITO! El rol superadmin se agregó correctamente</p>";
                            echo "<p style='color: blue;'>🔄 Ahora cierra sesión y vuelve a iniciar sesión como superadmin</p>";
                        } else {
                            echo "<p style='color: red;'>❌ El rol sigue vacío</p>";
                        }
                        
                    } else {
                        echo "<p style='color: red;'>❌ Error al actualizar el usuario</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>❌ Error al modificar la tabla</p>";
                }
                
            } else {
                echo "<p style='color: green;'>✅ El rol 'superadmin' ya está permitido en la tabla</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ No se encontró la columna 'role'</p>";
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

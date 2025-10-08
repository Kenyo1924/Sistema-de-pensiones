<?php
session_start();
include_once 'backend/config/database.php';

echo "<h1>🔧 Arreglar Usuario Superadmin</h1>";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<h2>✅ Conexión a Base de Datos: EXITOSA</h2>";
    
    // Actualizar el rol del usuario superadmin
    try {
        $stmt = $db->prepare("UPDATE users SET role = 'superadmin' WHERE username = 'superadmin'");
        $result = $stmt->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Rol de superadmin actualizado correctamente</p>";
            
            // Verificar el cambio
            $stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>👤 Usuario superadmin después de la actualización:</h3>";
            echo "<pre>";
            print_r($user);
            echo "</pre>";
            
            echo "<p style='color: blue;'>🔄 Ahora cierra sesión y vuelve a iniciar sesión como superadmin</p>";
            
        } else {
            echo "<p style='color: red;'>❌ Error al actualizar el rol</p>";
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

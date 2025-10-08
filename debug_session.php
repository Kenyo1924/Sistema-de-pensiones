<?php
session_start();
include_once 'backend/config/database.php';
include_once 'backend/core/user.php';

echo "<h1>🔍 Diagnóstico de Sesión y Base de Datos</h1>";

// Verificar sesión
echo "<h2>📋 Estado de la Sesión:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar base de datos
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<h2>✅ Conexión a Base de Datos: EXITOSA</h2>";
    
    // Verificar tabla users
    try {
        $stmt = $db->query("SELECT * FROM users WHERE username = 'superadmin'");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>👤 Usuario 'superadmin' en la base de datos:</h3>";
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Usuario 'superadmin' NO encontrado en la base de datos</p>";
        }
        
        // Verificar todos los usuarios
        echo "<h3>👥 Todos los usuarios en la base de datos:</h3>";
        $stmt = $db->query("SELECT id, username, role, created_at FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($users);
        echo "</pre>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error al consultar usuarios: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<h2>❌ Conexión a Base de Datos: FALLIDA</h2>";
}

// Probar login manual
echo "<h2>🧪 Prueba de Login Manual:</h2>";
if (isset($_POST['test_login'])) {
    $user = new User($db);
    $user->username = $_POST['username'];
    $password = $_POST['password'];
    
    $user_data = $user->login($password);
    
    if ($user_data) {
        echo "<p style='color: green;'>✅ Login exitoso:</p>";
        echo "<pre>";
        print_r($user_data);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Login fallido</p>";
    }
}
?>

<form method="post" style="margin: 20px 0; padding: 20px; border: 1px solid #ccc;">
    <h3>Probar Login:</h3>
    <input type="text" name="username" placeholder="Usuario" value="superadmin" required>
    <input type="password" name="password" placeholder="Contraseña" value="superadmin123" required>
    <button type="submit" name="test_login">Probar Login</button>
</form>

<p><a href="test_roles.php">← Volver a Prueba de Roles</a></p>
<p><a href="add_superadmin_role.php" style="color: #007bff;">🔧 Agregar Rol Superadmin</a></p>
<p><a href="fix_superadmin.php" style="color: #28a745;">🔧 Arreglar Usuario Superadmin</a></p>
<p><a href="fix_superadmin_detailed.php" style="color: #dc3545;">🔍 Diagnóstico Detallado</a></p>
<p><a href="setup.php">🔄 Ejecutar Setup</a></p>

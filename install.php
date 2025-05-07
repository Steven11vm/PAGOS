<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'invoice_approval_system';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Sistema de Aprobación de Facturas - Instalación</h1>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>✓ Base de datos creada correctamente</p>";
} else {
    echo "<p style='color:red'>✗ Error al crear la base de datos: " . $conn->error . "</p>";
}

// Select database
$conn->select_db($db_name);

// Read SQL file
$sql_file = file_get_contents('database.sql');

// Execute SQL commands
if ($conn->multi_query($sql_file)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "<p style='color:green'>✓ Tablas de la base de datos creadas correctamente</p>";
} else {
    echo "<p style='color:red'>✗ Error al crear las tablas: " . $conn->error . "</p>";
}

// Create uploads directory
$uploads_dir = 'uploads/invoices';
if (!is_dir($uploads_dir)) {
    if (mkdir($uploads_dir, 0755, true)) {
        echo "<p style='color:green'>✓ Directorio de uploads creado correctamente</p>";
    } else {
        echo "<p style='color:red'>✗ Error al crear el directorio de uploads</p>";
    }
}

// Close connection
$conn->close();

echo "<div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
    <h2>Instalación completada</h2>
    <p>El sistema ha sido instalado correctamente. Ahora puede acceder al sistema con las siguientes credenciales:</p>
    <ul>
        <li><strong>Administrador:</strong> admin@example.com / admin123</li>
        <li><strong>Subgerente:</strong> subgerente@example.com / admin123</li>
        <li><strong>Gerente:</strong> gerente@example.com / admin123</li>
        <li><strong>Contador:</strong> contador@example.com / admin123</li>
    </ul>
    <p><a href='index.php' style='display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Ir al sistema</a></p>
</div>";
?>

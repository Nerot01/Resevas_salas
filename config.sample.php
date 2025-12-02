<?php
// config.sample.php
// Copia este archivo a config.php y rellena con tus credenciales reales.

$host = 'localhost';
$db_name = 'tu_base_de_datos';
$username = 'tu_usuario';
$password = 'tu_contraseña';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Email configuration (for notifications)
$admin_emails = ['correo1@ejemplo.com', 'correo2@ejemplo.com'];
?>
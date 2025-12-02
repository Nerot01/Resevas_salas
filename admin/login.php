<?php
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Check in admin_users table
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: index.php");
            exit;
        } else {
            $error = "Credenciales incorrectas.";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="booking-form">
            <h2 style="text-align: center; margin-bottom: 1.5rem; color: var(--primary-color);">Administración</h2>
            <?php if (isset($error)): ?>
                <p style="color: var(--error-color); margin-bottom: 1rem; text-align: center;"><?= $error ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Ingresar</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;"><a href="../index.php"
                    style="color: var(--accent-color);">Volver al inicio</a></p>
        </div>
    </div>
</body>

</html>
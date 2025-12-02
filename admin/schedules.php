<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Handle Add Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $room_id = $_POST['room_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $class_name = $_POST['class_name'];
    $teacher_name = $_POST['teacher_name'];

    $stmt = $pdo->prepare("INSERT INTO res_schedules (room_id, day_of_week, start_time, end_time, class_name, teacher_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$room_id, $day_of_week, $start_time, $end_time, $class_name, $teacher_name]);

    header("Location: schedules.php");
    exit;
}

// Handle Delete Schedule
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM res_schedules WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: schedules.php");
    exit;
}

// Fetch rooms
$stmt = $pdo->query("SELECT * FROM res_rooms");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch schedules
$stmt = $pdo->query("
    SELECT s.*, r.name as room_name 
    FROM res_schedules s 
    JOIN res_rooms r ON s.room_id = r.id 
    ORDER BY FIELD(day_of_week, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), start_time
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Horarios</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <header>
        <h1>Gestionar Horarios</h1>
        <nav>
            <a href="index.php">Volver al Panel</a>
            <a href="logout.php">Cerrar Sesión</a>
        </nav>
    </header>

    <div class="container">

        <div class="booking-form" style="max-width: 100%; margin-bottom: 3rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Agregar Nuevo Horario</h3>
            <form method="POST"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Sala</label>
                    <select name="room_id" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Día</label>
                    <select name="day_of_week" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                        <option value="Domingo">Domingo</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Inicio</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Fin</label>
                    <input type="time" name="end_time" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Actividad/Clase</label>
                    <input type="text" name="class_name" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Responsable</label>
                    <input type="text" name="teacher_name">
                </div>
                <button type="submit" name="add_schedule" class="btn" style="height: 42px;">Agregar</button>
            </form>
        </div>

        <h3 class="section-title">Horarios Actuales</h3>
        <div class="schedule-container">
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Horario</th>
                        <th>Sala</th>
                        <th>Actividad</th>
                        <th>Responsable</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                            <td><?= date('H:i', strtotime($row['start_time'])) ?> -
                                <?= date('H:i', strtotime($row['end_time'])) ?></td>
                            <td><?= htmlspecialchars($row['room_name']) ?></td>
                            <td><?= htmlspecialchars($row['class_name']) ?></td>
                            <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                            <td>
                                <a href="?delete=<?= $row['id'] ?>" class="action-btn reject-btn"
                                    onclick="return confirm('¿Eliminar este horario?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
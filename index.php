<?php
require_once 'config.php';

// Fetch rooms for the dropdown
try {
    $stmt = $pdo->query("SELECT * FROM res_rooms");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rooms = [];
}

// Fetch schedules to display
try {
    $stmt = $pdo->query("
        SELECT s.*, r.name as room_name 
        FROM res_schedules s 
        JOIN res_rooms r ON s.room_id = r.id 
        ORDER BY FIELD(day_of_week, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'), start_time
    ");
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $schedules = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Salas - Ejecutivo</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <header>
        <h1>Gestión de Espacios</h1>
        <nav>
            <a href="#horarios">Horarios</a>
            <a href="#reservar" class="btn" onclick="openModal()">Reservar Sala</a>
            <a href="admin/login.php">Admin</a>
        </nav>
    </header>

    <div class="container">
        <div class="hero">
            <h2>Espacios para la Excelencia</h2>
            <p>Consulta la disponibilidad y gestiona tus reservas de manera eficiente y profesional.</p>
        </div>

        <section id="horarios">
            <h3 class="section-title">Horarios de Clases y Actividades</h3>
            <div class="schedule-container">
                <table>
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Horario</th>
                            <th>Sala</th>
                            <th>Actividad</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No hay actividades programadas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                                    <td><?= date('H:i', strtotime($row['start_time'])) ?> -
                                        <?= date('H:i', strtotime($row['end_time'])) ?></td>
                                    <td><?= htmlspecialchars($row['room_name']) ?></td>
                                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                                    <td><?= htmlspecialchars($row['teacher_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">Solicitud de Reserva</h3>
            <form action="api/process_booking.php" method="POST">
                <div class="form-group">
                    <label for="user_name">Nombre Completo</label>
                    <input type="text" id="user_name" name="user_name" required>
                </div>
                <div class="form-group">
                    <label for="user_email">Correo Electrónico</label>
                    <input type="email" id="user_email" name="user_email" required>
                </div>
                <div class="form-group">
                    <label for="room_id">Sala</label>
                    <select id="room_id" name="room_id" required>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?> (Cap:
                                <?= $room['capacity'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="booking_date">Fecha</label>
                    <input type="date" id="booking_date" name="booking_date" required>
                </div>
                <div class="form-group" style="display: flex; gap: 1rem;">
                    <div style="flex: 1;">
                        <label for="start_time">Inicio</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    <div style="flex: 1;">
                        <label for="end_time">Fin</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reason">Motivo</label>
                    <textarea id="reason" name="reason" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Enviar Solicitud</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date('Y') ?> Sistema de Reservas. Todos los derechos reservados.</p>
    </footer>

    <script>
        function openModal() {
            document.getElementById('bookingModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('bookingModal').classList.remove('active');
        }

        // Close modal if clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('bookingModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>

</html>
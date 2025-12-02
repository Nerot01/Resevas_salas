<?php
require_once 'config.php';
require_once 'includes/time_slots.php';

// Fetch rooms for the dropdown (booking form)
try {
    $stmt = $pdo->query("SELECT * FROM res_rooms");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rooms = [];
}

// Fetch fixed schedules
try {
    $stmt = $pdo->query("
        SELECT s.*, r.name as room_name 
        FROM res_schedules s 
        JOIN res_rooms r ON s.room_id = r.id 
    ");
    $raw_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $raw_schedules = [];
}

// Fetch approved bookings for the current week
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week = date('Y-m-d', strtotime('sunday this week'));

try {
    $stmt = $pdo->prepare("
        SELECT b.*, r.name as room_name 
        FROM res_bookings b 
        JOIN res_rooms r ON b.room_id = r.id 
        WHERE b.status = 'approved' 
        AND b.booking_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_of_week, $end_of_week]);
    $approved_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $approved_bookings = [];
}

// Organize schedules into a grid: [time_slot][day] = content
$schedule_grid = [];

// Helper to check overlap
function is_in_slot($slot, $start_time, $end_time) {
    $slot_start = explode(' - ', $slot)[0];
    $slot_end = explode(' - ', $slot)[1];
    
    // Check if the slot is fully contained within the class time
    // We add :00 to match H:i:s format if needed, but usually H:i works
    return ($slot_start >= substr($start_time, 0, 5) && $slot_end <= substr($end_time, 0, 5));
}

// 1. Add Fixed Schedules
foreach ($raw_schedules as $row) {
    foreach ($time_slots as $slot) {
        if (is_in_slot($slot, $row['start_time'], $row['end_time'])) {
            $row['type'] = 'class'; 
            $schedule_grid[$slot][$row['day_of_week']][] = $row;
        }
    }
}

// 2. Add Approved Bookings
$day_of_week_map_rev = [
    'Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'
];

foreach ($approved_bookings as $row) {
    $day_name = date('l', strtotime($row['booking_date']));
    $day_spanish = $day_of_week_map_rev[$day_name];
    
    foreach ($time_slots as $slot) {
        if (is_in_slot($slot, $row['start_time'], $row['end_time'])) {
            $row['type'] = 'booking'; 
            $row['class_name'] = "Reserva: " . $row['user_name']; 
            $row['teacher_name'] = $row['reason'];
            $schedule_grid[$slot][$day_spanish][] = $row;
        }
    }
}

$days_of_week = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario de Clases</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        <h2>Horario Semestral</h2>
        <p>Semana del <?= date('d/m', strtotime($start_of_week)) ?> al <?= date('d/m', strtotime($end_of_week)) ?></p>
        <button class="btn" onclick="downloadPDF()" style="background-color: var(--secondary-color); margin-top: 1rem;">Descargar PDF</button>
    </div>

    <section id="horarios">
        <div class="schedule-container" id="schedule-to-print">
            <div class="pdf-header" style="display: none; text-align: center; margin-bottom: 20px;">
                <h2>Horario de Clases</h2>
                <p>Generado el: <span id="print-date"></span></p>
            </div>
            <table class="grid-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Horario</th>
                        <?php foreach ($days_of_week as $day): ?>
                            <th><?= $day ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_slots as $slot): ?>
                    <tr>
                        <td class="time-cell"><?= $slot ?></td>
                        <?php foreach ($days_of_week as $day): ?>
                            <td class="slot-cell">
                                <?php if (isset($schedule_grid[$slot][$day])): ?>
                                    <?php foreach ($schedule_grid[$slot][$day] as $class): ?>
                                        <div class="class-block <?= $class['type'] === 'booking' ? 'booking-block' : '' ?>">
                                            <strong><?= htmlspecialchars($class['class_name']) ?></strong><br>
                                            <span class="room-tag"><?= htmlspecialchars($class['room_name']) ?></span><br>
                                            <small><?= htmlspecialchars($class['teacher_name']) ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
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
                        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?> (Cap: <?= $room['capacity'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="booking_date">Fecha</label>
                <input type="date" id="booking_date" name="booking_date" required>
            </div>
            
            <!-- Updated Time Inputs to Blocks -->
            <div class="form-group">
                <label for="start_slot">Bloque Inicio</label>
                <select id="start_slot" name="start_slot" required>
                    <?php foreach ($time_slots as $slot): ?>
                        <option value="<?= $slot ?>"><?= $slot ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="num_blocks">Duración</label>
                <select id="num_blocks" name="num_blocks" required>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?> Bloque(s)</option>
                    <?php endfor; ?>
                </select>
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
    <p>&copy; <?= date('Y') ?> Sistema de Reservas.</p>
</footer>

<script>
    function openModal() {
        document.getElementById('bookingModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('bookingModal').classList.remove('active');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('bookingModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    function downloadPDF() {
        const element = document.getElementById('schedule-to-print');
        const dateSpan = document.getElementById('print-date');
        const header = document.querySelector('.pdf-header');
        
        dateSpan.innerText = new Date().toLocaleDateString();
        header.style.display = 'block';

        const opt = {
            margin:       0.2,
            filename:     'horario_clases.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            header.style.display = 'none';
        });
    }
</script>

</body>
</html>
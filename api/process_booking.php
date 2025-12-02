<?php
require_once '../config.php';
require_once '../includes/time_slots.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = $_POST['user_name'] ?? '';
    $user_email = $_POST['user_email'] ?? '';
    $room_id = $_POST['room_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $start_slot = $_POST['start_slot'] ?? '';
    $num_blocks = (int) ($_POST['num_blocks'] ?? 1);
    $reason = $_POST['reason'] ?? '';

    if (empty($user_name) || empty($user_email) || empty($room_id) || empty($booking_date) || empty($start_slot)) {
        die("Por favor complete todos los campos obligatorios.");
    }

    // Calculate times from blocks
    $start_time = get_slot_start($start_slot);
    $end_time = get_end_time_from_blocks($start_slot, $num_blocks);

    if (!$end_time) {
        die("Error: El número de bloques excede el horario disponible.");
    }

    try {
        // 1. Check for conflicts with fixed schedules (res_schedules)
        $day_of_week_map = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        $day_name = date('l', strtotime($booking_date));
        $day_spanish = $day_of_week_map[$day_name];

        $stmt = $pdo->prepare("
            SELECT * FROM res_schedules 
            WHERE room_id = ? 
            AND day_of_week = ? 
            AND (
                (start_time < ? AND end_time > ?) OR 
                (start_time < ? AND end_time > ?) OR 
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $stmt->execute([$room_id, $day_spanish, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Error: El horario seleccionado entra en conflicto con una clase programada.'); window.history.back();</script>";
            exit;
        }

        // 2. Check for conflicts with existing approved bookings (res_bookings)
        $stmt = $pdo->prepare("
            SELECT * FROM res_bookings 
            WHERE room_id = ? 
            AND booking_date = ? 
            AND status = 'approved'
            AND (
                (start_time < ? AND end_time > ?) OR 
                (start_time < ? AND end_time > ?) OR 
                (start_time >= ? AND end_time <= ?)
            )
        ");
        $stmt->execute([$room_id, $booking_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Error: El horario seleccionado ya está reservado por otro usuario.'); window.history.back();</script>";
            exit;
        }

        // Insert booking into database
        $stmt = $pdo->prepare("INSERT INTO res_bookings (room_id, user_name, user_email, booking_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room_id, $user_name, $user_email, $booking_date, $start_time, $end_time, $reason]);

        // Send email to manager
        $subject = "Nueva Solicitud de Reserva - " . $booking_date;
        $message = "Se ha recibido una nueva solicitud de reserva:\n\n";
        $message .= "Solicitante: $user_name ($user_email)\n";
        $message .= "Sala ID: $room_id\n";
        $message .= "Fecha: $booking_date\n";
        $message .= "Bloque: $start_slot ($num_blocks bloques)\n";
        $message .= "Horario: $start_time - $end_time\n";
        $message .= "Motivo: $reason\n\n";
        $message .= "Por favor ingrese al panel de administración para aprobar o rechazar.";

        $headers = "From: no-reply@reservassalas.com";

        // Use mail() function. Ensure server is configured.
        $sent = false;
        if (isset($admin_emails) && is_array($admin_emails)) {
            foreach ($admin_emails as $email) {
                if (mail($email, $subject, $message, $headers)) {
                    $sent = true;
                }
            }
        } elseif (isset($admin_email)) {
            if (mail($admin_email, $subject, $message, $headers)) {
                $sent = true;
            }
        }

        if ($sent) {
            echo "<script>alert('Solicitud enviada con éxito. Espere confirmación.'); window.location.href='../index.php';</script>";
        } else {
            echo "<script>alert('Solicitud guardada, pero hubo un error enviando el correo. Contacte al administrador.'); window.location.href='../index.php';</script>";
        }

    } catch (PDOException $e) {
        die("Error al procesar la solicitud: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit;
}
?>
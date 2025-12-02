<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = $_POST['user_name'] ?? '';
    $user_email = $_POST['user_email'] ?? '';
    $room_id = $_POST['room_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if (empty($user_name) || empty($user_email) || empty($room_id) || empty($booking_date) || empty($start_time) || empty($end_time)) {
        die("Por favor complete todos los campos obligatorios.");
    }

    try {
        // Insert booking into database
        $stmt = $pdo->prepare("INSERT INTO res_bookings (room_id, user_name, user_email, booking_date, start_time, end_time, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room_id, $user_name, $user_email, $booking_date, $start_time, $end_time, $reason]);

        // Send email to manager
        $subject = "Nueva Solicitud de Reserva - " . $booking_date;
        $message = "Se ha recibido una nueva solicitud de reserva:\n\n";
        $message .= "Solicitante: $user_name ($user_email)\n";
        $message .= "Sala ID: $room_id\n";
        $message .= "Fecha: $booking_date\n";
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
            // Fallback for backward compatibility if array not set
            if (mail($admin_email, $subject, $message, $headers)) {
                $sent = true;
            }
        }

        if ($sent) {
            echo "<script>alert('Solicitud enviada con éxito. Espere confirmación.'); window.location.href='../index.php';</script>";
        } else {
            // Fallback if mail fails (common in local dev)
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
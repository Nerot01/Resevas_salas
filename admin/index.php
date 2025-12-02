<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Handle booking actions (Approve/Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['action'] === 'approve' ? 'approved' : 'rejected';

    $stmt = $pdo->prepare("UPDATE res_bookings SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // Optional: Send email to user about status change (omitted for brevity but recommended)

    header("Location: index.php");
    exit;
}

// Fetch pending bookings
$stmt = $pdo->query("SELECT b.*, r.name as room_name FROM res_bookings b JOIN res_rooms r ON b.room_id = r.id WHERE b.status = 'pending' ORDER BY b.created_at DESC");
$pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch approved bookings (limit 10)
$stmt = $pdo->query("SELECT b.*, r.name as room_name FROM res_bookings b JOIN res_rooms r ON b.room_id = r.id WHERE b.status = 'approved' ORDER BY b.booking_date DESC LIMIT 10");
$approved_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            text-decoration: none;
            border-radius: 0.25rem;
            color: white;
        }

        .approve-btn {
            background-color: var(--success-color);
        }

        .reject-btn {
            background-color: var(--error-color);
        }
    </style>
</head>

<body>

    <header>
        <h1>Panel de Administración</h1>
        <nav>
            <a href="../index.php" target="_blank">Ver Sitio</a>
            <a href="schedules.php">Gestionar Horarios</a>
            <a href="logout.php">Cerrar Sesión</a>
        </nav>
    </header>

    <div class="container">
        <div class="admin-header">
            <h2>Solicitudes Pendientes</h2>
        </div>

        <div class="schedule-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Sala</th>
                        <th>Solicitante</th>
                        <th>Motivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending_bookings)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No hay solicitudes pendientes.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pending_bookings as $row): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['booking_date'])) ?></td>
                                <td><?= date('H:i', strtotime($row['start_time'])) ?> -
                                    <?= date('H:i', strtotime($row['end_time'])) ?></td>
                                <td><?= htmlspecialchars($row['room_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['user_name']) ?><br>
                                    <small><?= htmlspecialchars($row['user_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['reason']) ?></td>
                                <td>
                                    <a href="?action=approve&id=<?= $row['id'] ?>" class="action-btn approve-btn"
                                        onclick="return confirm('¿Aprobar reserva?')">Aprobar</a>
                                    <a href="?action=reject&id=<?= $row['id'] ?>" class="action-btn reject-btn"
                                        onclick="return confirm('¿Rechazar reserva?')">Rechazar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 style="margin-bottom: 1rem; color: var(--primary-color);">Últimas Reservas Aprobadas</h2>
        <div class="schedule-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th>Sala</th>
                        <th>Solicitante</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved_bookings as $row): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['booking_date'])) ?></td>
                            <td><?= date('H:i', strtotime($row['start_time'])) ?> -
                                <?= date('H:i', strtotime($row['end_time'])) ?></td>
                            <td><?= htmlspecialchars($row['room_name']) ?></td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><span class="status-badge status-approved">Aprobado</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
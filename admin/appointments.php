<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('admin');
$pdo = getDBConnection();
$sql = "SELECT a.id, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, a.appointment_date, a.appointment_time, a.status, a.reason
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC";
$stmt = $pdo->query($sql);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<div class="container">
    <h2>Appointment Management</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="7" class="text-center">No appointments found.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= htmlspecialchars($appt['id']) ?></td>
                    <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                    <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($appt['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($appt['appointment_time']) ?></td>
                    <td><span class="badge bg-<?= $appt['status'] === 'scheduled' ? 'warning' : ($appt['status'] === 'completed' ? 'success' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($appt['status']) ?></span></td>
                    <td><?= htmlspecialchars($appt['reason']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
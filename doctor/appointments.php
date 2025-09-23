<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('doctor'); // Ensure only doctors can access

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get the doctor's ID from the doctors table using the user_id
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor ? $doctor['id'] : 0;

$appointments = [];
if ($doctor_id > 0) {
    $stmt = $pdo->prepare("
        SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-calendar-check"></i> My Appointments</h2>

    <?php if (empty($appointments)): ?>
        <div class="alert alert-info" role="alert">
            No appointments found for you.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?= htmlspecialchars($appt['id']) ?></td>
                        <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                        <td><?= htmlspecialchars($appt['appointment_date']) ?></td>
                        <td><?= htmlspecialchars($appt['appointment_time']) ?></td>
                        <td><?= htmlspecialchars($appt['reason']) ?></td>
                        <td><span class="badge bg-<?= $appt['status'] === 'scheduled' ? 'warning' : ($appt['status'] === 'completed' ? 'success' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($appt['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
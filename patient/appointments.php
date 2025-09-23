<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
if (!hasRole('patient')) {
    header('Location: ../public/dashboard.php');
    exit;
}
$pdo = getDBConnection();
$user_id = $_SESSION['user_id']; // This is the user_id from the users table

// Get patient record id from the patients table using the user_id
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$pat = $stmt->fetch(PDO::FETCH_ASSOC);
$pid = $pat ? $pat['id'] : 0;

$appointments = [];
if ($pid > 0) {
    $stmt = $pdo->prepare("SELECT a.*, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name FROM appointments a JOIN doctors d ON a.doctor_id = d.id WHERE a.patient_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
    $stmt->execute([$pid]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<div class="container">
    <h2>My Appointments</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Status</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="5" class="text-center">No appointments found.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= htmlspecialchars($appt['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($appt['appointment_time']) ?></td>
                    <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td><span class="badge bg-<?= $appt['status'] === 'scheduled' ? 'warning' : ($appt['status'] === 'completed' ? 'success' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($appt['status']) ?></span></td>
                    <td><?= htmlspecialchars($appt['reason']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
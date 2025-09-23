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

$reports = [];
if ($pid > 0) {
    $stmt = $pdo->prepare("SELECT r.*, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name FROM medical_reports r JOIN doctors d ON r.doctor_id = d.id WHERE r.patient_id = ? ORDER BY r.report_date DESC, r.id DESC");
    $stmt->execute([$pid]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<div class="container">
    <h2>My Medical Reports</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Type</th>
                <th>Doctor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="5" class="text-center">No reports found.</td></tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= htmlspecialchars($report['report_date']) ?></td>
                    <td><?= htmlspecialchars($report['report_title']) ?></td>
                    <td><?= htmlspecialchars($report['report_type']) ?></td>
                    <td><?= htmlspecialchars($report['doctor_name']) ?></td>
                    <td><span class="badge bg-<?= $report['status'] === 'completed' ? 'success' : ($report['status'] === 'pending' ? 'warning' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($report['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
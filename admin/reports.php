<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('admin');
$pdo = getDBConnection();
$sql = "SELECT r.id, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, r.report_type, r.report_title, r.report_date, r.status
        FROM medical_reports r
        JOIN patients p ON r.patient_id = p.id
        JOIN doctors d ON r.doctor_id = d.id
        ORDER BY r.report_date DESC, r.id DESC";
$stmt = $pdo->query($sql);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<div class="container">
    <h2>Medical Reports</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Type</th>
                <th>Title</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reports)): ?>
                <tr><td colspan="7" class="text-center">No reports found.</td></tr>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= htmlspecialchars($report['id']) ?></td>
                    <td><?= htmlspecialchars($report['patient_name']) ?></td>
                    <td><?= htmlspecialchars($report['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($report['report_type']) ?></td>
                    <td><?= htmlspecialchars($report['report_title']) ?></td>
                    <td><?= htmlspecialchars($report['report_date']) ?></td>
                    <td><span class="badge bg-<?= $report['status'] === 'completed' ? 'success' : ($report['status'] === 'pending' ? 'warning' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($report['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
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

$prescriptions = [];
if ($doctor_id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.id, CONCAT(pat.first_name, ' ', pat.last_name) AS patient_name,
               p.medication, p.dosage, p.instructions, p.issue_date, p.status
        FROM prescriptions p
        JOIN patients pat ON p.patient_id = pat.id
        WHERE p.doctor_id = ?
        ORDER BY p.issue_date DESC, p.id DESC
    ");
    $stmt->execute([$doctor_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-prescription"></i> My Prescriptions</h2>

    <?php if (empty($prescriptions)): ?>
        <div class="alert alert-info" role="alert">
            No prescriptions found issued by you.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Instructions</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prescriptions as $prescription): ?>
                    <tr>
                        <td><?= htmlspecialchars($prescription['id']) ?></td>
                        <td><?= htmlspecialchars($prescription['patient_name']) ?></td>
                        <td><?= htmlspecialchars($prescription['medication']) ?></td>
                        <td><?= htmlspecialchars($prescription['dosage']) ?></td>
                        <td><?= htmlspecialchars($prescription['instructions']) ?></td>
                        <td><?= htmlspecialchars($prescription['issue_date']) ?></td>
                        <td><span class="badge bg-<?= $prescription['status'] === 'active' ? 'success' : ($prescription['status'] === 'cancelled' ? 'danger' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($prescription['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
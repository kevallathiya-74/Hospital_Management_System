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

$patients = [];
if ($doctor_id > 0) {
    // Select patients who have appointments with this doctor
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, p.first_name, p.last_name, p.phone, p.email, p.blood_group, p.date_of_birth, p.status
        FROM patients p
        JOIN appointments a ON p.id = a.patient_id
        WHERE a.doctor_id = ?
        ORDER BY p.last_name, p.first_name
    ");
    $stmt->execute([$doctor_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-users"></i> My Patients</h2>

    <?php if (empty($patients)): ?>
        <div class="alert alert-info" role="alert">
            No patients found associated with your appointments.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Blood Group</th>
                        <th>Age</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['id']) ?></td>
                        <td><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></td>
                        <td>
                            <?= htmlspecialchars($patient['phone']) ?><br>
                            <small class="text-muted"><?= htmlspecialchars($patient['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($patient['blood_group'] ?: 'N/A') ?></td>
                        <td>
                            <?php
                            $dob = new DateTime($patient['date_of_birth']);
                            $now = new DateTime();
                            $age = $now->diff($dob)->y;
                            echo $age . ' years';
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $patient['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?= ucfirst($patient['status']); ?>
                            </span>
                        </td>
                        <td>
                            <!-- Add view/edit patient details links if needed -->
                            <a href="#" class="btn btn-sm btn-outline-info disabled" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
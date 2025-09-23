<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

requireLogin();
if (!hasRole('patient')) {
    header('Location: ../public/dashboard.php'); // Redirect to public dashboard if not patient
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get patient record
$stmt = $pdo->prepare("SELECT p.*, u.username, u.email as user_email, u.status as user_status FROM patients p LEFT JOIN users u ON p.user_id = u.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-user"></i> My Patient Details
            </h1>
        </div>
    </div>
    <?php if ($patient): ?>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="profile.php" class="btn btn-primary w-100 mb-2"><i class="fas fa-id-card"></i> View Profile</a>
                    <a href="appointments.php" class="btn btn-success w-100 mb-2"><i class="fas fa-calendar-check"></i> My Appointments</a>
                    <a href="reports.php" class="btn btn-info w-100 mb-2"><i class="fas fa-file-medical"></i> My Reports</a>
                    <a href="edit_profile.php" class="btn btn-warning w-100 mb-2"><i class="fas fa-edit"></i> Edit Profile</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user"></i> Patient Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Username</th><td><?= htmlspecialchars($patient['username']) ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($patient['user_email']) ?></td></tr>
                        <tr><th>Name</th><td><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></td></tr>
                        <tr><th>Date of Birth</th><td><?= htmlspecialchars($patient['date_of_birth']) ?></td></tr>
                        <tr><th>Gender</th><td><?= htmlspecialchars($patient['gender']) ?></td></tr>
                        <tr><th>Phone</th><td><?= htmlspecialchars($patient['phone']) ?></td></tr>
                        <tr><th>Address</th><td><?= htmlspecialchars($patient['address']) ?></td></tr>
                        <tr><th>Blood Group</th><td><?= htmlspecialchars($patient['blood_group']) ?></td></tr>
                        <tr><th>Emergency Contact</th><td><?= htmlspecialchars($patient['emergency_contact_name']) ?> (<?= htmlspecialchars($patient['emergency_contact']) ?>)</td></tr>
                        <tr><th>Medical History</th><td><?= htmlspecialchars($patient['medical_history']) ?></td></tr>
                        <tr><th>Allergies</th><td><?= htmlspecialchars($patient['allergies']) ?></td></tr>
                        <tr><th>Status</th><td><span class="badge bg-<?= $patient['user_status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($patient['user_status']) ?></span></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">Patient record not found.</div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
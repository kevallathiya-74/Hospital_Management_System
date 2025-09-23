<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
if (!hasRole('patient')) {
    header('Location: ../public/dashboard.php');
    exit;
}
$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.username, u.email, p.* FROM users u JOIN patients p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<div class="container">
    <h2>My Profile</h2>
    <?php if ($profile): ?>
    <table class="table table-bordered">
        <tr><th>Username</th><td><?= htmlspecialchars($profile['username']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($profile['email']) ?></td></tr>
        <tr><th>Name</th><td><?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></td></tr>
        <tr><th>Date of Birth</th><td><?= htmlspecialchars($profile['date_of_birth']) ?></td></tr>
        <tr><th>Gender</th><td><?= htmlspecialchars($profile['gender']) ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($profile['phone']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($profile['address']) ?></td></tr>
        <tr><th>Blood Group</th><td><?= htmlspecialchars($profile['blood_group']) ?></td></tr>
        <tr><th>Emergency Contact</th><td><?= htmlspecialchars($profile['emergency_contact_name']) ?> (<?= htmlspecialchars($profile['emergency_contact']) ?>)</td></tr>
        <tr><th>Medical History</th><td><?= htmlspecialchars($profile['medical_history']) ?></td></tr>
        <tr><th>Allergies</th><td><?= htmlspecialchars($profile['allergies']) ?></td></tr>
    </table>
    <?php else: ?>
    <div class="alert alert-danger">Profile not found.</div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
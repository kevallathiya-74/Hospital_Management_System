<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();
if (!hasRole('admin')) {
    header('Location: ../public/dashboard.php');
    exit;
}
$pdo = getDBConnection();
$sql = "SELECT b.id, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, b.appointment_id, b.room_id, b.bill_date, b.due_date, b.consultation_fee, b.room_charges, b.medicine_charges, b.other_charges, b.total_amount, b.paid_amount, b.status, b.payment_method
        FROM bills b
        JOIN patients p ON b.patient_id = p.id
        ORDER BY b.bill_date DESC, b.id DESC";
$stmt = $pdo->query($sql);
$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<div class="container">
    <h2>Billing Management</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Appointment</th>
                <th>Room</th>
                <th>Bill Date</th>
                <th>Due Date</th>
                <th>Consultation Fee</th>
                <th>Room Charges</th>
                <th>Medicine Charges</th>
                <th>Other Charges</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Status</th>
                <th>Payment Method</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bills)): ?>
                <tr><td colspan="14" class="text-center">No bills found.</td></tr>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                <tr>
                    <td><?= htmlspecialchars($bill['id']) ?></td>
                    <td><?= htmlspecialchars($bill['patient_name']) ?></td>
                    <td><?= $bill['appointment_id'] ? htmlspecialchars($bill['appointment_id']) : '-' ?></td>
                    <td><?= $bill['room_id'] ? htmlspecialchars($bill['room_id']) : '-' ?></td>
                    <td><?= htmlspecialchars($bill['bill_date']) ?></td>
                    <td><?= htmlspecialchars($bill['due_date']) ?></td>
                    <td><?= htmlspecialchars($bill['consultation_fee']) ?></td>
                    <td><?= htmlspecialchars($bill['room_charges']) ?></td>
                    <td><?= htmlspecialchars($bill['medicine_charges']) ?></td>
                    <td><?= htmlspecialchars($bill['other_charges']) ?></td>
                    <td><strong><?= htmlspecialchars($bill['total_amount']) ?></strong></td>
                    <td><?= htmlspecialchars($bill['paid_amount']) ?></td>
                    <td><span class="badge bg-<?= $bill['status'] === 'paid' ? 'success' : ($bill['status'] === 'pending' ? 'warning' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($bill['status']) ?></span></td>
                    <td><?= $bill['payment_method'] ? htmlspecialchars($bill['payment_method']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
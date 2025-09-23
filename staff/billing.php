<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('staff'); // Ensure only staff can access

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
    <h2><i class="fas fa-file-invoice-dollar"></i> Billing Management</h2>
    <p>This page allows staff to view and manage billing records.</p>

    <a href="#" class="btn btn-success mb-3 disabled" title="Functionality to add/edit bills would be implemented here">Add New Bill (Demo)</a>

    <?php if (empty($bills)): ?>
        <div class="alert alert-info" role="alert">
            No bills found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Appointment</th>
                        <th>Room</th>
                        <th>Bill Date</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?= htmlspecialchars($bill['id']) ?></td>
                        <td><?= htmlspecialchars($bill['patient_name']) ?></td>
                        <td><?= $bill['appointment_id'] ? htmlspecialchars($bill['appointment_id']) : '-' ?></td>
                        <td><?= $bill['room_id'] ? htmlspecialchars($bill['room_id']) : '-' ?></td>
                        <td><?= htmlspecialchars($bill['bill_date']) ?></td>
                        <td><?= htmlspecialchars($bill['due_date']) ?></td>
                        <td><strong>₹<?= number_format($bill['total_amount'], 2) ?></strong></td>
                        <td>₹<?= number_format($bill['paid_amount'], 2) ?></td>
                        <td><span class="badge bg-<?= $bill['status'] === 'paid' ? 'success' : ($bill['status'] === 'pending' ? 'warning' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($bill['status']) ?></span></td>
                        <td><?= $bill['payment_method'] ? htmlspecialchars($bill['payment_method']) : '-' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary disabled" title="Edit Bill"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-info disabled" title="View Details"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
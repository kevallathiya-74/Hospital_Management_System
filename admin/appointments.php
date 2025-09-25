<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/appointment_handler.php'; // Include the handler

requireRole('admin');
$pdo = getDBConnection();
$message = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);

    if ($action === 'approve') {
        if (approveAppointment($pdo, $appointment_id)) {
            $message = '<div class="alert alert-success">Appointment approved successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to approve appointment.</div>';
        }
    } elseif ($action === 'reject') {
        $rejection_reason = trim($_POST['rejection_reason'] ?? '');
        if (rejectAppointment($pdo, $appointment_id, $rejection_reason)) {
            $message = '<div class="alert alert-success">Appointment rejected successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to reject appointment. Reason is required.</div>';
        }
    }
}

$sql = "SELECT a.id, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, d.specialization, a.appointment_date, a.appointment_time, a.status, a.reason, a.rejection_reason
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC";
$stmt = $pdo->query($sql);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>
<div class="container">
    <h2>Appointment Management</h2>

    <?php echo $message; ?>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Specialization</th>
                <th>Date</th>
                <th>Time</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="9" class="text-center">No appointments found.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= htmlspecialchars($appt['id']) ?></td>
                    <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                    <td><?= htmlspecialchars($appt['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($appt['specialization']) ?></td>
                    <td><?= htmlspecialchars($appt['appointment_date']) ?></td>
                    <td><?= htmlspecialchars($appt['appointment_time']) ?></td>
                    <td><?= htmlspecialchars($appt['reason']) ?></td>
                    <td>
                        <span class="badge bg-<?= $appt['status'] === 'scheduled' || $appt['status'] === 'pending' ? 'warning' : ($appt['status'] === 'approved' || $appt['status'] === 'completed' ? 'success' : 'danger') ?> text-uppercase">
                            <?= htmlspecialchars($appt['status']) ?>
                        </span>
                        <?php if ($appt['status'] === 'rejected' && !empty($appt['rejection_reason'])): ?>
                            <i class="fas fa-info-circle text-danger ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Reason: <?= htmlspecialchars($appt['rejection_reason']) ?>"></i>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($appt['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success" onclick="approveAppointment(<?= $appt['id'] ?>)">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showRejectModal(<?= $appt['id'] ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-secondary disabled">No Actions</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Reject Appointment Modal -->
<div class="modal fade" id="rejectAppointmentModal" tabindex="-1" aria-labelledby="rejectAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectAppointmentModalLabel">Reject Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="appointment_id" id="reject_appointment_id">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="approveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="appointment_id" id="approve_appointment_id">
</form>

<script>
    function approveAppointment(appointmentId) {
        if (confirm('Are you sure you want to approve this appointment?')) {
            document.getElementById('approve_appointment_id').value = appointmentId;
            document.getElementById('approveForm').submit();
        }
    }

    function showRejectModal(appointmentId) {
        document.getElementById('reject_appointment_id').value = appointmentId;
        var rejectModal = new bootstrap.Modal(document.getElementById('rejectAppointmentModal'));
        rejectModal.show();
    }

    // Initialize tooltips (if not already done by script.js)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/appointment_handler.php'; // Include the handler

requireRole('doctor'); // Ensure only doctors can access

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Get the doctor's ID from the doctors table using the user_id
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor ? $doctor['id'] : 0;

if ($doctor_id === 0) {
    $message = '<div class="alert alert-danger">Doctor profile not found. Cannot manage appointments.</div>';
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor_id > 0) {
    $action = $_POST['action'] ?? '';
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);

    // Verify the appointment belongs to this doctor
    $check_stmt = $pdo->prepare("SELECT doctor_id FROM appointments WHERE id = ?");
    $check_stmt->execute([$appointment_id]);
    if ($check_stmt->fetchColumn() != $doctor_id) {
        $message = '<div class="alert alert-danger">Unauthorized action.</div>';
    } else {
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
}

$appointments = [];
if ($doctor_id > 0) {
    $stmt = $pdo->prepare("
        SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-calendar-check"></i> My Appointments</h2>

    <?php echo $message; ?>

    <?php if (empty($appointments)): ?>
        <div class="alert alert-info" role="alert">
            No appointments found for you.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?= htmlspecialchars($appt['id']) ?></td>
                        <td><?= htmlspecialchars($appt['patient_name']) ?></td>
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
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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
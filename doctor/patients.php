<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/appointment_handler.php'; // Include the handler

requireRole('doctor');

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Get the doctor's ID
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
$doctor_id = $doctor ? $doctor['id'] : 0;

if ($doctor_id === 0) {
    $message = '<div class="alert alert-danger">Doctor profile not found.</div>';
    $patients = [];
} else {
    // Get all active patients
    $patients_stmt = $pdo->query("SELECT id, first_name, last_name, phone, email FROM patients WHERE status = 'active' ORDER BY last_name, first_name");
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle actions (approve, reject, complete, prescription, bill)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor_id > 0) {
        $action = $_POST['action'] ?? '';
        $appointment_id = (int)($_POST['appointment_id'] ?? 0);

        // Verify appointment belongs to this doctor
        $check_stmt = $pdo->prepare("SELECT patient_id, status FROM appointments WHERE id = ? AND doctor_id = ?");
        $check_stmt->execute([$appointment_id, $doctor_id]);
        $appt_check = $check_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$appt_check) {
            $message = '<div class="alert alert-danger">Invalid appointment.</div>';
        } else {
            $patient_id = $appt_check['patient_id'];
            $current_status = $appt_check['status'];

            switch ($action) {
                case 'approve':
                    if (approveAppointment($pdo, $appointment_id)) {
                        $message = '<div class="alert alert-success">Appointment approved!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Failed to approve.</div>';
                    }
                    break;

                case 'reject':
                    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
                    if (empty($rejection_reason)) {
                        $message = '<div class="alert alert-danger">Rejection reason is required.</div>';
                    } elseif (rejectAppointment($pdo, $appointment_id, $rejection_reason)) {
                        $message = '<div class="alert alert-success">Appointment rejected with reason.</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Failed to reject.</div>';
                    }
                    break;

                case 'complete':
                    if ($current_status !== 'approved') {
                        $message = '<div class="alert alert-danger">Can only complete approved appointments.</div>';
                    } elseif (completeAppointment($pdo, $appointment_id, $doctor_id)) {
                        $message = '<div class="alert alert-success">Appointment marked as completed!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Failed to complete.</div>';
                    }
                    break;

                case 'add_prescription':
                    $medication = trim($_POST['medication'] ?? '');
                    $dosage = trim($_POST['dosage'] ?? '');
                    $instructions = trim($_POST['instructions'] ?? '');
                    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
                    if (empty($medication)) {
                        $message = '<div class="alert alert-danger">Medication is required.</div>';
                    } elseif (addPrescription($pdo, $doctor_id, $patient_id, $appointment_id, $medication, $dosage, $instructions, $issue_date)) {
                        $message = '<div class="alert alert-success">Prescription added successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Failed to add prescription.</div>';
                    }
                    break;

                case 'generate_bill':
                    $other_charges = (float)($_POST['other_charges'] ?? 0);
                    $payment_method = $_POST['payment_method'] ?? '';
                    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'));
                    $consultation_fee = getDoctorConsultationFee($pdo, $doctor_id);
                    if (empty($payment_method)) {
                        $message = '<div class="alert alert-danger">Payment method is required.</div>';
                    } elseif (generateBill($pdo, $patient_id, $appointment_id, $consultation_fee, $other_charges, $payment_method, $due_date)) {
                        $message = '<div class="alert alert-success">Bill generated successfully! Total: ₹' . number_format($consultation_fee + $other_charges, 2) . '</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Failed to generate bill.</div>';
                    }
                    break;
            }
        }
    }

    // Fetch appointments for each patient (dynamic per doctor)
    foreach ($patients as &$patient) {
        $patient['appointments'] = getPatientAppointments($pdo, $doctor_id, $patient['id']);
        // Get prescription and bill for each appointment
        foreach ($patient['appointments'] as &$appt) {
            $appt['prescription'] = getPrescriptionByAppointment($pdo, $appt['id']);
            $appt['bill'] = getBillByAppointment($pdo, $appt['id']);
        }
    }
    unset($patient); // Unset reference
}

include '../includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-users"></i> Patients Management</h2>
    <p>View patients and manage their appointments, prescriptions, and bills dynamically.</p>

    <?php if (isset($message)): ?>
        <?= $message ?>
    <?php endif; ?>

    <?php if (empty($patients)): ?>
        <div class="alert alert-info">No active patients found.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Appointments & Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></td>
                        <td><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($patient['email'] ?? 'N/A') ?></td>
                        <td>
                            <?php if (empty($patient['appointments'])): ?>
                                <span class="text-muted">No appointments with you.</span>
                            <?php else: ?>
                                <div class="accordion" id="accordion<?= $patient['id'] ?>">
                                    <?php foreach ($patient['appointments'] as $index => $appt): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $patient['id'] . $appt['id'] ?>">
                                                Appt ID: <?= $appt['id'] ?> | Date: <?= htmlspecialchars($appt['appointment_date']) ?> | Time: <?= htmlspecialchars($appt['appointment_time']) ?> | Reason: <?= htmlspecialchars(substr($appt['reason'], 0, 50)) . (strlen($appt['reason']) > 50 ? '...' : '') ?>
                                                <span class="badge bg-<?= $appt['status'] === 'pending' ? 'warning' : ($appt['status'] === 'approved' || $appt['status'] === 'completed' ? 'success' : 'danger') ?> ms-2"><?= ucfirst($appt['status']) ?></span>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $patient['id'] . $appt['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#accordion<?= $patient['id'] ?>">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <strong>Full Reason:</strong> <?= htmlspecialchars($appt['reason']) ?><br>
                                                        <strong>Status:</strong> <?= ucfirst($appt['status']) ?>
                                                        <?php if ($appt['status'] === 'rejected' && !empty($appt['rejection_reason'])): ?>
                                                            <br><small class="text-danger">Rejection Reason: <?= htmlspecialchars($appt['rejection_reason']) ?></small>
                                                        <?php endif; ?>
                                                        <br><strong>Prescription:</strong>
                                                        <?php if (!empty($appt['prescription'])): ?>
                                                            <?php foreach ($appt['prescription'] as $pres): ?>
                                                                <div class="border p-2 mb-1 bg-light">
                                                                    <strong><?= htmlspecialchars($pres['medication']) ?></strong><br>
                                                                    Dosage: <?= htmlspecialchars($pres['dosage']) ?><br>
                                                                    Instructions: <?= htmlspecialchars($pres['instructions']) ?><br>
                                                                    Issued: <?= htmlspecialchars($pres['issue_date']) ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No prescription added yet.</span>
                                                        <?php endif; ?>
                                                        <br><strong>Bill:</strong>
                                                        <?php if ($appt['bill']): ?>
                                                            <div class="border p-2 bg-light">
                                                                <strong>Total: ₹<?= number_format($appt['bill']['total_amount'], 2) ?></strong><br>
                                                                Consultation Fee: ₹<?= number_format($appt['bill']['consultation_fee'], 2) ?><br>
                                                                Other Charges: ₹<?= number_format($appt['bill']['other_charges'], 2) ?><br>
                                                                Status: <span class="badge bg-<?= $appt['bill']['status'] === 'paid' ? 'success' : ($appt['bill']['status'] === 'pending' ? 'warning' : 'danger') ?>"><?= ucfirst($appt['bill']['status']) ?></span><br>
                                                                Payment Method: <?= htmlspecialchars($appt['bill']['payment_method']) ?><br>
                                                                Due Date: <?= htmlspecialchars($appt['bill']['due_date']) ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No bill generated yet.</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Actions:</strong><br><br>
                                                        <?php if ($appt['status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-success me-1 mb-1" onclick="approveAppt(<?= $appt['id'] ?>)">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <button class="btn btn-sm btn-danger me-1 mb-1" onclick="showRejectModal(<?= $appt['id'] ?>)">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        <?php elseif ($appt['status'] === 'approved'): ?>
                                                            <button class="btn btn-sm btn-info me-1 mb-1" onclick="completeAppt(<?= $appt['id'] ?>)">
                                                                <i class="fas fa-check-double"></i> Mark as Completed
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-primary me-1 mb-1" onclick="showPrescriptionModal(<?= $appt['id'] ?>, <?= $patient['id'] ?>)">
                                                                <i class="fas fa-prescription"></i> Add Prescription
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-success mb-1" onclick="showBillModal(<?= $appt['id'] ?>, <?= $patient['id'] ?>, <?= getDoctorConsultationFee($pdo, $doctor_id) ?>)">
                                                                <i class="fas fa-file-invoice-dollar"></i> Generate Bill
                                                            </button>
                                                        <?php elseif ($appt['status'] === 'completed'): ?>
                                                            <?php if (empty($appt['prescription'])): ?>
                                                                <button class="btn btn-sm btn-outline-primary me-1 mb-1" onclick="showPrescriptionModal(<?= $appt['id'] ?>, <?= $patient['id'] ?>)">
                                                                    <i class="fas fa-prescription"></i> Add Prescription
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-success"><i class="fas fa-check"></i> Prescription Added</span><br>
                                                            <?php endif; ?>
                                                            <?php if (!$appt['bill']): ?>
                                                                <button class="btn btn-sm btn-outline-success mb-1" onclick="showBillModal(<?= $appt['id'] ?>, <?= $patient['id'] ?>, <?= getDoctorConsultationFee($pdo, $doctor_id) ?>)">
                                                                    <i class="fas fa-file-invoice-dollar"></i> Generate Bill
                                                                </button>
                                                            <?php else: ?>
                                                                <span class="text-success"><i class="fas fa-check"></i> Bill Generated</span>
                                                            <?php endif; ?>
                                                        <?php elseif ($appt['status'] === 'rejected'): ?>
                                                            <span class="text-danger"><i class="fas fa-ban"></i> Rejected - No Further Actions</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
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
                        <label for="rejection_reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Enter a valid reason (e.g., scheduling conflict, patient no-show)"></textarea>
                        <div class="form-text">Reason is required and will be shown to the patient.</div>
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

<!-- Complete Appointment Modal -->
<div class="modal fade" id="completeAppointmentModal" tabindex="-1" aria-labelledby="completeAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeAppointmentModalLabel">Mark as Completed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="complete">
                    <input type="hidden" name="appointment_id" id="complete_appointment_id">
                    <p>Are you sure? This will mark the appointment as completed and enable prescription/bill generation.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Mark Completed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Prescription Modal -->
<div class="modal fade" id="prescriptionModal" tabindex="-1" aria-labelledby="prescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="prescriptionModalLabel">Add Prescription</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_prescription">
                    <input type="hidden" name="appointment_id" id="prescription_appointment_id">
                    <input type="hidden" name="patient_id" id="prescription_patient_id">
                    <div class="mb-3">
                        <label for="medication" class="form-label">Medication <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="medication" name="medication" required placeholder="e.g., Paracetamol">
                    </div>
                    <div class="mb-3">
                        <label for="dosage" class="form-label">Dosage</label>
                        <input type="text" class="form-control" id="dosage" name="dosage" placeholder="e.g., 500mg twice daily">
                    </div>
                    <div class="mb-3">
                        <label for="instructions" class="form-label">Instructions</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="2" placeholder="e.g., Take after meals, avoid alcohol"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="issue_date" class="form-label">Issue Date</label>
                        <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-text">Medication is required. Dosage and instructions are optional but recommended.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Bill Modal -->
<div class="modal fade" id="billModal" tabindex="-1" aria-labelledby="billModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="billModalLabel">Generate Bill</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="generate_bill">
                    <input type="hidden" name="appointment_id" id="bill_appointment_id">
                    <input type="hidden" name="patient_id" id="bill_patient_id">
                    <div class="mb-3">
                        <label for="consultation_fee" class="form-label">Consultation Fee (₹)</label>
                        <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" step="0.01" readonly placeholder="Auto-calculated from doctor profile">
                        <div class="form-text">This is your standard consultation fee from your profile.</div>
                    </div>
                    <div class="mb-3">
                        <label for="other_charges" class="form-label">Other Charges (₹)</label>
                        <input type="number" class="form-control" id="other_charges" name="other_charges" step="0.01" value="0" min="0" placeholder="e.g., Tests, additional services">
                    </div>
                    <div class="mb-3">
                        <label for="total_amount_preview" class="form-label">Total Amount (₹) <span id="total_amount_preview" class="badge bg-info">0.00</span></label>
                        <div class="form-text">Auto-calculated: Consultation Fee + Other Charges.</div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Online Pay">Online Pay (Card/UPI)</option>
                            <option value="Offline Pay">Offline Pay (Cash/Card at Counter)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" min="<?= date('Y-m-d') ?>">
                        <div class="form-text">Bill starts as 'pending'. Patient can pay online or offline based on method.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Generate Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms for Approve and Complete (Submitted via JS) -->
<form id="approveForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="appointment_id" id="approve_appointment_id">
</form>

<form id="completeForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="complete">
    <input type="hidden" name="appointment_id" id="complete_appointment_id">
</form>

<script>
    // Approve Appointment (Simple confirm and submit hidden form)
    function approveAppt(appointmentId) {
        if (confirm('Are you sure you want to approve this appointment?')) {
            document.getElementById('approve_appointment_id').value = appointmentId;
            document.getElementById('approveForm').submit();
        }
    }

    // Show Reject Modal and populate ID
    function showRejectModal(appointmentId) {
        document.getElementById('reject_appointment_id').value = appointmentId;
        document.getElementById('rejection_reason').value = ''; // Clear previous input
        var rejectModal = new bootstrap.Modal(document.getElementById('rejectAppointmentModal'));
        rejectModal.show();
    }

    // Complete Appointment (Show modal for confirmation)
    function completeAppt(appointmentId) {
        document.getElementById('complete_appointment_id').value = appointmentId;
        var completeModal = new bootstrap.Modal(document.getElementById('completeAppointmentModal'));
        completeModal.show();
    }

    // Show Prescription Modal and populate IDs
    function showPrescriptionModal(appointmentId, patientId) {
        document.getElementById('prescription_appointment_id').value = appointmentId;
        document.getElementById('prescription_patient_id').value = patientId;
        document.getElementById('medication').value = '';
        document.getElementById('dosage').value = '';
        document.getElementById('instructions').value = '';
        document.getElementById('issue_date').value = '<?= date('Y-m-d') ?>';
        var prescriptionModal = new bootstrap.Modal(document.getElementById('prescriptionModal'));
        prescriptionModal.show();
    }

    // Show Bill Modal, populate IDs and dynamic consultation fee
    function showBillModal(appointmentId, patientId, consultationFee) {
        document.getElementById('bill_appointment_id').value = appointmentId;
        document.getElementById('bill_patient_id').value = patientId;
        document.getElementById('consultation_fee').value = consultationFee;
        document.getElementById('other_charges').value = '0';
        document.getElementById('payment_method').value = '';
        document.getElementById('due_date').value = '<?= date('Y-m-d', strtotime('+7 days')) ?>';
        updateTotalAmount(); // Initial calculation
        var billModal = new bootstrap.Modal(document.getElementById('billModal'));
        billModal.show();
    }

    // Update Total Amount Preview in Bill Modal (Dynamic JS Calculation)
    function updateTotalAmount() {
        const consultationFee = parseFloat(document.getElementById('consultation_fee').value) || 0;
        const otherCharges = parseFloat(document.getElementById('other_charges').value) || 0;
        const total = consultationFee + otherCharges;
        document.getElementById('total_amount_preview').textContent = total.toFixed(2);
    }

    // Event Listeners for Bill Modal (Real-time total update)
    document.addEventListener('DOMContentLoaded', function() {
        const otherChargesInput = document.getElementById('other_charges');
        if (otherChargesInput) {
            otherChargesInput.addEventListener('input', updateTotalAmount);
        }

        // Initialize tooltips for rejection reasons (if shown in accordion)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Form validation for modals (client-side required check)
        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill all required fields.');
                }
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
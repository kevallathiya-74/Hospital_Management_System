<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('staff'); // Ensure only staff can access

$pdo = getDBConnection();
$message = '';

// Fetch all active patients and doctors for dropdowns
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query("SELECT id, first_name, last_name, specialization FROM doctors WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = (int)$_POST['patient_id'];
    $doctor_id = (int)$_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $reason]);
            $message = '<div class="alert alert-success">Appointment scheduled successfully!</div>';
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error scheduling appointment: ' . $e->getMessage() . '</div>';
        }
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-calendar-plus"></i> Schedule Appointment</h2>
    <p>Use this form to schedule a new appointment for a patient with a doctor.</p>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-header">
            <h5>Appointment Details</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                    <select class="form-control" id="patient_id" name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $patient): ?>
                            <option value="<?= htmlspecialchars($patient['id']) ?>">
                                <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                    <select class="form-control" id="doctor_id" name="doctor_id" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= htmlspecialchars($doctor['id']) ?>">
                                <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> (<?= htmlspecialchars($doctor['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="appointment_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="appointment_time" class="form-label">Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for Appointment</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-calendar-plus"></i> Schedule Appointment
                </button>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
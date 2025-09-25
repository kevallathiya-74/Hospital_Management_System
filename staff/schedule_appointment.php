<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/appointment_handler.php'; // Include the handler

requireRole('staff'); // Ensure only staff can access

$pdo = getDBConnection();
$message = '';

// Fetch all active patients and doctors for dropdowns
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query("SELECT id, first_name, last_name, specialization FROM doctors WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);

$selected_doctor_id = $_POST['doctor_id'] ?? '';
$doctor_schedule_html = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'fetch_schedule') {
        // This block handles AJAX-like request to fetch schedule
        // For simplicity, we'll re-render the page with schedule info
        // In a real AJAX scenario, this would return JSON
        if (!empty($selected_doctor_id)) {
            $doctor_schedules = getDoctorSchedule($pdo, $selected_doctor_id);
            if (!empty($doctor_schedules)) {
                $doctor_schedule_html .= '<p class="mt-2"><strong>Available Schedule:</strong></p>';
                $doctor_schedule_html .= '<ul class="list-group mb-3">';
                foreach ($doctor_schedules as $schedule) {
                    $doctor_schedule_html .= '<li class="list-group-item">';
                    $doctor_schedule_html .= htmlspecialchars($schedule['day_of_week']) . ': ';
                    $doctor_schedule_html .= date('h:i A', strtotime($schedule['start_time'])) . ' - ';
                    $doctor_schedule_html .= date('h:i A', strtotime($schedule['end_time']));
                    $doctor_schedule_html .= '</li>';
                }
                $doctor_schedule_html .= '</ul>';
            } else {
                $doctor_schedule_html = '<div class="alert alert-info mt-2">No schedule defined for this doctor.</div>';
            }
        }
    } else {
        // Handle actual appointment scheduling
        $patient_id = (int)$_POST['patient_id'];
        $doctor_id = (int)$_POST['doctor_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $reason = trim($_POST['reason']);

        if (empty($patient_id) || empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
            $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
        } else {
            try {
                // Basic check for doctor availability (can be enhanced with time slot validation)
                $day_of_week = date('l', strtotime($appointment_date)); // 'l' gives full day name
                $check_schedule_stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM doctor_schedules
                    WHERE doctor_id = ? AND day_of_week = ?
                    AND ? BETWEEN start_time AND end_time
                ");
                $check_schedule_stmt->execute([$doctor_id, $day_of_week, $appointment_time]);
                if ($check_schedule_stmt->fetchColumn() == 0) {
                    $message = '<div class="alert alert-danger">Doctor is not available at the selected date/time. Please check their schedule.</div>';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $reason]);
                    $message = '<div class="alert alert-success">Appointment scheduled successfully! It is now pending doctor approval.</div>';
                }
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Error scheduling appointment: ' . $e->getMessage() . '</div>';
            }
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
            <form method="POST" id="scheduleAppointmentForm">
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
                    <select class="form-control" id="doctor_id" name="doctor_id" required onchange="fetchDoctorSchedule()">
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?= htmlspecialchars($doctor['id']) ?>" <?= ($selected_doctor_id == $doctor['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> (<?= htmlspecialchars($doctor['specialization']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="doctor_schedule_display">
                        <?= $doctor_schedule_html ?>
                    </div>
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

<script>
    function fetchDoctorSchedule() {
        const doctorId = document.getElementById('doctor_id').value;
        if (doctorId) {
            // Create a temporary form to submit and get the schedule
            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = 'schedule_appointment.php'; // Submit to self
            tempForm.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'fetch_schedule';
            tempForm.appendChild(actionInput);

            const doctorIdInput = document.createElement('input');
            doctorIdInput.type = 'hidden';
            doctorIdInput.name = 'doctor_id';
            doctorIdInput.value = doctorId;
            tempForm.appendChild(doctorIdInput);

            document.body.appendChild(tempForm);
            tempForm.submit();
        } else {
            document.getElementById('doctor_schedule_display').innerHTML = '';
        }
    }

    // Call on page load if a doctor was already selected (e.g., after a failed form submission)
    document.addEventListener('DOMContentLoaded', function() {
        const selectedDoctor = document.getElementById('doctor_id').value;
        if (selectedDoctor) {
            // If there's already schedule HTML, don't re-fetch unless explicitly needed
            // This prevents infinite loops if the page reloads due to validation errors
            const scheduleDisplay = document.getElementById('doctor_schedule_display');
            if (scheduleDisplay.innerHTML.trim() === '') {
                 // fetchDoctorSchedule(); // Uncomment if you want to re-fetch on every page load with a selected doctor
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
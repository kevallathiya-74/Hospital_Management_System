<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/appointment_handler.php'; // Include the handler

requireRole('patient'); // Ensure only patients can access

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Get patient record ID from the patients table using the user_id
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$pat = $stmt->fetch(PDO::FETCH_ASSOC);
$patient_id = $pat ? $pat['id'] : 0;

if ($patient_id === 0) {
    $message = '<div class="alert alert-danger">Patient profile not found. Cannot schedule appointments.</div>';
}

// Fetch all active doctors for dropdown
$doctors = $pdo->query("SELECT id, first_name, last_name, specialization FROM doctors WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);

$selected_doctor_id = $_POST['doctor_id'] ?? '';
$doctor_schedule_html = '';

// Handle form submission for fetching doctor schedule (AJAX-like)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_schedule') {
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
}
// Handle actual appointment scheduling
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id > 0) {
    $doctor_id = (int)$_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
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
                // Clear form fields after successful submission
                $_POST = array();
                $selected_doctor_id = '';
                $doctor_schedule_html = '';
            }
        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Error scheduling appointment: ' . $e->getMessage() . '</div>';
        }
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-calendar-plus"></i> Schedule New Appointment</h2>
    <p>Select a doctor and an available time slot to book your appointment.</p>

    <?php echo $message; ?>

    <?php if ($patient_id === 0): ?>
        <div class="alert alert-danger">Your patient profile could not be found. Please contact support.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5>Appointment Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="scheduleAppointmentForm">
                    <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patient_id) ?>">

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
                            <input type="date" class="form-control" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['appointment_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="appointment_time" class="form-label">Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="appointment_time" name="appointment_time" required value="<?= htmlspecialchars($_POST['appointment_time'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Appointment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="Briefly describe your reason for the appointment"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-calendar-plus"></i> Schedule Appointment
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function fetchDoctorSchedule() {
        const doctorId = document.getElementById('doctor_id').value;
        if (doctorId) {
            // Use Fetch API for a more modern AJAX request
            fetch('schedule_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=fetch_schedule&doctor_id=' + doctorId
            })
            .then(response => response.text())
            .then(html => {
                // Extract only the schedule display part from the returned HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const scheduleDisplayContent = doc.getElementById('doctor_schedule_display').innerHTML;
                document.getElementById('doctor_schedule_display').innerHTML = scheduleDisplayContent;
            })
            .catch(error => {
                console.error('Error fetching doctor schedule:', error);
                document.getElementById('doctor_schedule_display').innerHTML = '<div class="alert alert-danger mt-2">Failed to load schedule.</div>';
            });
        } else {
            document.getElementById('doctor_schedule_display').innerHTML = '';
        }
    }

    // Call on page load if a doctor was already selected (e.g., after a failed form submission)
    document.addEventListener('DOMContentLoaded', function() {
        const selectedDoctor = document.getElementById('doctor_id').value;
        if (selectedDoctor) {
            // Only fetch if the schedule display is empty (prevents re-fetching if already rendered by PHP)
            if (document.getElementById('doctor_schedule_display').innerHTML.trim() === '') {
                fetchDoctorSchedule();
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>

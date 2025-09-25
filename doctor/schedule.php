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
    $message = '<div class="alert alert-danger">Doctor profile not found. Cannot manage schedule.</div>';
}

// Handle form submissions for adding/editing/deleting schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $doctor_id > 0) {
    $action = $_POST['action'] ?? '';
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $schedule_id = $_POST['schedule_id'] ?? null;

    if ($action === 'add' || $action === 'edit') {
        if (empty($day_of_week) || empty($start_time) || empty($end_time)) {
            $message = '<div class="alert alert-danger">Please fill all schedule fields.</div>';
        } elseif (strtotime($start_time) >= strtotime($end_time)) {
            $message = '<div class="alert alert-danger">Start time must be before end time.</div>';
        } else {
            if (updateDoctorSchedule($pdo, $doctor_id, $day_of_week, $start_time, $end_time, $schedule_id)) {
                $message = '<div class="alert alert-success">Schedule ' . ($action === 'add' ? 'added' : 'updated') . ' successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Error ' . ($action === 'add' ? 'adding' : 'updating') . ' schedule. It might already exist.</div>';
            }
        }
    } elseif ($action === 'delete') {
        if (deleteDoctorSchedule($pdo, $schedule_id, $doctor_id)) {
            $message = '<div class="alert alert-success">Schedule deleted successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error deleting schedule.</div>';
        }
    }
}

$doctor_schedules = ($doctor_id > 0) ? getDoctorSchedule($pdo, $doctor_id) : [];
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

include '../includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-clock"></i> My Availability Schedule</h2>
    <p>Manage your available days and time slots for appointments.</p>

    <?php echo $message; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Add/Edit Schedule Slot</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" id="schedule_action" value="add">
                <input type="hidden" name="schedule_id" id="schedule_id">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="day_of_week" class="form-label">Day of Week</label>
                        <select name="day_of_week" id="day_of_week" class="form-select" required>
                            <option value="">Select Day</option>
                            <?php foreach ($days_of_week as $day): ?>
                                <option value="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" id="schedule_submit_btn">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <h3>Current Schedule</h3>
    <?php if (empty($doctor_schedules)): ?>
        <div class="alert alert-info" role="alert">
            No schedule slots defined yet.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Day</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctor_schedules as $schedule): ?>
                    <tr>
                        <td><?= htmlspecialchars($schedule['day_of_week']) ?></td>
                        <td><?= date('h:i A', strtotime($schedule['start_time'])) ?></td>
                        <td><?= date('h:i A', strtotime($schedule['end_time'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editSchedule(<?= htmlspecialchars(json_encode($schedule)) ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSchedule(<?= $schedule['id'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Schedule Modal -->
<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-labelledby="deleteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteScheduleModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this schedule slot? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="schedule_id" id="delete_schedule_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editSchedule(schedule) {
        document.getElementById('schedule_action').value = 'edit';
        document.getElementById('schedule_id').value = schedule.id;
        document.getElementById('day_of_week').value = schedule.day_of_week;
        document.getElementById('start_time').value = schedule.start_time;
        document.getElementById('end_time').value = schedule.end_time;
        document.getElementById('schedule_submit_btn').innerHTML = '<i class="fas fa-save"></i> Update';
        document.getElementById('schedule_submit_btn').classList.remove('btn-primary');
        document.getElementById('schedule_submit_btn').classList.add('btn-success');
    }

    function deleteSchedule(scheduleId) {
        document.getElementById('delete_schedule_id').value = scheduleId;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteScheduleModal'));
        deleteModal.show();
    }

    // Reset form when modal is closed or new add is intended
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.card-body form');
        form.addEventListener('reset', function() {
            document.getElementById('schedule_action').value = 'add';
            document.getElementById('schedule_id').value = '';
            document.getElementById('schedule_submit_btn').innerHTML = '<i class="fas fa-plus"></i> Add';
            document.getElementById('schedule_submit_btn').classList.remove('btn-success');
            document.getElementById('schedule_submit_btn').classList.add('btn-primary');
        });
    });
</script>

<?php include '../includes/footer.php'; ?>

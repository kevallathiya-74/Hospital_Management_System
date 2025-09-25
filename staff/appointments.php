<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
require_once '../includes/appointment_handler.php'; // Include the handler

requireRole('staff');

$pdo = getDBConnection();
$message = '';
$selected_day = $_GET['day'] ?? '';

$available_doctors = getAvailableDoctors($pdo, $selected_day);

$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

include '../includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-user-md"></i> Available Doctors</h2>
    <p>View doctors and their available schedules to assist with appointment scheduling.</p>

    <?php echo $message; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5>Filter by Day</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="available_doctors.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="day_filter" class="form-label">Select Day:</label>
                        <select name="day" id="day_filter" class="form-select">
                            <option value="">All Days</option>
                            <?php foreach ($days_of_week as $day): ?>
                                <option value="<?= htmlspecialchars($day) ?>" <?= ($selected_day === $day) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($day) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($available_doctors)): ?>
        <div class="alert alert-info" role="alert">
            No doctors found with available schedules for <?= $selected_day ? htmlspecialchars($selected_day) : 'any day' ?>.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Doctor Name</th>
                        <th>Specialization</th>
                        <th>Available Schedule</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_doctors as $doctor): ?>
                    <tr>
                        <td><?= htmlspecialchars($doctor['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($doctor['specialization']) ?></td>
                        <td><?= $doctor['schedule'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

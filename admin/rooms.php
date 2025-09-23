<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

requireRole('admin');

$pdo = getDBConnection();
$message = '';

// Function to get patient name by ID (centralized for efficiency)
function getPatientNameById($pdo, $patient_id) {
    if (!$patient_id) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS patient_name FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    return $patient ? $patient['patient_name'] : 'Unknown Patient';
}

// Handle form submissions for Add, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $room_number = trim($_POST['room_number'] ?? '');
            $room_type = trim($_POST['room_type'] ?? '');
            $floor = (int)($_POST['floor'] ?? 0);
            $capacity = (int)($_POST['capacity'] ?? 1);
            $rate_per_day = (float)($_POST['rate_per_day'] ?? 0);
            $status = $_POST['status'] ?? 'available';

            // Validation
            $errors = [];
            if ($room_number === '') { $errors[] = "Room number is required."; }
            if ($room_type === '') { $errors[] = "Room type is required."; }
            if ($floor <= 0) { $errors[] = "Floor must be a positive integer."; }
            if ($capacity <= 0) { $errors[] = "Capacity must be a positive integer."; }
            if ($rate_per_day < 0) { $errors[] = "Rate per day cannot be negative."; }
            if (!in_array($status, ['available', 'occupied', 'maintenance'])) { $errors[] = "Invalid status selected."; }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ?");
            $stmt->execute([$room_number]);
            if ($stmt->fetchColumn() > 0) { $errors[] = "Room number already exists."; }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, floor, capacity, rate_per_day, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$room_number, $room_type, $floor, $capacity, $rate_per_day, $status]);
                    $message = '<div class="alert alert-success">Room added successfully!</div>';
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Error adding room: ' . $e->getMessage() . '</div>';
                }
            } else {
                $message = '<div class="alert alert-danger"><ul>';
                foreach ($errors as $error) {
                    $message .= '<li>' . htmlspecialchars($error) . '</li>';
                }
                $message .= '</ul></div>';
            }
        } elseif ($_POST['action'] === 'edit') {
            $room_id = (int)$_POST['room_id'];
            $room_number = trim($_POST['room_number'] ?? '');
            $room_type = trim($_POST['room_type'] ?? '');
            $floor = (int)($_POST['floor'] ?? 0);
            $capacity = (int)($_POST['capacity'] ?? 1);
            $rate_per_day = (float)($_POST['rate_per_day'] ?? 0);
            $status = $_POST['status'] ?? 'available';
            $assigned_patient_id = !empty($_POST['assigned_patient_id']) ? (int)$_POST['assigned_patient_id'] : null;

            // Validation
            $errors = [];
            if ($room_number === '') { $errors[] = "Room number is required."; }
            if ($room_type === '') { $errors[] = "Room type is required."; }
            if ($floor <= 0) { $errors[] = "Floor must be a positive integer."; }
            if ($capacity <= 0) { $errors[] = "Capacity must be a positive integer."; }
            if ($rate_per_day < 0) { $errors[] = "Rate per day cannot be negative."; }
            if (!in_array($status, ['available', 'occupied', 'maintenance'])) { $errors[] = "Invalid status selected."; }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND id != ?");
            $stmt->execute([$room_number, $room_id]);
            if ($stmt->fetchColumn() > 0) { $errors[] = "Room number already exists."; }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type = ?, floor = ?, capacity = ?, rate_per_day = ?, status = ?, assigned_patient_id = ? WHERE id = ?");
                    $stmt->execute([$room_number, $room_type, $floor, $capacity, $rate_per_day, $status, $assigned_patient_id, $room_id]);
                    $message = '<div class="alert alert-success">Room updated successfully!</div>';
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Error updating room: ' . $e->getMessage() . '</div>';
                }
            } else {
                $message = '<div class="alert alert-danger"><ul>';
                foreach ($errors as $error) {
                    $message .= '<li>' . htmlspecialchars($error) . '</li>';
                }
                $message .= '</ul></div>';
            }
        } elseif ($_POST['action'] === 'delete') {
            $room_id = (int)$_POST['room_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->execute([$room_id]);
                $message = '<div class="alert alert-success">Room deleted successfully!</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Error deleting room: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Get all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY floor, room_number");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all active patients for the dropdown in edit modal
$patients = $pdo->query("SELECT id, first_name, last_name FROM patients WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-bed"></i> Room Management
            </h1>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
            <i class="fas fa-plus"></i> Add New Room
        </button>
    </div>

    <?php if (empty($rooms)): ?>
        <div class="alert alert-info" role="alert">
            No rooms found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Room Number</th>
                        <th>Type</th>
                        <th>Floor</th>
                        <th>Capacity</th>
                        <th>Rate/Day</th>
                        <th>Status</th>
                        <th>Assigned Patient</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['id']) ?></td>
                        <td><?= htmlspecialchars($room['room_number']) ?></td>
                        <td><?= htmlspecialchars($room['room_type']) ?></td>
                        <td><?= htmlspecialchars($room['floor']) ?></td>
                        <td><?= htmlspecialchars($room['capacity']) ?></td>
                        <td>₹<?= number_format($room['rate_per_day'], 2) ?></td>
                        <td><span class="badge bg-<?= $room['status'] === 'available' ? 'success' : ($room['status'] === 'occupied' ? 'warning' : 'secondary') ?> text-uppercase"><?= htmlspecialchars($room['status']) ?></span></td>
                        <td>
                            <?php
                            if ($room['status'] === 'occupied' && $room['assigned_patient_id']) {
                                echo htmlspecialchars(getPatientNameById($pdo, $room['assigned_patient_id']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(<?php echo $room['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoomModalLabel">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" name="room_number" id="room_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="room_type" class="form-label">Room Type</label>
                        <input type="text" class="form-control" id="room_type" name="room_type" required>
                    </div>
                    <div class="mb-3">
                        <label for="floor" class="form-label">Floor</label>
                        <input type="number" name="floor" id="floor" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input type="number" name="capacity" id="capacity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="rate_per_day" class="form-label">Rate Per Day (₹)</label>
                        <input type="number" step="0.01" name="rate_per_day" id="rate_per_day" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    <div class="mb-3">
                        <label for="edit_room_number" class="form-label">Room Number</label>
                        <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_room_type" class="form-label">Room Type</label>
                        <input type="text" class="form-control" id="edit_room_type" name="room_type" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_floor" class="form-label">Floor</label>
                        <input type="number" name="floor" id="edit_floor" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacity</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_rate_per_day" class="form-label">Rate Per Day (₹)</label>
                        <input type="number" step="0.01" name="rate_per_day" id="edit_rate_per_day" class="form-control" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required onchange="toggleAssignedPatient(this.value)">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3" id="edit_assigned_patient_group">
                        <label for="edit_assigned_patient_id" class="form-label">Assigned Patient</label>
                        <select name="assigned_patient_id" id="edit_assigned_patient_id" class="form-select">
                            <option value="">Select Patient (Optional)</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= htmlspecialchars($patient['id']) ?>">
                                    <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteRoomModal" tabindex="-1" aria-labelledby="deleteRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRoomModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this room? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="room_id" id="delete_room_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editRoom(room) {
        document.getElementById('edit_room_id').value = room.id;
        document.getElementById('edit_room_number').value = room.room_number;
        document.getElementById('edit_room_type').value = room.room_type;
        document.getElementById('edit_floor').value = room.floor;
        document.getElementById('edit_capacity').value = room.capacity;
        document.getElementById('edit_rate_per_day').value = room.rate_per_day;
        document.getElementById('edit_status').value = room.status;
        
        // Set assigned patient
        const assignedPatientSelect = document.getElementById('edit_assigned_patient_id');
        if (room.assigned_patient_id) {
            assignedPatientSelect.value = room.assigned_patient_id;
        } else {
            assignedPatientSelect.value = ''; // No patient assigned
        }

        // Toggle visibility of assigned patient dropdown based on status
        toggleAssignedPatient(room.status);

        var editRoomModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
        editRoomModal.show();
    }

    function deleteRoom(roomId) {
        document.getElementById('delete_room_id').value = roomId;
        var deleteRoomModal = new bootstrap.Modal(document.getElementById('deleteRoomModal'));
        deleteRoomModal.show();
    }

    function toggleAssignedPatient(status) {
        const assignedPatientGroup = document.getElementById('edit_assigned_patient_group');
        if (status === 'occupied') {
            assignedPatientGroup.style.display = 'block';
            document.getElementById('edit_assigned_patient_id').setAttribute('required', 'required');
        } else {
            assignedPatientGroup.style.display = 'none';
            document.getElementById('edit_assigned_patient_id').removeAttribute('required');
            document.getElementById('edit_assigned_patient_id').value = ''; // Clear selection if not occupied
        }
    }

    // Initial call for the add room modal (if it's shown with pre-selected status)
    document.addEventListener('DOMContentLoaded', function() {
        const addRoomStatusSelect = document.querySelector('#addRoomModal #status');
        if (addRoomStatusSelect) {
            addRoomStatusSelect.addEventListener('change', function() {
                const assignedPatientGroup = document.querySelector('#addRoomModal #edit_assigned_patient_group'); // Re-using ID, consider unique IDs
                if (assignedPatientGroup) {
                    if (this.value === 'occupied') {
                        assignedPatientGroup.style.display = 'block';
                        document.querySelector('#addRoomModal #edit_assigned_patient_id').setAttribute('required', 'required');
                    } else {
                        assignedPatientGroup.style.display = 'none';
                        document.querySelector('#addRoomModal #edit_assigned_patient_id').removeAttribute('required');
                        document.querySelector('#addRoomModal #edit_assigned_patient_id').value = '';
                    }
                }
            });
            // Hide on load if default is not occupied
            if (addRoomStatusSelect.value !== 'occupied') {
                const assignedPatientGroup = document.querySelector('#addRoomModal #edit_assigned_patient_group');
                if (assignedPatientGroup) {
                    assignedPatientGroup.style.display = 'none';
                }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
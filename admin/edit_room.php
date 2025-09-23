<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

requireRole('admin');

$pdo = getDBConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: rooms.php');
    exit;
}

$room_id = (int)$_GET['id'];

// Fetch room data
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header('Location: rooms.php?msg=Room not found');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = trim($_POST['room_number'] ?? '');
    $room_type = trim($_POST['room_type'] ?? '');
    $floor = (int)($_POST['floor'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 1);
    $rate_per_day = (float)($_POST['rate_per_day'] ?? 0);
    $status = $_POST['status'] ?? 'available';

    // Validation
    if ($room_number === '') {
        $errors[] = "Room number is required.";
    }
    if ($room_type === '') {
        $errors[] = "Room type is required.";
    }
    if ($floor <= 0) {
        $errors[] = "Floor must be a positive integer.";
    }
    if ($capacity <= 0) {
        $errors[] = "Capacity must be a positive integer.";
    }
    if ($rate_per_day < 0) {
        $errors[] = "Rate per day cannot be negative.";
    }
    if (!in_array($status, ['available', 'occupied', 'maintenance'])) {
        $errors[] = "Invalid status selected.";
    }

    // Check if room number already exists for another room
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND id != ?");
    $stmt->execute([$room_number, $room_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Room number already exists.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type = ?, floor = ?, capacity = ?, rate_per_day = ?, status = ? WHERE id = ?");
        $stmt->execute([$room_number, $room_type, $floor, $capacity, $rate_per_day, $status, $room_id]);
        header('Location: rooms.php?msg=Room updated successfully');
        exit;
    }
} else {
    // Pre-fill form with existing data
    $room_number = $room['room_number'];
    $room_type = $room['room_type'];
    $floor = $room['floor'];
    $capacity = $room['capacity'];
    $rate_per_day = $room['rate_per_day'];
    $status = $room['status'];
}

include '../includes/header.php';
?>

<div class="container">
    <h2><i class="fas fa-edit"></i> Edit Room</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="edit_room.php?id=<?= $room_id ?>" class="mt-3">
        <div class="mb-3">
            <label for="room_number" class="form-label">Room Number</label>
            <input type="text" name="room_number" id="room_number" class="form-control" required value="<?= htmlspecialchars($room_number) ?>">
        </div>
        <div class="mb-3">
            <label for="room_type" class="form-label">Room Type</label>
            <input type="text" name="room_type" id="room_type" class="form-control" required value="<?= htmlspecialchars($room_type) ?>">
        </div>
        <div class="mb-3">
            <label for="floor" class="form-label">Floor</label>
            <input type="number" name="floor" id="floor" class="form-control" min="1" required value="<?= htmlspecialchars($floor) ?>">
        </div>
        <div class="mb-3">
            <label for="capacity" class="form-label">Capacity</label>
            <input type="number" name="capacity" id="capacity" class="form-control" min="1" required value="<?= htmlspecialchars($capacity) ?>">
        </div>
        <div class="mb-3">
            <label for="rate_per_day" class="form-label">Rate Per Day (₹)</label>
            <input type="number" step="0.01" name="rate_per_day" id="rate_per_day" class="form-control" min="0" required value="<?= htmlspecialchars($rate_per_day) ?>">
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <?php
                $statuses = ['available', 'occupied', 'maintenance'];
                foreach ($statuses as $status_option) {
                    $selected = ($status === $status_option) ? 'selected' : '';
                    echo "<option value=\"$status_option\" $selected>" . ucfirst($status_option) . "</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Room</button>
        <a href="rooms.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
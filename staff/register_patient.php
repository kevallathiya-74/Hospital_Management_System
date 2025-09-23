<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('staff'); // Ensure only staff can access

$pdo = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password for the patient's user account
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $blood_group = trim($_POST['blood_group']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $emergency_contact_name = trim($_POST['emergency_contact_name']);
    $medical_history = trim($_POST['medical_history']);
    $allergies = trim($_POST['allergies']);

    try {
        $pdo->beginTransaction();

        $userId = null;
        // Only create a user account if username and password are provided
        if (!empty($username) && !empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'patient')");
            $stmt->execute([$username, $hashed_password, $email]);
            $userId = $pdo->lastInsertId();
        }

        // Insert patient details
        $stmt = $pdo->prepare("INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, phone, email, address, blood_group, emergency_contact, emergency_contact_name, medical_history, allergies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $first_name, $last_name, $date_of_birth, $gender, $phone, $email, $address, $blood_group, $emergency_contact, $emergency_contact_name, $medical_history, $allergies]);

        $pdo->commit();
        $message = '<div class="alert alert-success">Patient registered successfully!</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

include '../includes/header.php';
?>
<div class="container">
    <h2><i class="fas fa-user-plus"></i> Register New Patient</h2>
    <p>Use this form to register a new patient in the system. You can optionally create a user account for them.</p>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-header">
            <h5>Patient Details</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="blood_group" class="form-label">Blood Group</label>
                    <select class="form-control" id="blood_group" name="blood_group">
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="medical_history" class="form-label">Medical History</label>
                    <textarea class="form-control" id="medical_history" name="medical_history" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label for="allergies" class="form-label">Allergies</label>
                    <textarea class="form-control" id="allergies" name="allergies" rows="2"></textarea>
                </div>

                <hr>
                <h5>Optional: Create User Account for Patient</h5>
                <p class="text-muted">If you provide a username and password, a patient account will be created, allowing them to log in.</p>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-user-plus"></i> Register Patient
                </button>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
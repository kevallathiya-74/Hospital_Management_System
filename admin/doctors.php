<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/auth.php';
require_once '../config/db.php';

requireRole('admin');

$pdo = getDBConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $specialization = trim($_POST['specialization']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $qualification = trim($_POST['qualification']);
            $experience_years = (int)$_POST['experience_years'];
            $consultation_fee = (float)$_POST['consultation_fee'];
            
            try {
                $pdo->beginTransaction();
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'doctor')");
                $stmt->execute([$username, $password, $email]);
                $userId = $pdo->lastInsertId();
                
                // Insert doctor details
                $stmt = $pdo->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialization, phone, address, qualification, experience_years, consultation_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $first_name, $last_name, $specialization, $phone, $address, $qualification, $experience_years, $consultation_fee]);
                
                $pdo->commit();
                $message = '<div class="alert alert-success">Doctor added successfully!</div>';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        } elseif ($_POST['action'] === 'edit') {
            $doctor_id = (int)$_POST['doctor_id'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $specialization = trim($_POST['specialization']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);
            $qualification = trim($_POST['qualification']);
            $experience_years = (int)$_POST['experience_years'];
            $consultation_fee = (float)$_POST['consultation_fee'];
            $status = $_POST['status'];
            
            try {
                $stmt = $pdo->prepare("UPDATE doctors SET first_name = ?, last_name = ?, specialization = ?, phone = ?, address = ?, qualification = ?, experience_years = ?, consultation_fee = ?, status = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $specialization, $phone, $address, $qualification, $experience_years, $consultation_fee, $status, $doctor_id]);
                
                $message = '<div class="alert alert-success">Doctor updated successfully!</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        } elseif ($_POST['action'] === 'delete') {
            $doctor_id = (int)$_POST['doctor_id'];
            
            try {
                $stmt = $pdo->prepare("SELECT user_id FROM doctors WHERE id = ?");
                $stmt->execute([$doctor_id]);
                $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($doctor) {
                    $pdo->beginTransaction();
                    
                    // Delete doctor
                    $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
                    $stmt->execute([$doctor_id]);
                    
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$doctor['user_id']]);
                    
                    $pdo->commit();
                    $message = '<div class="alert alert-success">Doctor deleted successfully!</div>';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Get all doctors
$stmt = $pdo->query("SELECT d.*, u.username, u.email, u.status as user_status FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC");
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-user-md"></i> Manage Doctors
        </h1>
    </div>
</div>

<?php echo $message; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Add New Doctor</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="qualification" class="form-label">Qualification</label>
                        <input type="text" class="form-control" id="qualification" name="qualification">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="experience_years" class="form-label">Experience (Years)</label>
                                <input type="number" class="form-control" id="experience_years" name="experience_years" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="consultation_fee" class="form-label">Consultation Fee</label>
                                <input type="number" class="form-control" id="consultation_fee" name="consultation_fee" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Add Doctor
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Doctors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Phone</th>
                                <th>Experience</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($doctor['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                <td><?php echo $doctor['experience_years']; ?> years</td>
                                <td>₹<?php echo number_format($doctor['consultation_fee'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $doctor['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($doctor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDoctor(<?php echo $doctor['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Doctor Modal -->
<div class="modal fade" id="editDoctorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="doctor_id" id="edit_doctor_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_specialization" class="form-label">Specialization</label>
                        <input type="text" class="form-control" id="edit_specialization" name="specialization" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_qualification" class="form-label">Qualification</label>
                        <input type="text" class="form-control" id="edit_qualification" name="qualification">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_experience_years" class="form-label">Experience (Years)</label>
                                <input type="number" class="form-control" id="edit_experience_years" name="experience_years" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_consultation_fee" class="form-label">Consultation Fee</label>
                                <input type="number" class="form-control" id="edit_consultation_fee" name="consultation_fee" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this doctor? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="doctor_id" id="delete_doctor_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editDoctor(doctor) {
    document.getElementById('edit_doctor_id').value = doctor.id;
    document.getElementById('edit_first_name').value = doctor.first_name;
    document.getElementById('edit_last_name').value = doctor.last_name;
    document.getElementById('edit_specialization').value = doctor.specialization;
    document.getElementById('edit_phone').value = doctor.phone;
    document.getElementById('edit_address').value = doctor.address;
    document.getElementById('edit_qualification').value = doctor.qualification;
    document.getElementById('edit_experience_years').value = doctor.experience_years;
    document.getElementById('edit_consultation_fee').value = doctor.consultation_fee;
    document.getElementById('edit_status').value = doctor.status;
    
    new bootstrap.Modal(document.getElementById('editDoctorModal')).show();
}

function deleteDoctor(doctorId) {
    document.getElementById('delete_doctor_id').value = doctorId;
    new bootstrap.Modal(document.getElementById('deleteDoctorModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>  
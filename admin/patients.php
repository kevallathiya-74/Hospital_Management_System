<?php
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
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'patient')");
                $stmt->execute([$username, $password, $email]);
                $userId = $pdo->lastInsertId();
                
                // Insert patient details
                $stmt = $pdo->prepare("INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, phone, email, address, blood_group, emergency_contact, emergency_contact_name, medical_history, allergies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $first_name, $last_name, $date_of_birth, $gender, $phone, $email, $address, $blood_group, $emergency_contact, $emergency_contact_name, $medical_history, $allergies]);
                
                $pdo->commit();
                $message = '<div class="alert alert-success">Patient added successfully!</div>';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        } elseif ($_POST['action'] === 'edit') {
            $patient_id = (int)$_POST['patient_id'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $date_of_birth = $_POST['date_of_birth'];
            $gender = $_POST['gender'];
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            $blood_group = trim($_POST['blood_group']);
            $emergency_contact = trim($_POST['emergency_contact']);
            $emergency_contact_name = trim($_POST['emergency_contact_name']);
            $medical_history = trim($_POST['medical_history']);
            $allergies = trim($_POST['allergies']);
            $status = $_POST['status'];
            
            try {
                $stmt = $pdo->prepare("UPDATE patients SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, phone = ?, email = ?, address = ?, blood_group = ?, emergency_contact = ?, emergency_contact_name = ?, medical_history = ?, allergies = ?, status = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $date_of_birth, $gender, $phone, $email, $address, $blood_group, $emergency_contact, $emergency_contact_name, $medical_history, $allergies, $status, $patient_id]);
                
                $message = '<div class="alert alert-success">Patient updated successfully!</div>';
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        } elseif ($_POST['action'] === 'delete') {
            $patient_id = (int)$_POST['patient_id'];
            
            try {
                $stmt = $pdo->prepare("SELECT user_id FROM patients WHERE id = ?");
                $stmt->execute([$patient_id]);
                $patient = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($patient) {
                    $pdo->beginTransaction();
                    
                    // Delete patient
                    $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
                    $stmt->execute([$patient_id]);
                    
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$patient['user_id']]);
                    
                    $pdo->commit();
                    $message = '<div class="alert alert-success">Patient deleted successfully!</div>';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Get all patients
$stmt = $pdo->query("SELECT p.*, u.username, u.email as user_email, u.status as user_status FROM patients p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-users"></i> Manage Patients
        </h1>
    </div>
</div>

<?php echo $message; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus"></i> Add New Patient</h5>
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="emergency_contact" class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name">
                            </div>
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
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Add Patient
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> All Patients</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Blood Group</th>
                                <th>Age</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($patient['username'] ?? 'No Account'); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($patient['phone']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($patient['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($patient['blood_group'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $dob = new DateTime($patient['date_of_birth']);
                                    $now = new DateTime();
                                    $age = $now->diff($dob)->y;
                                    echo $age . ' years';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $patient['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($patient['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="editPatient(<?php echo htmlspecialchars(json_encode($patient)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deletePatient(<?php echo $patient['id']; ?>)">
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

<!-- Edit Patient Modal -->
<div class="modal fade" id="editPatientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="patient_id" id="edit_patient_id">
                    
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="edit_date_of_birth" name="date_of_birth" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_gender" class="form-label">Gender</label>
                                <select class="form-control" id="edit_gender" name="gender" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="edit_phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_blood_group" class="form-label">Blood Group</label>
                        <select class="form-control" id="edit_blood_group" name="blood_group">
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_emergency_contact" class="form-label">Emergency Contact</label>
                                <input type="text" class="form-control" id="edit_emergency_contact" name="emergency_contact">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                <input type="text" class="form-control" id="edit_emergency_contact_name" name="emergency_contact_name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_medical_history" class="form-label">Medical History</label>
                        <textarea class="form-control" id="edit_medical_history" name="medical_history" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_allergies" class="form-label">Allergies</label>
                        <textarea class="form-control" id="edit_allergies" name="allergies" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this patient? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="patient_id" id="delete_patient_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editPatient(patient) {
    document.getElementById('edit_patient_id').value = patient.id;
    document.getElementById('edit_first_name').value = patient.first_name;
    document.getElementById('edit_last_name').value = patient.last_name;
    document.getElementById('edit_date_of_birth').value = patient.date_of_birth;
    document.getElementById('edit_gender').value = patient.gender;
    document.getElementById('edit_phone').value = patient.phone;
    document.getElementById('edit_email').value = patient.email;
    document.getElementById('edit_address').value = patient.address;
    document.getElementById('edit_blood_group').value = patient.blood_group;
    document.getElementById('edit_emergency_contact').value = patient.emergency_contact;
    document.getElementById('edit_emergency_contact_name').value = patient.emergency_contact_name;
    document.getElementById('edit_medical_history').value = patient.medical_history;
    document.getElementById('edit_allergies').value = patient.allergies;
    document.getElementById('edit_status').value = patient.status;
    
    new bootstrap.Modal(document.getElementById('editPatientModal')).show();
}

function deletePatient(patientId) {
    document.getElementById('delete_patient_id').value = patientId;
    new bootstrap.Modal(document.getElementById('deletePatientModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
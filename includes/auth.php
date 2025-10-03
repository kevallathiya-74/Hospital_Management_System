<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../public/index.php");
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: ../public/dashboard.php");
        exit();
    }
}

// Login function
function login($username, $password) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? AND status = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// Logout function
function logout() {
    // Unset all session variables
    $_SESSION = array();

    // If session uses cookies, delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../public/index.php");
    exit();
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user by role
function getUserByRole($role) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? AND status = 'active'");
    $stmt->execute([$role]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get navigation links for the current user's role
 * Returns an array of [url, icon, label]
 */
function getRoleNavLinks() {
    if (!isLoggedIn()) return [];
    $role = $_SESSION['role'];
    $links = [];
    switch ($role) {
        case 'admin':
            $links = [
                ['../admin/doctors.php', 'fas fa-user-md', 'Doctors'],
                ['../admin/patients.php', 'fas fa-users', 'Patients'],
                ['../admin/rooms.php', 'fas fa-bed', 'Rooms'],
                ['../admin/appointments.php', 'fas fa-calendar-check', 'Appointments'],
                ['../admin/reports.php', 'fas fa-chart-bar', 'Reports'],
                ['../admin/billing.php', 'fas fa-file-invoice-dollar', 'Billing'],
            ];
            break;
        case 'doctor':
            $links = [
                ['../doctor/appointments.php', 'fas fa-calendar-check', 'My Appointments'],
                ['../doctor/schedule.php', 'fas fa-clock', 'My Schedule'], // NEW
                ['../doctor/patients.php', 'fas fa-users', 'Patients'],
                ['../doctor/prescriptions.php', 'fas fa-prescription', 'Prescriptions'],
            ];
            break;
        case 'staff':
            $links = [
                ['../staff/register_patient.php', 'fas fa-user-plus', 'Register Patient'],
                ['../staff/schedule_appointment.php', 'fas fa-calendar-plus', 'Schedule Appointment'],
                ['../staff/appointments.php', 'fas fa-calendar-check', 'Manage Appointments'], // NEW
                ['../staff/billing.php', 'fas fa-file-invoice-dollar', 'Billing'],
            ];
            break;
        case 'patient':
            $links = [
                ['../patient/dashboard.php', 'fas fa-tachometer-alt', 'Dashboard'], // Added for consistency
                ['../patient/schedule_appointment.php', 'fas fa-calendar-plus', 'Schedule Appointment'], // ADDED THIS LINE
                ['../patient/appointments.php', 'fas fa-calendar-check', 'My Appointments'],
                ['../patient/available_doctors.php', 'fas fa-user-md', 'Available Doctors'], // NEW
                ['../patient/reports.php', 'fas fa-file-medical', 'My Reports'],
                ['../patient/profile.php', 'fas fa-user', 'Profile'],
            ];
            break;
    }
    return $links;
}

/**
 * Get dashboard stats queries for the current user's role
 * Returns an array of [label, value, icon, color]
 */
function getRoleDashboardStats($pdo, $userId) {
    $role = $_SESSION['role'];
    $stats = [];
    switch ($role) {
        case 'admin':
            $stats[] = ['Doctors', $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'active'")->fetchColumn(), 'fa-user-md', 'primary'];
            $stats[] = ['Patients', $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'active'")->fetchColumn(), 'fa-users', 'success'];
            $stats[] = ['Appointments', $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn(), 'fa-calendar-check', 'warning']; // Changed to pending
            $stats[] = ['Rooms', $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn(), 'fa-bed', 'info'];
            $stats[] = ['Pending Bills', $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'pending'")->fetchColumn(), 'fa-file-invoice-dollar', 'danger'];
            break;
        case 'doctor':
            // Get doctor_id from user_id
            $stmt_doctor_id = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
            $stmt_doctor_id->execute([$userId]);
            $doctor_data = $stmt_doctor_id->fetch(PDO::FETCH_ASSOC);
            $doctor_id = $doctor_data ? $doctor_data['id'] : 0;

            $stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'");
            $stmt_pending->execute([$doctor_id]);
            $pending_appointments = $stmt_pending->fetchColumn();

            $stmt_approved = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'approved'");
            $stmt_approved->execute([$doctor_id]);
            $approved_appointments = $stmt_approved->fetchColumn();

            $stmt_prescriptions = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE doctor_id = ?");
            $stmt_prescriptions->execute([$doctor_id]);
            $prescriptions_count = $stmt_prescriptions->fetchColumn();

            $stats[] = ['Pending Appointments', $pending_appointments, 'fa-calendar-check', 'warning']; // Changed to pending
            $stats[] = ['Approved Appointments', $approved_appointments, 'fa-check', 'success']; // New stat
            $stats[] = ['Prescriptions', $prescriptions_count, 'fa-prescription', 'info'];
            break;
        case 'staff':
            $stats[] = ['Pending Appointments', $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn(), 'fa-calendar-check', 'warning']; // Changed to pending
            $stats[] = ['Pending Bills', $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'pending'")->fetchColumn(), 'fa-file-invoice-dollar', 'danger'];
            $stats[] = ['Active Patients', $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'active'")->fetchColumn(), 'fa-users', 'success'];
            break;
        case 'patient':
            $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
            $stmt->execute([$userId]);
            $pat = $stmt->fetch(PDO::FETCH_ASSOC);
            $pid = $pat ? $pat['id'] : 0;

            $stmt_pending = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'pending'");
            $stmt_pending->execute([$pid]);
            $pending_appointments = $stmt_pending->fetchColumn();

            $stmt_approved = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'approved'");
            $stmt_approved->execute([$pid]);
            $approved_appointments = $stmt_approved->fetchColumn();

            $stmt_reports = $pdo->prepare("SELECT COUNT(*) FROM medical_reports WHERE patient_id = ?");
            $stmt_reports->execute([$pid]);
            $medical_reports_count = $stmt_reports->fetchColumn();

            $stats[] = ['Pending Appointments', $pending_appointments, 'fa-calendar-check', 'warning']; // Changed to pending
            $stats[] = ['Approved Appointments', $approved_appointments, 'fa-check', 'success']; // New stat
            $stats[] = ['Medical Reports', $medical_reports_count, 'fa-file-medical', 'info'];
            break;
    }
    return $stats;
}
?>

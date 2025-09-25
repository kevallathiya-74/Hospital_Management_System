<?php
/**
 * Centralized Appointment Handler
 * Reusable functions for approve/reject and doctor availability.
 * Include this in any page: require_once 'appointment_handler.php';
 */

function approveAppointment($pdo, $appointment_id) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'approved', rejection_reason = NULL WHERE id = ? AND status = 'pending'");
    return $stmt->execute([$appointment_id]);
}

function rejectAppointment($pdo, $appointment_id, $reason) {
    if (empty($reason)) {
        return false; // Reason required
    }
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'rejected', rejection_reason = ? WHERE id = ? AND status = 'pending'");
    return $stmt->execute([$reason, $appointment_id]);
}

function getAvailableDoctors($pdo, $day = null) {
    $query = "
        SELECT DISTINCT d.id, CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, d.specialization,
               GROUP_CONCAT(CONCAT(ds.day_of_week, ': ', TIME_FORMAT(ds.start_time, '%h:%i %p'), ' - ', TIME_FORMAT(ds.end_time, '%h:%i %p')) ORDER BY FIELD(ds.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ds.start_time SEPARATOR '<br>') AS schedule
        FROM doctors d
        JOIN doctor_schedules ds ON d.id = ds.doctor_id AND ds.is_available = 1
        WHERE d.status = 'active'
    ";
    $params = [];
    if ($day) {
        $query .= " AND ds.day_of_week = ?";
        $params[] = $day;
    }
    $query .= " GROUP BY d.id ORDER BY d.last_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDoctorSchedule($pdo, $doctor_id) {
    $stmt = $pdo->prepare("
        SELECT id, day_of_week, start_time, end_time
        FROM doctor_schedules
        WHERE doctor_id = ? AND is_available = 1
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time
    ");
    $stmt->execute([$doctor_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateDoctorSchedule($pdo, $doctor_id, $day, $start_time, $end_time, $schedule_id = null) {
    if ($schedule_id) { // Edit existing
        $stmt = $pdo->prepare("UPDATE doctor_schedules SET day_of_week = ?, start_time = ?, end_time = ? WHERE id = ? AND doctor_id = ?");
        return $stmt->execute([$day, $start_time, $end_time, $schedule_id, $doctor_id]);
    } else { // Add new
        $stmt = $pdo->prepare("
            INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)
        ");
        return $stmt->execute([$doctor_id, $day, $start_time, $end_time]);
    }
}

function deleteDoctorSchedule($pdo, $schedule_id, $doctor_id) {
    $stmt = $pdo->prepare("DELETE FROM doctor_schedules WHERE id = ? AND doctor_id = ?");
    return $stmt->execute([$schedule_id, $doctor_id]);
}


function getPendingAppointments($pdo, $role = null, $user_id = null) {
    $query = "
        SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
               CONCAT(d.first_name, ' ', d.last_name) AS doctor_name, d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.status = 'pending'
    ";
    $params = [];
    if ($role === 'doctor') {
        $doctor_stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $doctor_stmt->execute([$user_id]);
        $doctor_id = $doctor_stmt->fetchColumn();
        $query .= " AND a.doctor_id = ?";
        $params[] = $doctor_id;
    } elseif ($role === 'staff' || $role === 'admin') {
        // Staff/Admin see all pending appointments
    }
    $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// NEW: Mark appointment as completed
function completeAppointment($pdo, $appointment_id, $doctor_id) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ? AND doctor_id = ? AND status = 'approved'");
    return $stmt->execute([$appointment_id, $doctor_id]);
}

// NEW: Add prescription dynamically
function addPrescription($pdo, $doctor_id, $patient_id, $appointment_id, $medication, $dosage, $instructions, $issue_date) {
    if (empty($medication)) {
        return false; // Medication required
    }
    $stmt = $pdo->prepare("
        INSERT INTO prescriptions (doctor_id, patient_id, appointment_id, medication, dosage, instructions, issue_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    return $stmt->execute([$doctor_id, $patient_id, $appointment_id, $medication, $dosage, $instructions, $issue_date]);
}

// NEW: Generate bill dynamically
function generateBill($pdo, $patient_id, $appointment_id, $consultation_fee, $other_charges, $payment_method, $due_date) {
    if (empty($payment_method)) {
        return false; // Payment method required
    }
    $total_amount = $consultation_fee + $other_charges;
    $bill_date = date('Y-m-d');
    $stmt = $pdo->prepare("
        INSERT INTO bills (patient_id, appointment_id, bill_date, due_date, consultation_fee, other_charges, total_amount, paid_amount, status, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 'pending', ?)
    ");
    return $stmt->execute([$patient_id, $appointment_id, $bill_date, $due_date, $consultation_fee, $other_charges, $total_amount, $payment_method]);
}

// NEW: Get patient's appointments for doctor view (with doctor filter)
function getPatientAppointments($pdo, $doctor_id, $patient_id = null) {
    $query = "
        SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) AS patient_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
    ";
    $params = [$doctor_id];
    if ($patient_id) {
        $query .= " AND a.patient_id = ?";
        $params[] = $patient_id;
    }
    $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// NEW: Get doctor's consultation fee
function getDoctorConsultationFee($pdo, $doctor_id) {
    $stmt = $pdo->prepare("SELECT consultation_fee FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $result = $stmt->fetchColumn();
    return $result ?: 0.00;
}

// NEW: Get existing prescriptions for an appointment
function getPrescriptionByAppointment($pdo, $appointment_id) {
    $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE appointment_id = ? ORDER BY issue_date DESC");
    $stmt->execute([$appointment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// NEW: Get existing bill for an appointment
function getBillByAppointment($pdo, $appointment_id) {
    $stmt = $pdo->prepare("SELECT * FROM bills WHERE appointment_id = ? ORDER BY bill_date DESC LIMIT 1");
    $stmt->execute([$appointment_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // Only latest bill
}
?>

-- Hospital Management System Database Schema and Sample Data

-- Create Database
CREATE DATABASE IF NOT EXISTS hospital_management;
USE hospital_management;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'doctor', 'staff', 'patient') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctors Table
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    specialization VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    qualification VARCHAR(100),
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Patients Table
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    blood_group VARCHAR(5),
    emergency_contact VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    medical_history TEXT,
    allergies TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Rooms Table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type VARCHAR(50),
    floor INT,
    capacity INT DEFAULT 1,
    rate_per_day DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    assigned_patient_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- Appointments Table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Medical Reports Table
CREATE TABLE medical_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    report_type VARCHAR(100),
    report_title VARCHAR(255),
    report_date DATE NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    status ENUM('pending', 'completed', 'reviewed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Bills Table
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_id INT,
    room_id INT,
    bill_date DATE NOT NULL,
    due_date DATE,
    consultation_fee DECIMAL(10, 2) DEFAULT 0.00,
    room_charges DECIMAL(10, 2) DEFAULT 0.00,
    medicine_charges DECIMAL(10, 2) DEFAULT 0.00,
    other_charges DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('pending', 'paid', 'partially_paid', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

-- Prescriptions Table
CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    medication VARCHAR(255) NOT NULL,
    dosage VARCHAR(100),
    instructions TEXT,
    issue_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- Insert sample users (password is 'password' hashed with PHP's password_hash)
INSERT INTO users (username, password, email, role) VALUES
('keval', '$2y$10$7Q6Xq6q6q6q6q6q6q6q6qOq6q6q6q6q6q6q6q6q6q6q6q6q6q6q6q', 'keval@gmail.com', 'admin'),
('doctor', '$2y$10$E1Nq6q6q6q6q6q6q6q6qOq6q6q6q6q6q6q6q6q6q6q6q6q6q6q6q', 'doctor@gmail.com', 'doctor'),
('staff', '$2y$10$F2Nq6q6q6q6q6q6q6q6qOq6q6q6q6q6q6q6q6q6q6q6q6q6q6q6q', 'staff@gmail.com', 'staff'),
('patient', '$2y$10$G3Nq6q6q6q6q6q6q6q6qOq6q6q6q6q6q6q6q6q6q6q6q6q6q6q6q', 'patient@gmail.com', 'patient');

-- Insert doctors linked to user doctor
INSERT INTO doctors (user_id, first_name, last_name, specialization, phone, address, qualification, experience_years, consultation_fee) VALUES
((SELECT id FROM users WHERE username = 'doctor'), 'Chirag', 'Parmar', 'Cardiology', '+91 8530405568', '21, Shastri Nagar, Ahmedabad', 'MD', 10, 500.00);

-- Insert patients linked to user patient
INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, phone, email, address, blood_group, emergency_contact, emergency_contact_name, medical_history, allergies) VALUES
((SELECT id FROM users WHERE username = 'patient'), 'Ramesh', 'Chauhan', '1990-05-15', 'male', '+91 8866705568', 'ramesh05@gmail.com', '35, Trikon Bag, Rajkot', 'A+', '+91 9876012345', 'Ankit Mehta', 'Mild asthma', 'Penicillin');

-- Insert another patient without user account
INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone, email, address, blood_group, emergency_contact, emergency_contact_name, medical_history, allergies) VALUES
('Priya', 'Sharma', '1985-11-20', 'female', '+91 9812345672', 'priya26@gmail.com', '14, Lajpat Nagar, New Delhi', 'O-', '+91 9958765430', 'Neha Sharma', 'Migraine', 'Dust allergy');

-- Insert rooms
INSERT INTO rooms (room_number, room_type, floor, capacity, rate_per_day, status) VALUES
('101', 'Standard', 1, 1, 150.00, 'available'),
('102', 'Standard', 1, 1, 150.00, 'occupied'),
('201', 'Deluxe', 2, 2, 300.00, 'available');

-- Insert appointments
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES
((SELECT id FROM patients WHERE user_id = (SELECT id FROM users WHERE username = 'patient')), (SELECT id FROM doctors WHERE user_id = (SELECT id FROM users WHERE username = 'doctor')), '2024-07-20', '10:00:00', 'Routine Checkup', 'scheduled'),
((SELECT id FROM patients WHERE first_name = 'Priya' AND last_name = 'Sharma'), (SELECT id FROM doctors WHERE user_id = (SELECT id FROM users WHERE username = 'doctor')), '2024-07-18', '14:30:00', 'Follow-up', 'completed');

-- Insert medical reports
INSERT INTO medical_reports (patient_id, doctor_id, report_type, report_title, report_date, description, status) VALUES
((SELECT id FROM patients WHERE user_id = (SELECT id FROM users WHERE username = 'patient')), (SELECT id FROM doctors WHERE user_id = (SELECT id FROM users WHERE username = 'doctor')), 'Blood Test', 'Complete Blood Count', '2024-07-10', 'Patient showed normal blood count.', 'completed');

-- Insert bills
INSERT INTO bills (patient_id, appointment_id, room_id, bill_date, due_date, consultation_fee, room_charges, medicine_charges, other_charges, total_amount, paid_amount, status) VALUES
((SELECT id FROM patients WHERE user_id = (SELECT id FROM users WHERE username = 'patient')), (SELECT id FROM appointments WHERE patient_id = (SELECT id FROM patients WHERE user_id = (SELECT id FROM users WHERE username = 'patient')) AND appointment_date = '2024-07-20'), NULL, '2024-07-20', '2024-08-20', 500.00, 0.00, 0.00, 0.00, 500.00, 0.00, 'pending'),
((SELECT id FROM patients WHERE first_name = 'Priya' AND last_name = 'Sharma'), (SELECT id FROM appointments WHERE patient_id = (SELECT id FROM patients WHERE first_name = 'Priya' AND last_name = 'Sharma') AND appointment_date = '2024-07-18'), (SELECT id FROM rooms WHERE room_number = '102'), '2024-07-18', '2024-08-18', 500.00, 300.00, 120.50, 50.00, 970.50, 970.50, 'paid');

-- Insert prescriptions
INSERT INTO prescriptions (patient_id, doctor_id, medication, dosage, instructions, issue_date) VALUES
((SELECT id FROM patients WHERE user_id = (SELECT id FROM users WHERE username = 'patient')), (SELECT id FROM doctors WHERE user_id = (SELECT id FROM users WHERE username = 'doctor')), 'Amoxicillin', '500mg', 'Take one capsule every 8 hours for 7 days.', '2024-07-18');
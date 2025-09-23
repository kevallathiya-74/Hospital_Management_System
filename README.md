# Hospital Management System

A comprehensive web-based Hospital Management System built with **PHP (Core PHP)** and **MySQL**. This system manages key hospital operations including patient registration, doctor management, appointments, billing, room allocation, and medical reports.

## 🎯 Features

### 👥 Multi-Role System
- **Admin**: Full system access with user management, reports, and system configuration
- **Doctor**: View appointments, manage patients, add prescriptions
- **Staff/Receptionist**: Register patients, schedule appointments, handle billing
- **Patient**: View appointments, reports, and personal information (optional)

### 🔍 Core Functionalities

#### For Admin:
- ✅ User management (Add/Edit/Delete Doctors, Staff, Patients)
- ✅ Room management and allocation
- ✅ View all appointments and generate reports
- ✅ Billing history and financial reports
- ✅ System statistics and analytics

#### For Doctors:
- ✅ View assigned appointments
- ✅ Access patient details and medical history
- ✅ Add prescriptions and medical notes
- ✅ View patient treatment history

#### For Staff/Receptionist:
- ✅ Register new patients
- ✅ Schedule appointments with doctors
- ✅ Generate and manage bills
- ✅ View patient information

#### For Patients (Optional):
- ✅ View personal appointments
- ✅ Access medical reports
- ✅ View billing information
- ✅ Update personal profile

## 🛠️ Technology Stack

- **Backend**: PHP (Core PHP - No Framework)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Server**: Apache/Nginx (XAMPP/WAMP/LAMP)

## 📁 Project Structure

```
Project/
├── config/                 # Database configuration
│   └── db.php
├── public/                 # Public entry points
│   ├── index.php          # Login page
│   ├── dashboard.php      # Main dashboard
│   └── logout.php         # Logout handler
├── admin/                  # Admin module
│   ├── doctors.php        # Doctor management
│   ├── patients.php       # Patient management
│   ├── rooms.php          # Room management
│   ├── appointments.php    # Appointment management
│   ├── reports.php        # Reports generation
│   └── billing.php        # Billing management
├── doctor/                 # Doctor module
│   ├── appointments.php    # View appointments
│   ├── patients.php       # Patient details
│   └── prescriptions.php   # Prescription management
├── staff/                  # Staff module
│   ├── register_patient.php
│   ├── schedule_appointment.php
│   └── billing.php
├── patient/                # Patient module (optional)
│   ├── appointments.php
│   ├── reports.php
│   └── profile.php
├── includes/               # Shared components
│   ├── header.php         # Navigation header
│   ├── footer.php         # Footer
│   └── auth.php           # Authentication system
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css      # Custom styles
│   ├── js/
│   │   └── script.js      # JavaScript functions
│   └── images/
├── database.sql            # Database schema
└── README.md              # This file
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache server
- XAMPP

### Step 1: Database Setup
1. Start your MySQL server
2. Create a new database named `hospital_management`
3. Import the database schema:
   ```sql
   mysql -u root -p hospital_management < database.sql
   ```
   Or use phpMyAdmin to import `database.sql`

### Step 2: Project Setup
1. Clone or download this project to your web server directory
   ```bash
   # For XAMPP (Windows)
   C:\xampp\htdocs\Project\
   
   # For WAMP (Windows)
   C:\wamp64\www\Project\
   
   # For LAMP (Linux)
   /var/www/html/Project/
   ```

2. Configure database connection in `config/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hospital_management');
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password
   ```

3. Set proper file permissions (Linux/Mac):
   ```bash
   chmod 755 -R Project/
   ```

### Step 3: Access the Application
1. Start your web server (Apache) and MySQL
2. Open your browser and navigate to:
   ```
   http://localhost/Project/public/
   ```

## 👤 Default Login Credentials

The system comes with pre-configured demo accounts:

| Role | Username | Password |
|------|----------|----------|
| Admin | keval | keval |
| Doctor | doctor | doctor |
| Staff | staff | staff |
| Patient | patient | patient |

## 🔧 Configuration

### Database Configuration
Edit `config/db.php` to match your database settings:
```php
define('DB_HOST', 'localhost');     // Database host
define('DB_NAME', 'hospital_management'); // Database name
define('DB_USER', 'root');         // Database username
define('DB_PASS', '');             // Database password
```

### Security Settings
- Change default passwords in the database
- Enable HTTPS in production
- Set proper file permissions
- Configure session security

## 📊 Database Schema

The system uses the following main tables:
- `users` - User authentication and roles
- `doctors` - Doctor information and specializations
- `patients` - Patient details and medical history
- `rooms` - Room allocation and status
- `appointments` - Appointment scheduling
- `prescriptions` - Medical prescriptions
- `bills` - Billing and payment information
- `medical_reports` - Patient medical reports

## 🎨 Customization

### Styling
- Edit `assets/css/style.css` for custom styles
- Modify Bootstrap classes in PHP files
- Add custom themes and branding

### Functionality
- Add new modules in respective directories
- Extend database schema as needed
- Implement additional features

## 🔒 Security Features

- ✅ Password hashing using PHP's `password_hash()`
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ SQL injection prevention with prepared statements
- ✅ XSS protection with `htmlspecialchars()`
- ✅ CSRF protection (implement as needed)

## 📈 Future Enhancements

- [ ] Email notifications
- [ ] SMS integration
- [ ] Advanced reporting with charts
- [ ] Mobile app integration
- [ ] Payment gateway integration
- [ ] Electronic Health Records (EHR)
- [ ] Multi-language support
- [ ] API endpoints for external integrations

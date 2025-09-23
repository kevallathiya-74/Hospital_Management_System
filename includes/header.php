<?php
require_once 'auth.php';
require_once '../config/db.php'; // Ensure db.php is included for getDBConnection if needed by getCurrentUser  or other functions
$currentUser  = getCurrentUser ();

function getDashboardUrlByRole() {
    if (!isLoggedIn()) return '../public/index.php';
    switch ($_SESSION['role']) {
        case 'admin': return '../admin/dashboard.php';
        case 'doctor': return '../doctor/dashboard.php';
        case 'staff': return '../staff/dashboard.php';
        case 'patient': return '../patient/dashboard.php';
        default: return '../public/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <!-- Google Fonts: Roboto for professional look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= getDashboardUrlByRole(); ?>">
                <i class="fas fa-hospital"></i> HMS
            </a>
            
            <?php if (isLoggedIn()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach (getRoleNavLinks() as $link): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $link[0] ?>">
                                <i class="<?= $link[1] ?>"></i> <?= $link[2] ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../public/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../public/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <main class="flex-grow-1 container mt-4">
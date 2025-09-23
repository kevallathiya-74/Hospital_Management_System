<?php
require_once '../includes/auth.php';
require_once '../config/db.php';
requireRole('patient');
$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$stats = getRoleDashboardStats($pdo, $userId);
include '../includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="fas fa-tachometer-alt"></i> Patient Dashboard</h1>
    </div>
</div>
<div class="row">
    <?php foreach ($stats as $stat): ?>
        <div class="col-md-4 mb-4">
            <div class="card bg-<?= $stat[3] ?> text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stat[1] ?></h4>
                            <p class="mb-0"><?= $stat[0] ?></p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas <?= $stat[2] ?> fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php include '../includes/footer.php'; ?>
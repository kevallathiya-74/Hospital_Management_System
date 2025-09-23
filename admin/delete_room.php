<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

requireRole('admin');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: rooms.php');
    exit;
}

$room_id = (int)$_GET['id'];
$pdo = getDBConnection();

// Optional: Check if room is assigned to a patient or occupied before deleting
// For simplicity, we delete directly here

$stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);

header('Location: rooms.php?msg=Room deleted successfully');
exit;
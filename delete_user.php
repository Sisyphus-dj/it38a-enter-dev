<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

// Get user ID from URL
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header('Location: admin_users.php');
    exit;
}

// Verify user exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    header('Location: admin_users.php');
    exit;
}

// Delete user
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$user_id]);

header('Location: admin_users.php');
exit; 
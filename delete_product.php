<?php
session_start();
include_once 'db_connection.php'; // Ensure this path is correct

// Define product upload directory, in case needed for path construction, though __DIR__ is better for unlink
define('PRODUCT_UPLOAD_DIR', 'uploads/products/');

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

// Get product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    header('Location: admin_products.php');
    exit;
}

// Verify product exists
$stmt = $pdo->prepare('SELECT id FROM products WHERE id = ?');
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    header('Location: admin_products.php');
    exit;
}

// Delete product
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$product_id]);

header('Location: admin_products.php');
exit;

?> 
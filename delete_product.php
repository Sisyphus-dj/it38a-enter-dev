<?php
session_start();
include_once 'db_connection.php'; // Ensure this path is correct

// Define product upload directory, in case needed for path construction, though __DIR__ is better for unlink
define('PRODUCT_UPLOAD_DIR', 'uploads/products/');

// User must be logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['shop_message'] = "You must be logged in to delete products.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: login.php');
    exit;
}

// Get product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    $_SESSION['shop_message'] = "Invalid product ID specified for deletion.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

$current_user_id = $_SESSION['user']['id'];

// Verify product exists and ownership
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    $_SESSION['shop_message'] = "Product not found.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}
if ($product['user_id'] != $current_user_id) {
    $_SESSION['shop_message'] = "You are not authorized to delete this product.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

// Check if product is referenced in order_items
$stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
$stmt->execute([$product_id]);
$order_count = $stmt->fetchColumn();
if ($order_count > 0) {
    $_SESSION['shop_message'] = "This product cannot be deleted because it has already been ordered.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

// Delete product image if exists
if (!empty($product['image_path']) && file_exists($product['image_path'])) {
    unlink($product['image_path']);
}

// Delete product
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$product_id]);

$_SESSION['shop_message'] = "Product deleted successfully.";
$_SESSION['shop_message_type'] = "success";
header('Location: shop_page.php');
exit;

?> 
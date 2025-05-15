<?php
session_start();
include_once 'db_connection.php'; // Ensure this path is correct

// Define product upload directory, in case needed for path construction, though __DIR__ is better for unlink
define('PRODUCT_UPLOAD_DIR', 'uploads/products/');

if (!isset($_SESSION['user'])) {
    $_SESSION['shop_message'] = "You must be logged in to delete products.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['shop_message'] = "Invalid product ID specified for deletion.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

$product_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user']['id'];

if (isset($pdo)) {
    try {
        // Fetch the product to verify ownership and get image path
        $stmt = $pdo->prepare("SELECT user_id, image_path FROM products WHERE id = :id");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['shop_message'] = "Product not found.";
            $_SESSION['shop_message_type'] = "error";
            header('Location: shop_page.php');
            exit;
        }

        // Check if the current user owns the product
        if ($product['user_id'] != $current_user_id) {
            $_SESSION['shop_message'] = "You are not authorized to delete this product.";
            $_SESSION['shop_message_type'] = "error";
            header('Location: shop_page.php');
            exit;
        }

        // Proceed with deletion
        // 1. Delete the image file if it exists
        if (!empty($product['image_path'])) {
            // Assuming image_path is stored relative to the project root e.g., "uploads/products/image.jpg"
            // and this script (delete_product.php) is in the project root.
            $image_file_path = __DIR__ . '/' . $product['image_path']; 
            // More robustly, if image_path is always relative to root, just use it directly:
            // $image_file_path = $product['image_path']; 
            // If $product['image_path'] is just 'image.jpg' and PRODUCT_UPLOAD_DIR must be prepended:
            // $image_file_path = PRODUCT_UPLOAD_DIR . basename($product['image_path']);
            // For now, assuming $product['image_path'] is like 'uploads/products/xyz.jpg' as stored by submit_product.php

            if (file_exists($image_file_path)) {
                if (!unlink($image_file_path)) {
                    error_log("Failed to delete product image file: " . $image_file_path . " for product ID: " . $product_id);
                    // Consider whether to halt or inform user more specifically. 
                    // For now, we proceed to delete DB record.
                }
            } else {
                 error_log("Product image file not found for deletion: " . $image_file_path . " for product ID: " . $product_id);
            }
        }

        // 2. Delete the product from the database
        $stmt_delete = $pdo->prepare("DELETE FROM products WHERE id = :id AND user_id = :user_id");
        $stmt_delete->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt_delete->bindParam(':user_id', $current_user_id, PDO::PARAM_INT); // Extra check for ownership

        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $_SESSION['shop_message'] = "Product deleted successfully!";
                $_SESSION['shop_message_type'] = "success";
            } else {
                // This case might happen if the product was deleted by another process between fetch and delete,
                // or if the user_id check in WHERE clause failed (though ownership was already checked).
                $_SESSION['shop_message'] = "Could not delete product. It might have already been removed or there was an issue.";
                $_SESSION['shop_message_type'] = "error";
                 error_log("Product deletion from DB failed or affected 0 rows for product ID: " . $product_id . " by user: " . $current_user_id);
            }
        } else {
            $_SESSION['shop_message'] = "Failed to delete product from database due to a query error.";
            $_SESSION['shop_message_type'] = "error";
            error_log("Product deletion DB query failed for product ID: " . $product_id);
        }

    } catch (PDOException $e) {
        error_log("Database error in delete_product.php: " . $e->getMessage());
        $_SESSION['shop_message'] = "An error occurred while trying to delete the product.";
        $_SESSION['shop_message_type'] = "error";
    }
} else {
    $_SESSION['shop_message'] = "Database connection not available.";
    $_SESSION['shop_message_type'] = "error";
}

header('Location: shop_page.php'); // Redirect back to shop page
exit;

?> 
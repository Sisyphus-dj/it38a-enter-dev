<?php
session_start();
include_once 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to add items to your cart.';
    $_SESSION['shop_message_type'] = 'error';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['product_id']) || !filter_var($_POST['product_id'], FILTER_VALIDATE_INT)) {
        $_SESSION['shop_message'] = 'Invalid product specified.';
        $_SESSION['shop_message_type'] = 'error';
        header('Location: shop_page.php'); // Or previous page
        exit;
    }
    $product_id = (int)$_POST['product_id'];
    $quantity = (isset($_POST['quantity']) && filter_var($_POST['quantity'], FILTER_VALIDATE_INT) && $_POST['quantity'] > 0) ? (int)$_POST['quantity'] : 1;

    if (!isset($pdo)) {
        $_SESSION['shop_message'] = 'Database connection error.';
        $_SESSION['shop_message_type'] = 'error';
        header('Location: shop_page.php');
        exit;
    }

    try {
        // 1. Check product existence and stock
        $stmt_product = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = :product_id");
        $stmt_product->execute([':product_id' => $product_id]);
        $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['shop_message'] = 'Product not found.';
            $_SESSION['shop_message_type'] = 'error';
            header('Location: shop_page.php');
            exit;
        }

        // 2. Check if item already in cart
        $stmt_check_cart = $pdo->prepare("SELECT id, quantity FROM shopping_cart_items WHERE user_id = :user_id AND product_id = :product_id");
        $stmt_check_cart->execute([':user_id' => $user_id, ':product_id' => $product_id]);
        $cart_item = $stmt_check_cart->fetch(PDO::FETCH_ASSOC);

        $new_total_quantity = $cart_item ? ($cart_item['quantity'] + $quantity) : $quantity;

        if ($new_total_quantity > $product['stock_quantity']) {
            $_SESSION['shop_message'] = 'Not enough stock for "' . htmlspecialchars($product['name']) . '". Available: ' . $product['stock_quantity'] . ', Requested total: ' . $new_total_quantity . '.';
            $_SESSION['shop_message_type'] = 'warning';
            header('Location: shop_page.php#product-' . $product_id);
            exit;
        }

        if ($cart_item) {
            // Update quantity if item exists
            $stmt_update_cart = $pdo->prepare("UPDATE shopping_cart_items SET quantity = :quantity WHERE id = :id");
            $stmt_update_cart->execute([':quantity' => $new_total_quantity, ':id' => $cart_item['id']]);
        } else {
            // Insert new item if it does not exist
            $stmt_insert_cart = $pdo->prepare("INSERT INTO shopping_cart_items (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            $stmt_insert_cart->execute([':user_id' => $user_id, ':product_id' => $product_id, ':quantity' => $quantity]);
        }

        $_SESSION['shop_message'] = htmlspecialchars($product['name']) . ' (x' . $quantity . ') added to your cart successfully!';
        $_SESSION['shop_message_type'] = 'success';
        header('Location: shop_page.php#product-' . $product_id); // Redirect back, potentially to the product
        exit;

    } catch (PDOException $e) {
        error_log("Error adding to cart: " . $e->getMessage());
        $_SESSION['shop_message'] = 'Could not add item to cart. Please try again.';
        $_SESSION['shop_message_type'] = 'error';
        header('Location: shop_page.php');
        exit;
    }

} else {
    // If not a POST request, redirect away
    header('Location: shop_page.php');
    exit;
}
?> 
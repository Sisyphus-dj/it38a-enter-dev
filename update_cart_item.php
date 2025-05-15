<?php
session_start();
include_once 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    // Not setting a session message here as it might be confusing if direct access attempted
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['cart_item_id']) || !filter_var($_POST['cart_item_id'], FILTER_VALIDATE_INT) ||
        !isset($_POST['quantity']) || !filter_var($_POST['quantity'], FILTER_VALIDATE_INT)) {
        
        $_SESSION['cart_message'] = 'Invalid data provided.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: cart_page.php');
        exit;
    }

    $cart_item_id = (int)$_POST['cart_item_id'];
    $new_quantity = (int)$_POST['quantity'];

    if ($new_quantity <= 0) {
        // If quantity is zero or less, it's effectively a removal.
        // Redirect to remove script or handle removal directly. For simplicity, let's guide to remove.
        // Or, more simply, ensure quantity is at least 1 for an update.
        $_SESSION['cart_message'] = 'Quantity must be at least 1. To remove an item, use the remove button.';
        $_SESSION['cart_message_type'] = 'warning';
        header('Location: cart_page.php#cart-item-' . $cart_item_id);
        exit;
    }

    if (!isset($pdo)) {
        $_SESSION['cart_message'] = 'Database connection error.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: cart_page.php');
        exit;
    }

    try {
        // 1. Verify cart item belongs to user and get product_id and product name for stock check
        $stmt_check = $pdo->prepare("
            SELECT sci.product_id, p.stock_quantity, p.name as product_name 
            FROM shopping_cart_items sci
            JOIN products p ON sci.product_id = p.id
            WHERE sci.id = :cart_item_id AND sci.user_id = :user_id
        ");
        $stmt_check->execute([':cart_item_id' => $cart_item_id, ':user_id' => $user_id]);
        $item_info = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$item_info) {
            $_SESSION['cart_message'] = 'Cart item not found or access denied.';
            $_SESSION['cart_message_type'] = 'error';
            header('Location: cart_page.php');
            exit;
        }

        // 2. Check against stock
        if ($new_quantity > $item_info['stock_quantity']) {
            $_SESSION['cart_message'] = 'Not enough stock for "' . htmlspecialchars($item_info['product_name']) . '". Requested: ' . $new_quantity . ', Available: ' . $item_info['stock_quantity'] . '.';
            $_SESSION['cart_message_type'] = 'warning';
            header('Location: cart_page.php#cart-item-' . $cart_item_id);
            exit;
        }

        // 3. Update quantity
        $stmt_update = $pdo->prepare("UPDATE shopping_cart_items SET quantity = :quantity WHERE id = :cart_item_id");
        $stmt_update->execute([':quantity' => $new_quantity, ':cart_item_id' => $cart_item_id]);

        $_SESSION['cart_message'] = 'Quantity for "' . htmlspecialchars($item_info['product_name']) . '" updated successfully.';
        $_SESSION['cart_message_type'] = 'success';
        header('Location: cart_page.php#cart-item-' . $cart_item_id);
        exit;

    } catch (PDOException $e) {
        error_log("Error updating cart item: " . $e->getMessage());
        $_SESSION['cart_message'] = 'Could not update cart item. Please try again.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: cart_page.php');
        exit;
    }

} else {
    // If not a POST request, redirect away
    header('Location: cart_page.php');
    exit;
}
?> 
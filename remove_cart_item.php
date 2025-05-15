<?php
session_start();
include_once 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['cart_item_id']) || !filter_var($_POST['cart_item_id'], FILTER_VALIDATE_INT)) {
        $_SESSION['cart_message'] = 'Invalid cart item specified.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: cart_page.php');
        exit;
    }

    $cart_item_id = (int)$_POST['cart_item_id'];

    if (!isset($pdo)) {
        $_SESSION['cart_message'] = 'Database connection error.';
        $_SESSION['cart_message_type'] = 'error';
        header('Location: cart_page.php');
        exit;
    }

    try {
        // To provide a more specific message, get the product name before deleting
        $stmt_get_name = $pdo->prepare("
            SELECT p.name as product_name 
            FROM shopping_cart_items sci
            JOIN products p ON sci.product_id = p.id
            WHERE sci.id = :cart_item_id AND sci.user_id = :user_id
        ");
        $stmt_get_name->execute([':cart_item_id' => $cart_item_id, ':user_id' => $user_id]);
        $item_info = $stmt_get_name->fetch(PDO::FETCH_ASSOC);
        $product_name = $item_info ? $item_info['product_name'] : 'Item';

        // Delete the item, ensuring it belongs to the logged-in user
        $stmt_delete = $pdo->prepare("DELETE FROM shopping_cart_items WHERE id = :cart_item_id AND user_id = :user_id");
        $stmt_delete->execute([':cart_item_id' => $cart_item_id, ':user_id' => $user_id]);

        if ($stmt_delete->rowCount() > 0) {
            $_SESSION['cart_message'] = htmlspecialchars($product_name) . ' removed from your cart.';
            $_SESSION['cart_message_type'] = 'success';
        } else {
            // This case could happen if the item was already removed or didn't belong to user
            $_SESSION['cart_message'] = 'Could not remove item. It might have already been removed or was not found.';
            $_SESSION['cart_message_type'] = 'warning';
        }
        header('Location: cart_page.php');
        exit;

    } catch (PDOException $e) {
        error_log("Error removing cart item: " . $e->getMessage());
        $_SESSION['cart_message'] = 'Could not remove item from cart. Please try again.';
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
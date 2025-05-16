<?php
session_start();
include_once 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to cancel orders.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Validate order ID
if (!isset($_POST['order_id']) || !filter_var($_POST['order_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['shop_message'] = 'Invalid order ID.';
    $_SESSION['shop_message_type'] = 'error';
    header('Location: order_history.php');
    exit;
}

$order_id = (int)$_POST['order_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if order exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT order_status 
        FROM orders 
        WHERE id = :order_id AND user_id = :user_id
    ");
    $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or you do not have permission to cancel it.');
    }

    // Check if order can be cancelled (only pending or processing orders)
    if (!in_array(strtolower($order['order_status']), ['pending', 'processing', 'pending gcash payment'])) {
        throw new Exception('This order cannot be cancelled as it is already being processed or has been completed.');
    }

    // Get order items to restore stock
    $stmt = $pdo->prepare("
        SELECT product_id, quantity 
        FROM order_items 
        WHERE order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Restore stock quantities
    $stmt = $pdo->prepare("
        UPDATE products 
        SET stock_quantity = stock_quantity + :quantity 
        WHERE id = :product_id
    ");
    foreach ($order_items as $item) {
        $stmt->execute([
            ':quantity' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }

    // Update order status to cancelled
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET order_status = 'Cancelled' 
        WHERE id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['shop_message'] = 'Order has been cancelled successfully.';
    $_SESSION['shop_message_type'] = 'success';

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['shop_message'] = 'Error cancelling order: ' . $e->getMessage();
    $_SESSION['shop_message_type'] = 'error';
}

header('Location: order_history.php');
exit; 
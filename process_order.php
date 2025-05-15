<?php
session_start();
include_once 'db_connection.php';

// 1. Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to place an order.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php?redirect=checkout_page.php');
    exit;
}
$user_id = $_SESSION['user']['id'];

// 2. Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout_page.php');
    exit;
}

// 3. Database Connection Check
if (!isset($pdo)) {
    $_SESSION['checkout_message'] = 'Database connection error. Cannot process order.';
    $_SESSION['checkout_message_type'] = 'error';
    header('Location: checkout_page.php');
    exit;
}

// 4. Validate Shipping Details & Payment Method
$required_fields = [
    'shipping_name', 'shipping_address_line1', 'shipping_city',
    'shipping_state', 'shipping_postal_code', 'shipping_country',
    'payment_method' // Added payment_method
];
$form_data = []; // Will hold all validated form data
$errors = [];

foreach ($required_fields as $field) {
    if (empty(trim($_POST[$field]))) {
        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    } else {
        $form_data[$field] = trim($_POST[$field]);
    }
}
// Optional field
$form_data['shipping_address_line2'] = isset($_POST['shipping_address_line2']) ? trim($_POST['shipping_address_line2']) : '';

// Validate payment method value
$allowed_payment_methods = ['cod', 'gcash'];
if (isset($form_data['payment_method']) && !in_array($form_data['payment_method'], $allowed_payment_methods)) {
    $errors['payment_method'] = 'Invalid payment method selected.';
}

if (!empty($errors)) {
    $_SESSION['checkout_form_data'] = $_POST; // Repopulate form
    $_SESSION['checkout_message'] = "Please correct the errors below.";
    $_SESSION['checkout_message_type'] = 'error';
    $_SESSION['checkout_form_errors'] = $errors; 
    header('Location: checkout_page.php');
    exit;
}

// Determine order status based on payment method
$order_status = 'pending'; // Default
if ($form_data['payment_method'] === 'cod') {
    $order_status = 'Processing'; // Or 'Awaiting COD Confirmation', 'Awaiting Shipment'
} elseif ($form_data['payment_method'] === 'gcash') {
    $order_status = 'Pending GCash Payment';
}

try {
    // 5. Fetch Cart Items (Re-validation) & Calculate Total
    $stmt_cart = $pdo->prepare("
        SELECT sci.quantity, p.id as product_id, p.name as product_name, p.price as product_price, p.stock_quantity
        FROM shopping_cart_items sci
        JOIN products p ON sci.product_id = p.id
        WHERE sci.user_id = :user_id
    ");
    $stmt_cart->execute([':user_id' => $user_id]);
    $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        $_SESSION['cart_message'] = 'Your cart is empty. Cannot place order.';
        $_SESSION['cart_message_type'] = 'warning';
        header('Location: cart_page.php');
        exit;
    }

    $current_cart_total = 0;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            throw new Exception('Product "' . htmlspecialchars($item['product_name']) . '" does not have enough stock (' . $item['quantity'] . ' requested, ' . $item['stock_quantity'] . ' available).');
        }
        $current_cart_total += $item['product_price'] * $item['quantity'];
    }

    // 6. Database Transaction
    $pdo->beginTransaction();

    // 7. Create Order Record
    $stmt_order = $pdo->prepare("
        INSERT INTO orders (user_id, total_amount, order_status, 
                            shipping_name, shipping_address_line1, shipping_address_line2, 
                            shipping_city, shipping_state, shipping_postal_code, shipping_country, 
                            payment_method) 
        VALUES (:user_id, :total_amount, :order_status, 
                :shipping_name, :shipping_address_line1, :shipping_address_line2, 
                :shipping_city, :shipping_state, :shipping_postal_code, :shipping_country, 
                :payment_method)
    ");
    $stmt_order->execute([
        ':user_id' => $user_id,
        ':total_amount' => $current_cart_total,
        ':order_status' => $order_status, // Use determined status
        ':shipping_name' => $form_data['shipping_name'],
        ':shipping_address_line1' => $form_data['shipping_address_line1'],
        ':shipping_address_line2' => $form_data['shipping_address_line2'],
        ':shipping_city' => $form_data['shipping_city'],
        ':shipping_state' => $form_data['shipping_state'],
        ':shipping_postal_code' => $form_data['shipping_postal_code'],
        ':shipping_country' => $form_data['shipping_country'],
        ':payment_method' => $form_data['payment_method']
    ]);
    $order_id = $pdo->lastInsertId();

    // 8. Process Order Items & Update Stock
    $stmt_order_item = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, subtotal)
        VALUES (:order_id, :product_id, :quantity, :price_at_purchase, :subtotal)
    ");
    $stmt_update_stock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :quantity WHERE id = :product_id");

    foreach ($cart_items as $item) {
        $subtotal = $item['product_price'] * $item['quantity'];
        $stmt_order_item->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price_at_purchase' => $item['product_price'],
            ':subtotal' => $subtotal
        ]);
        
        $stmt_update_stock->execute([
            ':quantity' => $item['quantity'],
            ':product_id' => $item['product_id']
        ]);
    }

    // 9. Clear Shopping Cart
    $stmt_clear_cart = $pdo->prepare("DELETE FROM shopping_cart_items WHERE user_id = :user_id");
    $stmt_clear_cart->execute([':user_id' => $user_id]);

    // 10. Commit Transaction
    $pdo->commit();

    // 11. Redirection
    $_SESSION['order_confirmation_id'] = $order_id;
    // Potentially redirect to a different page or show different info if GCash was selected
    if ($form_data['payment_method'] === 'gcash') {
        // For GCash, you might redirect to a page with GCash payment instructions or an API call point
        // For now, we'll go to the standard confirmation page.
        // $_SESSION['show_gcash_instructions'] = true; // Example flag for order_confirmation.php
    }
    header('Location: order_confirmation.php');
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Order processing error: " . $e->getMessage());
    $_SESSION['checkout_message'] = "Error processing your order: " . $e->getMessage();
    $_SESSION['checkout_message_type'] = 'error';
    $_SESSION['checkout_form_data'] = $_POST; 
    header('Location: checkout_page.php');
    exit;
}
?> 
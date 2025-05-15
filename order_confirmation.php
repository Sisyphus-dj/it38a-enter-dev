<?php
session_start();
include_once 'db_connection.php';

// 1. Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['id'];

// 2. Check for Order ID in session
if (!isset($_SESSION['order_confirmation_id'])) {
    $_SESSION['shop_message'] = 'No order to confirm.'; // Or a more generic message
    $_SESSION['shop_message_type'] = 'info';
    header('Location: shop_page.php'); // Redirect to shop or dashboard
    exit;
}
$order_id = $_SESSION['order_confirmation_id'];

// 3. Database Connection Check
if (!isset($pdo)) {
    // This is a critical page, so an error here is significant.
    // Consider a more user-friendly error page or a simple die() with a message.
    die('Database connection error. Cannot display order confirmation.'); 
}

$order_details = null;
$ordered_items = [];
$page_error = null;

try {
    // 4. Fetch Order Details
    $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id");
    $stmt_order->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order_details = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order_details) {
        $page_error = "Order not found or you do not have permission to view it.";
    } else {
        // 5. Fetch Ordered Items
        $stmt_items = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image_path
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $stmt_items->execute([':order_id' => $order_id]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. Clear the order confirmation ID from session to prevent re-showing on refresh
    unset($_SESSION['order_confirmation_id']);

} catch (PDOException $e) {
    error_log("Error fetching order confirmation details: " . $e->getMessage());
    $page_error = "Could not load your order confirmation. Please contact support if this issue persists.";
    // Do not unset session order_confirmation_id here, so they might retry or we can debug
}

$user = $_SESSION['user']; // For navigation menu

// Load settings
$settings = [];
$stmt = $pdo->query('SELECT * FROM settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?></title>
    <link rel="stylesheet" href="user_dashboard.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .confirmation-header {
            text-align: center;
            padding: 20px;
            background-color: #d4edda; /* Light green for success */
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .confirmation-header h1 {
            margin: 0;
            font-size: 1.8em;
        }
        .order-details-section, .shipping-details-section, .items-ordered-section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .order-details-section h2, .shipping-details-section h2, .items-ordered-section h2 {
            font-size: 1.3em;
            margin-top: 0;
            margin-bottom: 15px;
            color: #007bff;
        }
        .order-details-section p, .shipping-details-section p {
            margin: 5px 0;
            line-height: 1.6;
        }
        .order-details-section strong, .shipping-details-section strong {
            display: inline-block;
            min-width: 120px; /* For alignment */
        }
        .ordered-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            padding: 10px 0;
            font-size: 0.9em;
        }
        .ordered-item:last-child {
            border-bottom: none;
        }
        .ordered-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        .ordered-item-details {
            flex-grow: 1;
        }
        .ordered-item-details h5 {
            margin: 0 0 3px 0;
            font-size: 1em;
        }
        .ordered-item-price, .ordered-item-quantity, .ordered-item-subtotal {
            color: #555;
            font-size: 0.9em;
        }
        .ordered-item-subtotal {
            min-width: 60px;
            text-align: right;
            font-weight: bold;
        }
        .confirmation-actions {
            margin-top: 30px;
            text-align: center;
        }
        .confirmation-actions a {
            margin: 0 10px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-continue-shopping {
            background-color: #007bff;
            color: white;
        }
        .btn-view-orders {
            background-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
        <a href="user_dashboard.php" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="user_dashboard.php">Home</a></li>
            <li><a href="shop_page.php" class="shop-btn">Shop</a></li>
            <li><a href="cart_page.php">Cart</a></li>
            <?php if ($user): ?>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                    <!-- <a href="order_history.php">Order History</a>  Future link -->
                </div>
            </li>
            <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="confirmation-container">
        <?php if ($page_error): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php elseif ($order_details && !empty($ordered_items)): ?>
            <div class="confirmation-header">
                <h1>Thank You For Your Order from <?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?>!</h1>
                <p>Your order has been placed successfully.</p>
                <p>If you have questions, contact us at <a href="mailto:<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"><?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?></a></p>
            </div>

            <div class="order-details-section">
                <h2>Order Details</h2>
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order_details['id']); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order_details['order_date']))); ?></p>
                <p><strong>Order Status:</strong> <?php echo htmlspecialchars(ucfirst($order_details['order_status'])); ?></p>
                <p><strong>Total Amount:</strong> ₱<?php echo htmlspecialchars(number_format($order_details['total_amount'], 2)); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['payment_method'] ?? 'N/A'); ?></p>
                <?php if (($order_details['payment_method'] ?? '') == 'gcash'): ?>
                <p><strong>GCash Number:</strong> <?php echo htmlspecialchars($settings['gcash_number'] ?? ''); ?></p>
                <?php endif; ?>
            </div>

            <div class="shipping-details-section">
                <h2>Shipping Address</h2>
                <p><strong><?php echo htmlspecialchars($order_details['shipping_name']); ?></strong></p>
                <p><?php echo htmlspecialchars($order_details['shipping_address_line1']); ?></p>
                <?php if (!empty($order_details['shipping_address_line2'])): ?>
                    <p><?php echo htmlspecialchars($order_details['shipping_address_line2']); ?></p>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($order_details['shipping_city'] . ', ' . $order_details['shipping_state'] . ' ' . $order_details['shipping_postal_code']); ?></p>
                <p><?php echo htmlspecialchars($order_details['shipping_country']); ?></p>
            </div>

            <div class="items-ordered-section">
                <h2>Items Ordered</h2>
                <?php foreach ($ordered_items as $item): ?>
                    <div class="ordered-item">
                        <img src="<?php echo htmlspecialchars(!empty($item['image_path']) ? $item['image_path'] : 'placeholder-image.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        <div class="ordered-item-details">
                            <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            <span class="ordered-item-price">Price: ₱<?php echo htmlspecialchars(number_format($item['price_at_purchase'], 2)); ?></span>
                            <span class="ordered-item-quantity"> | Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                        </div>
                        <div class="ordered-item-subtotal">
                            ₱<?php echo htmlspecialchars(number_format($item['subtotal'], 2)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="confirmation-actions">
                <a href="shop_page.php" class="btn-continue-shopping">Continue Shopping</a>
                <!-- <a href="order_history.php" class="btn-view-orders">View Order History</a> Future link -->
            </div>

        <?php else: ?>
            <div class="feed-alert feed-alert-info">Could not retrieve order details. If you just placed an order, please check your email or contact support.</div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
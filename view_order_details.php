<?php
session_start();
include_once 'db_connection.php';

// 1. Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to view order details.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php?redirect=order_history.php');
    exit;
}
$user_id = $_SESSION['user']['id'];

// 2. Get and Validate Order ID from GET parameter
if (!isset($_GET['order_id']) || !filter_var($_GET['order_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['shop_message'] = 'Invalid order ID specified.'; // Or a message for order_history page
    $_SESSION['shop_message_type'] = 'error';
    header('Location: order_history.php');
    exit;
}
$order_id = (int)$_GET['order_id'];

// 3. Database Connection Check
if (!isset($pdo)) {
    die('Database connection error. Cannot display order details.');
}

$order_details = null;
$ordered_items = [];
$page_error = null;

try {
    // 4. Fetch Order Details (ensure it belongs to the logged-in user)
    $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id");
    $stmt_order->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order_details = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order_details) {
        $page_error = "Order not found or you do not have permission to view this order.";
    } else {
        // 5. Fetch Ordered Items for this specific order
        $stmt_items = $pdo->prepare("
            SELECT oi.*, p.name as product_name, p.image_path
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $stmt_items->execute([':order_id' => $order_id]);
        $ordered_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    $page_error = "Could not load your order details. Please contact support if this issue persists.";
}

$user = $_SESSION['user']; // For navigation menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - AgriSync</title>
    <link rel="stylesheet" href="user_dashboard.css">
    <!-- Reusing styles from order_confirmation.php, slightly adjusted -->
    <style>
        .order-view-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .order-view-header {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .order-view-header h1 {
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
            min-width: 130px; /* For alignment */
            color: #333;
        }
        .status-value {
            font-weight: bold;
        }
        /* Status specific colors - can be inherited from order_history or defined here */
        .status-pending, .status-pending-gcash-payment { color: #ffc107; }
        .status-processing, .status-awaiting-shipment { color: #007bff; }
        .status-shipped { color: #17a2b8; }
        .status-delivered { color: #28a745; }
        .status-cancelled, .status-payment-failed { color: #dc3545; }

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
        .order-view-actions {
            margin-top: 30px;
            text-align: center;
        }
        .order-view-actions a {
            margin: 0 10px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
        }
        .order-view-actions a:hover {
            background-color: #0056b3;
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
                    <a href="order_history.php">Order History</a>
                </div>
            </li>
            <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="order-view-container">
        <div class="order-view-header">
            <h1>Order Details</h1>
        </div>

        <?php if (isset($_SESSION['order_view_message'])): // For messages specific to this page ?>
            <div class="feed-alert feed-alert-<?php echo $_SESSION['order_view_message_type'] ?? 'info'; ?>">
                <?php echo htmlspecialchars($_SESSION['order_view_message']); ?>
            </div>
            <?php 
                unset($_SESSION['order_view_message']);
                unset($_SESSION['order_view_message_type']);
            ?>
        <?php endif; ?>

        <?php if ($page_error): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php elseif ($order_details && !empty($ordered_items)): 
            $status_class = 'status-' . strtolower(str_replace(' ', '-', $order_details['order_status']));    
        ?>
            <div class="order-details-section">
                <h2>General Information</h2>
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order_details['id']); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order_details['order_date']))); ?></p>
                <p><strong>Order Status:</strong> <span class="status-value <?php echo htmlspecialchars($status_class); ?>"><?php echo htmlspecialchars(ucfirst($order_details['order_status'])); ?></span></p>
                <p><strong>Total Amount:</strong> $<?php echo htmlspecialchars(number_format($order_details['total_amount'], 2)); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['payment_method'] ?? 'N/A'); ?></p>
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
                            <span class="ordered-item-price">Price Paid: $<?php echo htmlspecialchars(number_format($item['price_at_purchase'], 2)); ?></span>
                            <span class="ordered-item-quantity"> | Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                        </div>
                        <div class="ordered-item-subtotal">
                            $<?php echo htmlspecialchars(number_format($item['subtotal'], 2)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-view-actions">
                <a href="order_history.php">Back to Order History</a>
            </div>

        <?php else: // Should not happen if order ID was valid and belonged to user, unless items are somehow missing
        ?>
            <div class="feed-alert feed-alert-warning">Order details are currently unavailable. Please try again later or contact support.</div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
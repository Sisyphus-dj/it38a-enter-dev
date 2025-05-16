<?php
session_start();
include_once 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to view order details.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Validate order ID
if (!isset($_GET['order_id']) || !filter_var($_GET['order_id'], FILTER_VALIDATE_INT)) {
    $_SESSION['shop_message'] = 'Invalid order ID.';
    $_SESSION['shop_message_type'] = 'error';
    header('Location: order_history.php');
    exit;
}

$order_id = (int)$_GET['order_id'];
$order = null;
$order_items = [];
$page_error = null;

try {
    // Fetch order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.email as user_email, u.first_name, u.last_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = :order_id AND o.user_id = :user_id
    ");
    $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or you do not have permission to view it.');
    }

    // Fetch order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image_path
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $page_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - AgriSync</title>
    <link rel="stylesheet" href="user_dashboard.css">
    <style>
        .order-details-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .order-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        .order-section h2 {
            color: #4e944f;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4e944f;
        }
        .order-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .order-info p {
            margin: 5px 0;
        }
        .order-info strong {
            color: #333;
        }
        .order-items {
            margin-top: 20px;
        }
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .item-details {
            flex-grow: 1;
        }
        .item-details h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .item-price, .item-quantity {
            color: #666;
            font-size: 0.9em;
        }
        .item-subtotal {
            font-weight: bold;
            color: #4e944f;
        }
        .order-total {
            text-align: right;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #4e944f;
        }
        .order-total h3 {
            color: #333;
            margin: 0;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
        }
        .btn-cancel:hover {
            background-color: #c82333;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
        }
        .status-pending, .status-pending-gcash-payment { background-color: #ffc107; }
        .status-processing { background-color: #007bff; }
        .status-shipped { background-color: #17a2b8; }
        .status-delivered { background-color: #28a745; }
        .status-cancelled { background-color: #dc3545; }
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
            <li><a href="order_history.php" style="font-weight: bold; color: #4e944f;">Orders</a></li>
            <?php if ($user): ?>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
            <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="order-details-container">
        <?php if ($page_error): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php elseif ($order): ?>
            <div class="order-section">
                <h2>Order #<?php echo htmlspecialchars($order['id']); ?></h2>
                <div class="order-info">
                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></p>
                    <p><strong>Status:</strong> <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['order_status'])); ?>"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></span></p>
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
                    <p><strong>Total Amount:</strong> ₱<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></p>
                </div>
            </div>

            <div class="order-section">
                <h2>Shipping Information</h2>
                <div class="order-info">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address_line1']); ?></p>
                    <?php if (!empty($order['shipping_address_line2'])): ?>
                        <p><strong>Address Line 2:</strong> <?php echo htmlspecialchars($order['shipping_address_line2']); ?></p>
                    <?php endif; ?>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                    <p><strong>State/Province:</strong> <?php echo htmlspecialchars($order['shipping_state']); ?></p>
                    <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                    <p><strong>Country:</strong> <?php echo htmlspecialchars($order['shipping_country']); ?></p>
                </div>
            </div>

            <div class="order-section">
                <h2>Order Items</h2>
                <div class="order-items">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars(!empty($item['image_path']) ? $item['image_path'] : 'placeholder-image.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <span class="item-price">₱<?php echo htmlspecialchars(number_format($item['price_at_purchase'], 2)); ?></span>
                                <span class="item-quantity"> × <?php echo htmlspecialchars($item['quantity']); ?></span>
                            </div>
                            <div class="item-subtotal">
                                ₱<?php echo htmlspecialchars(number_format($item['subtotal'], 2)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="order-total">
                    <h3>Total: ₱<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></h3>
                </div>
            </div>

            <?php if (in_array(strtolower($order['order_status']), ['pending', 'processing', 'pending gcash payment'])): ?>
                <form action="cancel_order.php" method="POST" style="text-align: center;">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                    <button type="submit" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
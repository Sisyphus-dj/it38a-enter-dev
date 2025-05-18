<?php
session_start();
include_once 'db_connection.php';

// Ensure only sellers can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$orders = [];

if (isset($pdo)) {
    try {
        // Fetch orders for seller's products
        $stmt = $pdo->prepare("
            SELECT 
                o.id as order_id,
                o.order_date,
                o.status as order_status,
                oi.quantity,
                oi.price,
                p.name as product_name,
                u.first_name,
                u.last_name,
                u.email
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            JOIN users u ON o.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY o.order_date DESC
        ");
        $stmt->execute([$user['id']]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching seller orders: " . $e->getMessage());
        $error = "Could not load orders. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - AgriSync Marketplace</title>
    <link rel="stylesheet" href="user_dashboard.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        .order-details {
            margin-top: 10px;
        }
        .order-details p {
            margin: 5px 0;
            color: #666;
        }
        .order-total {
            margin-top: 15px;
            text-align: right;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
        <a href="user_dashboard.php" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="user_dashboard.php">Dashboard</a></li>
            <li><a href="shop_page.php">Shop</a></li>
            <li><a href="cart_page.php">Cart</a></li>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="nav-spacer"></div>

    <div class="orders-container">
        <h1>Your Orders</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <p>No orders found.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo htmlspecialchars($order['order_id']); ?></h3>
                            <p>Date: <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                        </div>
                        <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                    <div class="order-details">
                        <p><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?></p>
                        <p><strong>Price per unit:</strong> ₱<?php echo number_format($order['price'], 2); ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>Customer Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                    </div>
                    <div class="order-total">
                        Total: ₱<?php echo number_format($order['quantity'] * $order['price'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
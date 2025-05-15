<?php
session_start();
include_once 'db_connection.php';

// 1. Ensure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to view your order history.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php?redirect=order_history.php');
    exit;
}
$user_id = $_SESSION['user']['id'];

// 2. Database Connection Check
if (!isset($pdo)) {
    die('Database connection error. Cannot display order history.');
}

$orders = [];
$page_error = null;

try {
    // 3. Fetch User's Orders
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC");
    $stmt->execute([':user_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching order history: " . $e->getMessage());
    $page_error = "Could not load your order history. Please try again later.";
}

$user = $_SESSION['user']; // For navigation menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - AgriSync</title>
    <link rel="stylesheet" href="user_dashboard.css"> 
    <style>
        .order-history-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .order-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .order-history-table th, .order-history-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .order-history-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .order-history-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .order-history-table a {
            color: #007bff;
            text-decoration: none;
        }
        .order-history-table a:hover {
            text-decoration: underline;
        }
        .status-pending, .status-pending-gcash-payment {
            color: #ffc107; /* Amber */
            font-weight: bold;
        }
        .status-processing, .status-awaiting-shipment {
            color: #007bff; /* Blue */
            font-weight: bold;
        }
        .status-shipped {
            color: #17a2b8; /* Teal */
            font-weight: bold;
        }
        .status-delivered {
            color: #28a745; /* Green */
            font-weight: bold;
        }
        .status-cancelled, .status-payment-failed {
            color: #dc3545; /* Red */
            font-weight: bold;
        }
        .no-orders-message {
            text-align: center;
            padding: 30px;
            font-size: 1.2em;
            color: #777;
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
                    <a href="order_history.php" style="font-weight:bold;">Order History</a>
                </div>
            </li>
            <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="order-history-container">
        <h1>Your Order History</h1>

        <?php if ($page_error): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php endif; ?>

        <?php if (empty($orders) && !$page_error): ?>
            <div class="no-orders-message">
                <p>You haven't placed any orders yet.</p>
                <a href="shop_page.php" class="btn-add-product" style="font-size: 1em;">Start Shopping</a>
            </div>
        <?php elseif (!empty($orders)): ?>
            <table class="order-history-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $status_class = 'status-' . strtolower(str_replace(' ', '-', $order['order_status']));
                    ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars(date("M j, Y", strtotime($order['order_date']))); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                            <td class="<?php echo htmlspecialchars($status_class); ?>"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></td>
                            <td><a href="view_order_details.php?order_id=<?php echo htmlspecialchars($order['id']); ?>">View Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
<?php
session_start();
include_once 'db_connection.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['id'])) {
    die('Order ID not specified.');
}
$order_id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT o.*, u.first_name, u.last_name, u.email, oi.quantity, p.name as product_name, p.price as product_price, s.first_name as seller_first_name, s.last_name as seller_last_name FROM orders o JOIN users u ON o.user_id = u.id JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id JOIN users s ON p.seller_id = s.id WHERE o.id = ?');
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die('Order not found.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F8CFF;
            --secondary-color: #232946;
            --background-color: #16161a;
            --card-bg: #232946;
            --text-color: #eaeaea;
            --border-color: #2e2e3a;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        body {
            background: var(--background-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .order-details-container {
            max-width: 600px;
            margin: 40px auto;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 32px;
        }
        h2 {
            color: var(--primary-color);
            margin-bottom: 24px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
        }
        .details-table th, .details-table td {
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .details-table th {
            color: #b8c1ec;
            width: 160px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
            background: #222;
        }
        .status-pending { background-color: #facc15; color: #232946; }
        .status-processing { background-color: #38bdf8; color: #232946; }
        .status-shipped { background-color: #4F8CFF; color: #fff; }
        .status-delivered { background-color: #22c55e; color: #fff; }
        .status-cancelled { background-color: #ef4444; color: #fff; }
        .back-btn {
            display: inline-block;
            margin-top: 24px;
            padding: 10px 20px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
        }
        .back-btn:hover {
            background: var(--info);
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <h2>Order #<?php echo $order['id']; ?> Details</h2>
        <table class="details-table">
            <tr><th>Customer</th><td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</td></tr>
            <tr><th>Product</th><td><?php echo htmlspecialchars($order['product_name']); ?></td></tr>
            <tr><th>Seller</th><td><?php echo htmlspecialchars($order['seller_first_name'] . ' ' . $order['seller_last_name']); ?></td></tr>
            <tr><th>Quantity</th><td><?php echo $order['quantity']; ?></td></tr>
            <tr><th>Total Amount</th><td>$<?php echo number_format($order['total_amount'], 2); ?></td></tr>
            <tr><th>Status</th><td><span class="status-badge status-<?php echo strtolower($order['order_status']); ?>"><?php echo ucfirst($order['order_status']); ?></span></td></tr>
            <tr><th>Order Date</th><td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td></tr>
            <tr><th>Shipping Name</th><td><?php echo htmlspecialchars($order['shipping_name']); ?></td></tr>
            <tr><th>Shipping Address</th><td><?php echo htmlspecialchars($order['shipping_address_line1'] . ' ' . $order['shipping_address_line2'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_state'] . ' ' . $order['shipping_postal_code'] . ', ' . $order['shipping_country']); ?></td></tr>
        </table>
        <a href="manage_orders.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Manage Orders</a>
    </div>
</body>
</html> 
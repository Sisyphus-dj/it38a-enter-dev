<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user']; 

// Fetch basic statistics
$stmt = $pdo->query('SELECT COUNT(*) as total_users FROM users');
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $pdo->query('SELECT COUNT(*) as total_products FROM products');
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

$stmt = $pdo->query('SELECT COUNT(*) as total_orders FROM orders');
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

// Fetch recent orders
$stmt = $pdo->query('SELECT o.*, u.first_name, u.last_name 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.order_date DESC 
                     LIMIT 5');
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch low stock products
$stmt = $pdo->query('SELECT p.*, u.email as seller_email, u.first_name, u.last_name 
                     FROM products p 
                     JOIN users u ON p.seller_id = u.id 
                     WHERE p.stock_quantity <= 5 
                     ORDER BY p.stock_quantity ASC');
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch orders by status
$stmt = $pdo->query('SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status');
$orders_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total revenue
$stmt = $pdo->query('SELECT SUM(total_amount) as total_revenue FROM orders');
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Calculate average order value
$stmt = $pdo->query('SELECT AVG(total_amount) as avg_order_value FROM orders');
$avg_order_value = $stmt->fetch(PDO::FETCH_ASSOC)['avg_order_value'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgriSync</title>
    <link rel="stylesheet" href="styles.css"> 
    <style>
        :root {
            --primary-color: #4e944f;
            --secondary-color: #357a38;
            --background-color: #f5f5f5;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .admin-nav a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .dashboard-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-box h3 {
            margin-top: 0;
            color: var(--text-color);
        }

        .stat-box p {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-color);
        }

        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .quick-actions a {
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .quick-actions a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending { background-color: #ffd700; }
        .status-processing { background-color: #87ceeb; }
        .status-shipped { background-color: #98fb98; }
        .status-delivered { background-color: #90ee90; }

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .section-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-card h3 {
            margin-top: 0;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .section-card ul {
            list-style-type: none;
            padding: 0;
        }

        .section-card li {
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .section-card li:last-child {
            border-bottom: none;
        }

        .section-card a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .section-card a:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
        </div>

        <div class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_products.php">Manage Products</a>
            <a href="admin_orders.php">Manage Orders</a>
            <a href="admin_content.php">Content Management</a>
            <a href="admin_settings.php">Settings</a>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <h3>Total Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>
            <div class="stat-box">
                <h3>Total Products</h3>
                <p><?php echo $total_products; ?></p>
            </div>
            <div class="stat-box">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
            <div class="stat-box">
                <h3>Total Revenue</h3>
                <p>$<?php echo number_format($total_revenue, 2); ?></p>
            </div>
        </div>

        <div class="quick-actions">
            <a href="admin_orders.php">View All Orders</a>
            <a href="admin_users.php">Manage Users</a>
        </div>

        <div class="dashboard-section">
            <h2>Recent Orders</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="dashboard-section">
            <h2>Low Stock Alert</h2>
            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Current Stock</th>
                        <th>Seller</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['seller_email']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" style="color: var(--primary-color); text-decoration: none;">Logout</a>
        </div>
    </div>
</body>
</html>
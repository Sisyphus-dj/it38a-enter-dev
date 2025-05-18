<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user']; 

// Fetch basic statistics (exclude cancelled orders)
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders WHERE order_status != 'cancelled'");
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

// Calculate total revenue (exclude cancelled)
$stmt = $pdo->query("SELECT SUM(total_amount) as total_revenue FROM orders WHERE order_status != 'cancelled'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Calculate average order value (exclude cancelled)
$stmt = $pdo->query("SELECT AVG(total_amount) as avg_order_value FROM orders WHERE order_status != 'cancelled'");
$avg_order_value = $stmt->fetch(PDO::FETCH_ASSOC)['avg_order_value'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgriSync</title>
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
            --hover-shadow: 0 4px 16px rgba(0,0,0,0.35);
            --success: #22c55e;
            --warning: #facc15;
            --info: #38bdf8;
            --danger: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--card-bg);
            padding: 20px 0;
            box-shadow: var(--card-shadow);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .sidebar-menu li span {
            display: none;
        }

        .sidebar.collapsed .sidebar-menu li {
            text-align: center;
            padding: 15px 0;
        }

        .sidebar.collapsed .sidebar-menu li i {
            margin: 0;
            font-size: 1.2rem;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-header h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            padding: 10px 20px;
            margin: 5px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            color: var(--text-color);
        }

        .sidebar-menu li:hover,
        .sidebar-menu li.active {
            background: #1a1a2e;
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-menu li i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .dashboard-header {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .dashboard-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #1a1a2e;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            color: var(--text-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-card h3 {
            color: #b8c1ec;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .dashboard-section {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }

        .section-header h2 {
            color: var(--text-color);
            font-size: 1.2rem;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1a1a2e;
            color: var(--text-color);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: #232946;
            font-weight: 600;
        }

        tr:hover {
            background-color: #232946;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background-color: var(--warning); color: #232946; }
        .status-processing { background-color: var(--info); color: #232946; }
        .status-shipped { background-color: var(--primary-color); color: #fff; }
        .status-delivered { background-color: var(--success); color: #fff; }
        .status-cancelled { background-color: var(--danger); color: #fff; }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            background: var(--primary-color);
            color: #fff;
        }

        .action-btn:hover {
            background: var(--info);
        }

        .view-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .view-btn:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .sidebar .sidebar-header h2,
            .sidebar .sidebar-menu li span {
                display: none;
            }
            .sidebar .sidebar-menu li {
                text-align: center;
                padding: 15px 0;
            }
            .sidebar .sidebar-menu li i {
                margin: 0;
                font-size: 1.2rem;
            }
            .main-content {
                margin-left: 70px;
            }
            .toggle-sidebar {
                display: none;
            }
        }

        .dashboard-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px;
        }

        .section-header i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        @media (min-width: 1200px) {
            .dashboard-grid {
                flex-direction: row;
            }
            .dashboard-section {
                width: 100%;
            }
        }

        .toggle-sidebar {
            position: relative;
            background: transparent;
            color: var(--primary-color);
            border: none;
            padding: 15px;
            width: 100%;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .toggle-sidebar:hover {
            background: #f8f9fa;
        }

        .toggle-sidebar i {
            font-size: 1.2rem;
            margin-right: 10px;
        }

        .sidebar.collapsed .toggle-sidebar i {
            margin: 0;
        }

        /* Settings menu styles */
        .settings-menu {
            display: none;
            padding: 10px 0;
            background: #f8f9fa;
        }

        .settings-menu.active {
            display: block;
        }

        .settings-menu li {
            padding: 10px 20px 10px 50px !important;
            font-size: 0.9rem;
            color: #666;
        }

        .settings-menu li:hover {
            background: #e9ecef;
        }

        .settings-menu li i {
            margin-right: 8px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>AgriSync</h2>
            </div>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i> <span>Menu</span>
            </button>
            <ul class="sidebar-menu">
                <li class="active"><i class="fas fa-home"></i> <span>Dashboard</span></li>
                <li><a href="manage_users.php" style="text-decoration: none; color: inherit;"><i class="fas fa-users"></i> <span>Manage Users</span></a></li>
                <li><a href="manage_products.php" style="text-decoration: none; color: inherit;"><i class="fas fa-box"></i> <span>Manage Products</span></a></li>
                <li><a href="manage_orders.php" style="text-decoration: none; color: inherit;"><i class="fas fa-shopping-cart"></i> <span>Manage Orders</span></a></li>
                <li class="settings-item">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                    <ul class="settings-menu">
                        <li><a href="profile.php" style="text-decoration:none;color:inherit;"><i class="fas fa-user-circle"></i> <span>Profile</span></a></li>
                        <li><a href="notifications.php" style="text-decoration:none;color:inherit;"><i class="fas fa-bell"></i> <span>Notifications</span></a></li>
                        <li><a href="security.php" style="text-decoration:none;color:inherit;"><i class="fas fa-lock"></i> <span>Security</span></a></li>
                        <li><a href="appearance.php" style="text-decoration:none;color:inherit;"><i class="fas fa-palette"></i> <span>Appearance</span></a></li>
                    </ul>
                </li>
                <li><a href="logout.php" style="text-decoration: none; color: inherit;"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="value"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="value"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="value"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <div class="value">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
            </div>

            <!-- Dashboard Sections Grid -->
            <div class="dashboard-grid">
                <!-- Orders Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-shopping-cart"></i> Recent Orders</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                                <?php echo htmlspecialchars($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="action-btn view-btn" onclick="location.href='view_order.php?id=<?php echo $order['id']; ?>'">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2><i class="fas fa-box"></i> Low Stock Products</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Name</th>
                                    <th>Current Stock</th>
                                    <th>Seller</th>
                                    <th>Contact</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <tr>
                                        <td>#<?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo $product['stock_quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['seller_email']); ?></td>
                                        <td>
                                            <button class="action-btn view-btn">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        // Settings Menu Toggle
        const settingsItem = document.querySelector('.settings-item');
        const settingsMenu = document.querySelector('.settings-menu');

        settingsItem.addEventListener('click', () => {
            settingsMenu.classList.toggle('active');
        });

        // Check screen size on load
        window.addEventListener('load', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });

        // Check screen size on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });
    </script>
</body>
</html>
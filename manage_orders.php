<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

// Fetch all orders with user and product information
$stmt = $pdo->query('SELECT o.*, 
                     u.first_name as buyer_first_name, u.last_name as buyer_last_name,
                     oi.quantity,
                     p.name as product_name, p.price as product_price,
                     s.first_name as seller_first_name, s.last_name as seller_last_name
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id 
                     JOIN order_items oi ON o.id = oi.order_id
                     JOIN products p ON oi.product_id = p.id
                     JOIN users s ON p.seller_id = s.id
                     ORDER BY o.created_at DESC');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Let's see what columns are in the orders table
$debug_stmt = $pdo->query('DESCRIBE orders');
$columns = $debug_stmt->fetchAll(PDO::FETCH_COLUMN);
error_log('Orders table columns: ' . print_r($columns, true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - AgriSync</title>
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
        body {
            background: var(--background-color);
            color: var(--text-color);
        }
        .admin-container {
            margin: 0;
            max-width: none;
            background: var(--card-bg);
            border-radius: 0;
            box-shadow: none;
            padding: 0;
            width: 100vw;
        }
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
        .main-content {
            margin-left: 250px;
            width: calc(100vw - 250px);
            padding: 0;
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
        }
        .dashboard-section {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        th, td {
            padding: 12px 15px;
            font-size: 1em;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: normal;
            max-width: none;
            overflow: visible;
            text-overflow: unset;
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
            font-size: 0.8em;
            font-weight: bold;
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
        @media (max-width: 1000px) {
            .table-container {
                overflow-x: auto;
            }
            table {
                min-width: 800px;
            }
        }
        .sidebar, .sidebar-menu, .sidebar-menu li, .sidebar-header h2, .toggle-sidebar {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 1rem;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar collapsed" id="sidebar">
            <div class="sidebar-header">
                <h2>AgriSync</h2>
            </div>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i> <span>Menu</span>
            </button>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" style="text-decoration: none; color: inherit;"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="manage_users.php" style="text-decoration: none; color: inherit;"><i class="fas fa-users"></i> <span>Manage Users</span></a></li>
                <li class="active"><a href="manage_orders.php" style="text-decoration: none; color: inherit;"><i class="fas fa-shopping-cart"></i> <span>Manage Orders</span></a></li>
                <li><a href="manage_products.php" style="text-decoration: none; color: inherit;"><i class="fas fa-box"></i> <span>Manage Products</span></a></li>
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
        <div class="main-content expanded" id="mainContent">
            <div class="dashboard-header">
                <h1>Manage Orders</h1>
            </div>

            <div class="dashboard-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['buyer_first_name'] . ' ' . $order['buyer_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['seller_first_name'] . ' ' . $order['seller_last_name']); ?></td>
                                    <td><?php echo $order['quantity']; ?></td>
                                    <td>$<?php echo number_format($order['quantity'] * $order['product_price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn" onclick="location.href='view_order.php?id=<?php echo $order['id']; ?>'">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn update-status-btn" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateOrderStatus(orderId) {
            const statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            const currentStatus = event.target.closest('tr').querySelector('.status-badge').textContent.toLowerCase();
            const currentIndex = statuses.indexOf(currentStatus);
            const nextStatus = statuses[(currentIndex + 1) % statuses.length];
            
            if (confirm(`Update order status to ${nextStatus}?`)) {
                window.location.href = `update_order_status.php?id=${orderId}&status=${nextStatus}`;
            }
        }

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
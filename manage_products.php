<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

// Fetch all products with seller information
$stmt = $pdo->query('SELECT p.*, u.first_name, u.last_name 
                     FROM products p 
                     JOIN users u ON p.seller_id = u.id 
                     ORDER BY p.created_at DESC');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - AgriSync</title>
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
        .status-active { background-color: var(--success); color: #fff; }
        .status-inactive { background-color: var(--danger); color: #fff; }
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
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
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
        <div class="main-content expanded" id="mainContent">
            <div class="dashboard-header">
                <h1>Manage Products</h1>
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] !== 'admin'): ?>
                <button class="add-product-btn" onclick="location.href='add_product.php'">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Seller</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?php echo $product['id']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="product-image">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>$<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo isset($product['status']) && $product['status'] ? 'active' : 'inactive'; ?>">
                                            <?php echo isset($product['status']) && $product['status'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn view-btn" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (strtolower($product['category'] ?? '') !== 'agricultural'): ?>
                                            <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                // Add your delete logic here
                window.location.href = `delete_product.php?id=${productId}`;
            }
        }

        function viewProduct(productId) {
            window.location.href = `view_product.php?id=${productId}`;
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
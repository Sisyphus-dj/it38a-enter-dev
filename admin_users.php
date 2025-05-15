<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user'];

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];
    
    if ($user_id) {
        switch ($action) {
            case 'ban':
                $stmt = $pdo->prepare('UPDATE users SET status = "banned" WHERE id = ?');
                break;
            case 'activate':
                $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
                break;
            case 'suspend':
                $stmt = $pdo->prepare('UPDATE users SET status = "suspended" WHERE id = ?');
                break;
        }
        
        if (isset($stmt)) {
            $stmt->execute([$user_id]);
        }
    }
}

// Search and filter parameters
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?? '';
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?? '';
$role_filter = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_STRING) ?? '';

// Build the query
$query = '
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COUNT(DISTINCT p.id) as total_posts,
           COUNT(DISTINCT pr.id) as total_products
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN posts p ON u.id = p.user_id
    LEFT JOIN products pr ON u.id = pr.user_id
    WHERE 1=1
';

$params = [];

if ($search) {
    $query .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $query .= ' AND u.status = ?';
    $params[] = $status_filter;
}

if ($role_filter) {
    $query .= ' AND u.role = ?';
    $params[] = $role_filter;
}

$query .= ' GROUP BY u.id ORDER BY u.created_at DESC';

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - AgriSync Admin</title>
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
            max-width: 1200px;
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

        .filters {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            min-width: 200px;
        }

        .filters button {
            padding: 8px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .filters button:hover {
            background-color: var(--secondary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
            display: inline-block;
        }

        .status-active { background-color: #90ee90; }
        .status-banned { background-color: #ffb6c1; }
        .status-suspended { background-color: #ffd700; }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            color: white;
        }

        .view-button { background-color: var(--primary-color); }
        .ban-button { background-color: #dc3545; }
        .activate-button { background-color: #28a745; }
        .suspend-button { background-color: #ffc107; }

        .action-button:hover {
            opacity: 0.9;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-color);
        }

        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>User Management</h1>
        </div>

        <div class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_products.php">Manage Products</a>
            <a href="admin_orders.php">Manage Orders</a>
            <a href="admin_content.php">Content Management</a>
            <a href="admin_settings.php">Settings</a>
        </div>

        <div class="filters">
            <form method="get" action="">
                <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="banned" <?php echo $status_filter === 'banned' ? 'selected' : ''; ?>>Banned</option>
                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>

                <select name="role">
                    <option value="">All Roles</option>
                    <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>

                <button type="submit">Apply Filters</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Orders</th>
                    <th>Posts</th>
                    <th>Products</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo ucfirst($u['role']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $u['status']; ?>">
                                <?php echo htmlspecialchars($u['status'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($u['total_orders']); ?></td>
                        <td><?php echo number_format($u['total_posts']); ?></td>
                        <td><?php echo number_format($u['total_products']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td class="action-buttons">
                            <a href="view_user.php?id=<?php echo $u['id']; ?>" class="action-button view-button">View</a>
                            
                            <?php if ($u['status'] !== 'banned'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="action" value="ban">
                                    <button type="submit" class="action-button ban-button">Ban</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($u['status'] === 'banned'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="action-button activate-button">Activate</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($u['status'] === 'suspended'): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <button type="submit" class="action-button activate-button">Activate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" style="color: var(--primary-color); text-decoration: none;">Logout</a>
        </div>
    </div>
</body>
</html> 
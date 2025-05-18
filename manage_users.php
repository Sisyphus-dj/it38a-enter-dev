<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build query
$query = 'SELECT * FROM users WHERE 1=1';
$params = [];
if ($search !== '') {
    $query .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role_filter !== '') {
    $query .= ' AND role = ?';
    $params[] = $role_filter;
}
if ($status_filter !== '') {
    $query .= ' AND status = ?';
    $params[] = $status_filter;
}
$query .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - AgriSync</title>
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
            max-width: 900px;
            margin: 40px auto;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 32px;
        }
        .dashboard-header {
            background: none;
            box-shadow: none;
            padding: 0;
            margin-bottom: 20px;
        }
        .dashboard-header h1 {
            color: var(--primary-color);
        }
        .add-user-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .add-user-btn:hover {
            background-color: var(--info);
        }
        .dashboard-section {
            background: none;
            box-shadow: none;
            padding: 0;
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
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Include your sidebar here -->
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <h1>Manage Users</h1>
                <form method="get" style="display:flex;gap:10px;align-items:center;margin-bottom:20px;">
                    <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding:8px;border-radius:4px;border:1px solid var(--border-color);background:#1a1a2e;color:var(--text-color);">
                    <select name="role" style="padding:8px;border-radius:4px;border:1px solid var(--border-color);background:#1a1a2e;color:var(--text-color);">
                        <option value="">All Roles</option>
                        <option value="user" <?php if(($role_filter ?? '')==='user') echo 'selected'; ?>>User</option>
                        <option value="seller" <?php if(($role_filter ?? '')==='seller') echo 'selected'; ?>>Seller</option>
                        <option value="admin" <?php if(($role_filter ?? '')==='admin') echo 'selected'; ?>>Admin</option>
                    </select>
                    <select name="status" style="padding:8px;border-radius:4px;border:1px solid var(--border-color);background:#1a1a2e;color:var(--text-color);">
                        <option value="">All Status</option>
                        <option value="1" <?php if(($status_filter ?? '')==='1') echo 'selected'; ?>>Active</option>
                        <option value="0" <?php if(($status_filter ?? '')==='0') echo 'selected'; ?>>Inactive</option>
                    </select>
                    <button type="submit" style="background:var(--primary-color);color:#fff;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Search</button>
                </form>
                <button class="add-user-btn" onclick="document.getElementById('addUserModal').style.display='block'">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>

            <!-- Add User Modal -->
            <div id="addUserModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:2000;align-items:center;justify-content:center;">
                <div style="background:var(--card-bg);padding:32px 24px;border-radius:12px;max-width:400px;width:90%;margin:60px auto;position:relative;">
                    <h2 style="color:var(--primary-color);margin-bottom:18px;">Add New User</h2>
                    <form method="post" action="add_user.php">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required style="width:100%;padding:8px;margin-bottom:10px;background:#1a1a2e;color:var(--text-color);border:1px solid var(--border-color);border-radius:4px;">
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required style="width:100%;padding:8px;margin-bottom:10px;background:#1a1a2e;color:var(--text-color);border:1px solid var(--border-color);border-radius:4px;">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required style="width:100%;padding:8px;margin-bottom:10px;background:#1a1a2e;color:var(--text-color);border:1px solid var(--border-color);border-radius:4px;">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required style="width:100%;padding:8px;margin-bottom:10px;background:#1a1a2e;color:var(--text-color);border:1px solid var(--border-color);border-radius:4px;">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required style="width:100%;padding:8px;margin-bottom:10px;background:#1a1a2e;color:var(--text-color);border:1px solid var(--border-color);border-radius:4px;">
                            <option value="user">User</option>
                            <option value="seller">Seller</option>
                            <option value="admin">Admin</option>
                        </select>
                        <label for="status">Status:</label>
                        <select id="status" name="status" required style="width:100%;padding:8px;margin-bottom:18px;background:#1a1a2e;color:var(--text-color);border:1px solid var(--border-color);border-radius:4px;">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <button type="submit" style="background:var(--primary-color);color:#fff;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Add User</button>
                        <button type="button" onclick="document.getElementById('addUserModal').style.display='none'" style="margin-left:10px;background:var(--danger);color:#fff;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;font-weight:500;">Cancel</button>
                    </form>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['status'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn edit-btn" onclick="location.href='edit_user.php?id=<?php echo $user['id']; ?>'">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                // Add your delete logic here
                window.location.href = `delete_user.php?id=${userId}`;
            }
        }
        // Close modal on outside click
        window.onclick = function(event) {
            var modal = document.getElementById('addUserModal');
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html> 
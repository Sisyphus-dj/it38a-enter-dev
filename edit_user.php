<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user']; 

// Get user ID from URL
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header('Location: admin_users.php');
    exit;
}

// Fetch user details
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$edit_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$edit_user) {
    header('Location: admin_users.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $last_name = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    if (!$first_name || !$last_name || !$email || !$role) {
        $error = 'All fields are required.';
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Email already taken by another user.';
        } else {
            // Update user
            $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?');
            $stmt->execute([$first_name, $last_name, $email, $role, $user_id]);
            header('Location: admin_users.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - AgriSync Admin</title>
    <link rel="stylesheet" href="styles.css"> 
    <style>
        .admin-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .admin-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .admin-nav a {
            padding: 10px 20px;
            background-color: #4e944f;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .admin-nav a:hover {
            background-color: #357a38;
        }
        .edit-form {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }
        .edit-form label {
            display: block;
            margin-bottom: 5px;
        }
        .edit-form input, .edit-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-form button {
            background-color: #4e944f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .edit-form button:hover {
            background-color: #357a38;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Edit User</h1>
        </div>

        <div class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_products.php">Manage Products</a>
            <a href="admin_orders.php">Manage Orders</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form class="edit-form" method="post">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($edit_user['first_name']); ?>" required>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($edit_user['last_name']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>

            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="user" <?php echo $edit_user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>

            <button type="submit">Update User</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="admin_users.php">Back to Users</a>
        </div>
    </div>
</body>
</html> 
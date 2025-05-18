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
            header('Location: manage_users.php');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F8CFF;
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
        .admin-container {
            max-width: 500px;
            margin: 40px auto;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 32px;
        }
        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .edit-form label {
            display: block;
            margin-bottom: 5px;
        }
        .edit-form input, .edit-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: #1a1a2e;
            color: var(--text-color);
        }
        .edit-form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .edit-form button:hover {
            background-color: #38bdf8;
        }
        .error {
            color: #ef4444;
            margin-bottom: 15px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Edit User</h1>
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
        <a href="manage_users.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>
</body>
</html> 
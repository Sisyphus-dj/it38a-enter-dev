<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$user_id) {
    header('Location: admin_users.php');
    exit;
}

// Fetch user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: admin_users.php');
    exit;
}

// Fetch order history
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch posts
$stmt = $pdo->prepare('SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch products listed
$stmt = $pdo->prepare('SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - AgriSync Admin</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container { max-width: 1000px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 30px; }
        .profile-header { display: flex; align-items: center; gap: 30px; margin-bottom: 30px; }
        .profile-pic { width: 100px; height: 100px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 2.5em; color: #4e944f; }
        .profile-info h2 { margin: 0 0 10px 0; }
        .profile-info p { margin: 4px 0; }
        .section { margin-bottom: 30px; }
        .section h3 { margin-bottom: 10px; color: #357a38; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #4e944f; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        .back-link { color: #4e944f; text-decoration: none; margin-bottom: 20px; display: inline-block; }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="admin_users.php" class="back-link">&larr; Back to User Management</a>
        <div class="profile-header">
            <div class="profile-pic">
                <?php echo strtoupper(substr($user['first_name'],0,1)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($user['status'] ?? 'N/A'); ?></p>
                <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <div class="section">
            <h3>Order History</h3>
            <?php if (count($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                        <td><a href="view_order_details.php?id=<?php echo $order['id']; ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No orders found.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Posts</h3>
            <?php if (count($posts)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Post ID</th>
                        <th>Type</th>
                        <th>Content</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td><?php echo htmlspecialchars($post['post_type']); ?></td>
                        <td><?php echo htmlspecialchars($post['text_content'] ?? '[media/link]'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Products Listed</h3>
            <?php if (count($products)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock_quantity'] > 0 ? 'Active' : 'Out of Stock'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
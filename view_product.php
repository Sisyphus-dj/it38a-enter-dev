<?php
session_start();
include_once 'db_connection.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
if (!isset($_GET['id'])) {
    die('Product ID not specified.');
}
$product_id = intval($_GET['id']);
$stmt = $pdo->prepare('SELECT p.*, u.first_name, u.last_name, u.email FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) die('Product not found.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
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
        .product-details-container {
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
            background: #38bdf8;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="product-details-container">
        <h2>Product #<?php echo $product['id']; ?> Details</h2>
        <?php if (!empty($product['image_url'])): ?>
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
        <?php endif; ?>
        <table class="details-table">
            <tr><th>Name</th><td><?php echo htmlspecialchars($product['name']); ?></td></tr>
            <tr><th>Category</th><td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td></tr>
            <tr><th>Price</th><td>$<?php echo number_format($product['price'], 2); ?></td></tr>
            <tr><th>Stock</th><td><?php echo $product['stock_quantity']; ?></td></tr>
            <tr><th>Status</th><td><?php echo $product['status'] ? 'Active' : 'Inactive'; ?></td></tr>
            <tr><th>Seller</th><td><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?> (<?php echo htmlspecialchars($product['email']); ?>)</td></tr>
            <tr><th>Date Listed</th><td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td></tr>
            <tr><th>Description</th><td><?php echo htmlspecialchars($product['description'] ?? ''); ?></td></tr>
        </table>
        <a href="manage_products.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Manage Products</a>
    </div>
</body>
</html> 
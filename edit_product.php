<?php
session_start();
include_once 'db_connection.php';

// User must be logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['shop_message'] = "You must be logged in to edit products.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: login.php');
    exit;
}

// Validate Product ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['shop_message'] = "Invalid product ID specified for editing.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

$product_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user']['id'];

// Fetch product data
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
$stmt->execute();
$product_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product_data) {
    $_SESSION['shop_message'] = "Product not found.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

// Authorization check: Ensure current user owns the product
if ($product_data['user_id'] != $current_user_id) {
    $_SESSION['shop_message'] = "You are not authorized to edit this product.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

// Fetch categories for the dropdown
$stmt_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);

    if (!$name || !$description || !$price || !$category_id || !$stock_quantity) {
        $error = 'All fields are required.';
    } else {
        // Update product
        $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock_quantity = ? WHERE id = ?');
        $stmt->execute([$name, $description, $price, $category_id, $stock_quantity, $product_id]);
        header('Location: shop_page.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgriSync Admin</title>
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
        .edit-form input, .edit-form select, .edit-form textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-form textarea {
            height: 100px;
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
            <h1>Edit Product</h1>
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
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product_data['name']); ?>" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($product_data['description']); ?></textarea>

            <label for="price">Price:</label>
            <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product_data['price']); ?>" required>

            <label for="category_id">Category:</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $product_data['category_id'] === $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="stock_quantity">Stock Quantity:</label>
            <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product_data['stock_quantity']); ?>" required>

            <button type="submit">Update Product</button>
            <a href="shop_page.php" class="btn-cancel" style="margin-left:10px; background-color: #6c757d; color:white; padding: 10px 15px; text-decoration:none; border-radius:4px;">Cancel</a>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="admin_products.php">Back to Products</a>
        </div>
    </div>
</body>
</html> 
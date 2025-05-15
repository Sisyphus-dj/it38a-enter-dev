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
$product_data = null;
$categories = [];

if (isset($pdo)) {
    try {
        // Fetch product data
        $stmt_product = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt_product->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt_product->execute();
        $product_data = $stmt_product->fetch(PDO::FETCH_ASSOC);

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

    } catch (PDOException $e) {
        error_log("Database error in edit_product.php: " . $e->getMessage());
        $_SESSION['shop_message'] = "An error occurred while fetching product data.";
        $_SESSION['shop_message_type'] = "error";
        header('Location: shop_page.php');
        exit;
    }
} else {
    $_SESSION['shop_message'] = "Database connection not available.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}

$user = $_SESSION['user']; // For navbar consistency
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgriSync Marketplace</title>
    <link rel="stylesheet" href="user_dashboard.css"> <!-- Reusing dashboard CSS -->
    <style>
        .edit-product-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .current-image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: block; /* Make image block to sit above checkbox */
        }
        /* Using .create-post-form styles from user_dashboard.css for the form itself */
    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
        <a href="user_dashboard.php" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="user_dashboard.php">Home</a></li>
            <li><a href="shop_page.php" class="shop-btn">Shop</a></li>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="edit-product-container">
        <h2>Edit Product Details</h2>

        <?php
        // Display any session messages from update_product.php
        if (isset($_SESSION['product_form_message'])) {
            $message_type = $_SESSION['product_form_message_type'] ?? 'error';
            echo "<div class='feed-alert feed-alert-{$message_type}'>" . $_SESSION['product_form_message'] . "</div>";
            unset($_SESSION['product_form_message']);
            unset($_SESSION['product_form_message_type']);
        }
        ?>

        <form action="update_product.php" method="POST" enctype="multipart/form-data" class="create-post-form">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_data['id']); ?>">
            <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($product_data['image_path'] ?? ''); ?>">

            <div>
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" required 
                       value="<?php echo htmlspecialchars($product_data['name'] ?? ''); ?>">
            </div>

            <div>
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" rows="6" required><?php echo htmlspecialchars($product_data['description'] ?? ''); ?></textarea>
            </div>

            <div>
                <label for="product_price">Price ($):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" min="0" required
                       value="<?php echo htmlspecialchars($product_data['price'] ?? ''); ?>">
            </div>

            <div>
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id">
                    <option value="">Select a Category (Optional)</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>"
                            <?php echo ($product_data['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="stock_quantity">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                       value="<?php echo htmlspecialchars($product_data['stock_quantity'] ?? '0'); ?>">
            </div>

            <div>
                <label for="product_image">New Product Image (Optional - Replaces existing):</label>
                <?php if (!empty($product_data['image_path'])) : ?>
                    <p style="margin-bottom:5px;">Current Image:</p>
                    <img src="<?php echo htmlspecialchars($product_data['image_path']); ?>" alt="Current product image" class="current-image-preview">
                    <div>
                        <input type="checkbox" name="remove_existing_image" id="remove_existing_image" value="1">
                        <label for="remove_existing_image" style="font-weight: normal; display: inline;">Remove current image</label>
                    </div>
                <?php endif; ?>
                <input type="file" id="product_image" name="product_image" accept="image/*" style="margin-top:10px;">
            </div>

            <button type="submit" class="btn">Update Product</button>
            <a href="shop_page.php#product-<?php echo htmlspecialchars($product_data['id']); ?>" class="btn-cancel" style="margin-left:10px; background-color: #6c757d; color:white; padding: 10px 15px; text-decoration:none; border-radius:4px;">Cancel</a>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
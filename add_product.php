<?php
session_start();
include_once 'db_connection.php';

// User must be logged in and be a seller to add a product
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    $_SESSION['shop_message'] = "You must be a registered seller to add products.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: login.php?redirect=add_product.php'); // Redirect to login, then back
    exit;
}

$user = $_SESSION['user'];
$categories = [];

// Fetch categories for the dropdown
if (isset($pdo)) {
    try {
        $stmt_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching categories for add_product page: " . $e->getMessage());
        $form_error = "Could not load categories. Please try again later.";
    }
} else {
    $form_error = "Database connection not available.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - AgriSync Marketplace</title>
    <link rel="stylesheet" href="user_dashboard.css"> <!-- Reusing dashboard CSS -->
    <style>
        .add-product-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

    <div class="add-product-container">
        <h2>List a New Product in the Marketplace</h2>

        <?php 
        // Display form errors or messages passed via session from submit_product.php
        if (isset($_SESSION['form_submission_message'])) {
            $message_type = $_SESSION['form_submission_message_type'] ?? 'error';
            echo "<div class='feed-alert feed-alert-{$message_type}'>" . $_SESSION['form_submission_message'] . "</div>";
            unset($_SESSION['form_submission_message']);
            unset($_SESSION['form_submission_message_type']);
        }
        if (isset($form_error)) {
             echo "<div class='feed-alert feed-alert-error'>{$form_error}</div>";
        }
        ?>

        <form action="submit_product.php" method="POST" enctype="multipart/form-data" class="create-post-form"> 
            <!-- Using create-post-form class for similar styling -->
            
            <div>
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="product_name" required 
                       value="<?php echo htmlspecialchars($_SESSION['form_data_product']['product_name'] ?? ''); ?>">
            </div>

            <div>
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="product_description" rows="6" required><?php echo htmlspecialchars($_SESSION['form_data_product']['product_description'] ?? ''); ?></textarea>
            </div>

            <div>
                <label for="product_price">Price ($):</label>
                <input type="number" id="product_price" name="product_price" step="0.01" min="0" required
                       value="<?php echo htmlspecialchars($_SESSION['form_data_product']['product_price'] ?? ''); ?>">
            </div>

            <div>
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id">
                    <option value="">Select a Category (Optional)</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>"
                            <?php echo (isset($_SESSION['form_data_product']['category_id']) && $_SESSION['form_data_product']['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="stock_quantity">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                       value="<?php echo htmlspecialchars($_SESSION['form_data_product']['stock_quantity'] ?? '0'); ?>">
            </div>

            <div>
                <label for="product_image">Product Image (Optional):</label>
                <input type="file" id="product_image" name="product_image" accept="image/*">
            </div>

            <button type="submit" class="btn">Add Product to Marketplace</button>
             <a href="shop_page.php" class="btn-cancel" style="margin-left:10px; background-color: #6c757d; color:white; padding: 10px 15px; text-decoration:none; border-radius:4px;">Cancel</a>
        </form>
        <?php 
            // Clear form data after displaying it
            if (isset($_SESSION['form_data_product'])) {
                unset($_SESSION['form_data_product']);
            }
        ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
<?php
session_start();
include_once 'db_connection.php'; // For database connection

$products = [];
$categories = [];
$selected_category_id = null;
$selected_category_name = "All Categories";
$search_term = ''; // Initialize search term

// Get selected category filter if any
if (isset($_GET['category_filter']) && filter_var($_GET['category_filter'], FILTER_VALIDATE_INT)) {
    $selected_category_id = (int)$_GET['category_filter'];
} elseif (isset($_GET['category_filter']) && $_GET['category_filter'] === '') {
    // Explicitly handle 'All Categories' selection to clear category ID
    $selected_category_id = null;
}

// Get search term if any
if (isset($_GET['search_term']) && trim($_GET['search_term']) !== '') {
    $search_term = trim($_GET['search_term']);
}

if (isset($pdo)) {
    try {
        // Base SQL query
        $sql_products = "
            SELECT 
                p.*, 
                u.first_name, 
                u.last_name, 
                c.name as category_name 
            FROM products p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
        ";

        $params = [];
        $where_clauses = [];

        if ($selected_category_id !== null) {
            $where_clauses[] = "p.category_id = :category_id";
            $params[':category_id'] = $selected_category_id;
        }

        if (!empty($search_term)) {
            $where_clauses[] = "(p.name LIKE :search_term OR p.description LIKE :search_term)";
            $params[':search_term'] = '%' . $search_term . '%';
        }

        if (!empty($where_clauses)) {
            $sql_products .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $sql_products .= " ORDER BY p.created_at DESC";

        $stmt_products = $pdo->prepare($sql_products);
        $stmt_products->execute($params);
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all categories for the filter dropdown
        $stmt_categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

        // Get the name of the selected category for display
        if ($selected_category_id !== null) {
            foreach ($categories as $cat) {
                if ($cat['id'] == $selected_category_id) {
                    $selected_category_name = htmlspecialchars($cat['name']);
                    break;
                }
            }
        } elseif (isset($_GET['category_filter']) && $_GET['category_filter'] === '') {
            $selected_category_name = "All Categories";
        }

    } catch (PDOException $e) {
        error_log("Error fetching products or categories for shop page: " . $e->getMessage());
        $page_error = "Could not load product data. Please try again later.";
    }
} else {
    $page_error = "Database connection not available.";
}

$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - AgriSync Marketplace</title>
    <link rel="stylesheet" href="user_dashboard.css">
    <style>
        .shop-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .shop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .shop-filters {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f8f8;
            border-radius: 5px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 15px;
        }
        .shop-filters .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .shop-filters label {
            font-weight: bold;
            margin-bottom: 0;
        }
        .shop-filters select,
        .shop-filters input[type="text"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 200px;
        }
        .shop-filters button[type="submit"] {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            height: 38px;
            align-self: center;
        }
        .shop-filters button[type="submit"]:hover {
            background-color: #0056b3;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }
        .product-card img {
            max-width: 100%;
            height: 200px; 
            object-fit: cover; 
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .product-card h4 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 1.2em;
            color: #333;
        }
        .product-card .price {
            font-size: 1.1em;
            font-weight: bold;
            color: #28a745; 
            margin-bottom: 8px;
        }
        .product-card .category, .product-card .seller, .product-card .stock {
            font-size: 0.85em;
            color: #777;
            margin-bottom: 3px;
        }
        .product-card .description {
            font-size: 0.95em;
            color: #555;
            flex-grow: 1; 
            margin-bottom: 10px;
        }
        .product-actions-user {
            margin-top: auto; 
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: right;
        }
         .btn-add-product {
            background-color: #28a745; 
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-add-product:hover {
            background-color: #218838;
        }
        h2.current-filter-title {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: #333;
        }
        .add-to-cart-form {
            text-align: right; 
            margin-top: 10px;
        }
        .add-to-cart-form .quantity-input {
            width: 60px;
            padding: 8px;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        .add-to-cart-form .btn-add-to-cart {
            background-color: #17a2b8; 
            color:white; 
            padding: 8px 12px; 
            border:none; 
            border-radius:4px; 
            cursor:pointer;
        }
        .add-to-cart-form .btn-add-to-cart:hover {
            background-color: #138496;
        }
    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
        <a href="user_dashboard.php" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="user_dashboard.php">Home</a></li>
            <li><a href="shop_page.php" class="shop-btn">Shop</a></li>
            <li><a href="cart_page.php">Cart</a></li>
            <?php if ($user): ?>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
            <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="shop-container">
        <div class="shop-header">
            <h1>Welcome to the AgriSync Marketplace</h1>
            <?php if ($user && $user['role'] === 'seller'): ?>
                <a href="add_product.php" class="btn-add-product">+ Add New Product</a>
            <?php endif; ?>
        </div>

        <!-- Category Filter Form -->
        <div class="shop-filters">
            <form action="shop_page.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                <div class="filter-group">
                    <label for="category_filter">Filter by Category:</label>
                    <select name="category_filter" id="category_filter" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>"
                                <?php echo ($selected_category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search_term">Search by Keyword:</label>
                    <input type="text" name="search_term" id="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="e.g., organic tomatoes">
                </div>
                <div class="filter-group">
                    <button type="submit">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <h2 class="current-filter-title">
            Showing: <?php echo $selected_category_name; ?>
            <?php if (!empty($search_term)): ?>
                (Searched for: "<?php echo htmlspecialchars($search_term); ?>")
            <?php endif; ?>
        </h2>

        <?php if (isset($page_error)): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['shop_message'])) {
            $message_type = $_SESSION['shop_message_type'] ?? 'info';
            echo "<div class='feed-alert feed-alert-{$message_type}'>" . $_SESSION['shop_message'] . "</div>";
            unset($_SESSION['shop_message']);
            unset($_SESSION['shop_message_type']);
        }
        ?>

        <div class="product-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" id="product-<?php echo htmlspecialchars($product['id']); ?>">
                        <?php if (!empty($product['image_path'])) : ?>
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="placeholder-image.png" alt="No image available">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <div class="product-price">₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></div>
                        <?php if (!empty($product['category_name'])): ?>
                            <p class="category">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                        <?php endif; ?>
                        <p class="description"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                        <p class="seller">Seller: <?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></p>
                        
                        <?php if ($user && $user['id'] == $product['user_id']): ?>
                            <div class="product-actions-user">
                                <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn-edit" style="font-size:0.9em; padding: 5px 8px;">Edit</a>
                                <a href="delete_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this product?');" style="font-size:0.9em; padding: 5px 8px;">Delete</a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($user && $product['stock_quantity'] > 0): // Show add to cart only if logged in and stock > 0 ?>
                        <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['stock_quantity']); ?>" class="quantity-input" aria-label="Quantity">
                            <button type="submit" class="btn-add-to-cart">Add to Cart</button>
                        </form>
                        <?php elseif ($product['stock_quantity'] <= 0): ?>
                            <p style="text-align:right; color:red; margin-top:10px;">Out of Stock</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php elseif (empty($page_error)):
                $message = "No products found";
                if ($selected_category_id !== null && $selected_category_name !== "All Categories") {
                    $message .= " for '" . $selected_category_name . "'";
                }
                if (!empty($search_term)) {
                    $message .= " matching '" . htmlspecialchars($search_term) . "'";
                }
                $message .= ".";
                if ($selected_category_id !== null || !empty($search_term)) {
                    $message .= ' <a href="shop_page.php">Show all products</a>.';
                }
            ?>
                 <p><?php echo $message; ?></p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
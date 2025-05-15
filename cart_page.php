<?php
session_start();
include_once 'db_connection.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to view your cart.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$cart_items = [];
$cart_total = 0;
$page_error = null;

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                sci.id as cart_item_id, 
                sci.quantity, 
                p.id as product_id, 
                p.name as product_name, 
                p.price as product_price, 
                p.image_path, 
                p.stock_quantity
            FROM shopping_cart_items sci
            JOIN products p ON sci.product_id = p.id
            WHERE sci.user_id = :user_id
            ORDER BY sci.added_at DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cart_items as $item) {
            $cart_total += $item['product_price'] * $item['quantity'];
        }

        // Load settings
        $settings = [];
        $stmt = $pdo->query('SELECT * FROM settings');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['value'];
        }

    } catch (PDOException $e) {
        error_log("Error fetching cart items: " . $e->getMessage());
        $page_error = "Could not load your cart. Please try again later.";
    }
} else {
    $page_error = "Database connection not available.";
}

$user = $_SESSION['user']; // For navigation menu

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - AgriSync</title>
    <link rel="stylesheet" href="user_dashboard.css"> <!-- Reusing some styles -->
    <style>
        .cart-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-details h4 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
        }
        .cart-item-details .price {
            color: #555;
            font-size: 0.9em;
        }
        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .cart-item-actions input[type="number"] {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .cart-item-actions .btn-update,
        .cart-item-actions .btn-remove {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
        }
        .btn-update {
            background-color: #007bff;
            color: white;
        }
        .btn-update:hover {
            background-color: #0056b3;
        }
        .btn-remove {
            background-color: #dc3545;
            color: white;
        }
        .btn-remove:hover {
            background-color: #c82333;
        }
        .cart-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #007bff;
            text-align: right;
        }
        .cart-summary h3 {
            margin: 0 0 10px 0;
        }
        .btn-checkout {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        .btn-checkout:hover {
            background-color: #218838;
        }
        .empty-cart-message {
            text-align: center;
            padding: 30px;
            font-size: 1.2em;
            color: #777;
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
            <li><a href="cart_page.php" style="font-weight:bold;">Cart</a></li>
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

    <div class="cart-container">
        <h1>Your Shopping Cart</h1>

        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="feed-alert feed-alert-<?php echo $_SESSION['cart_message_type'] ?? 'info'; ?>">
                <?php echo htmlspecialchars($_SESSION['cart_message']); ?>
            </div>
            <?php 
                unset($_SESSION['cart_message']);
                unset($_SESSION['cart_message_type']);
            ?>
        <?php endif; ?>

        <?php if ($page_error): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items) && !$page_error): ?>
            <div class="empty-cart-message">
                <p>Your cart is currently empty.</p>
                <a href="shop_page.php" class="btn-add-product" style="font-size: 1em;">Continue Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" id="cart-item-<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                    <img src="<?php echo htmlspecialchars(!empty($item['image_path']) ? $item['image_path'] : 'placeholder-image.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <div class="cart-item-details">
                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                        <p class="price">Price: $<?php echo htmlspecialchars(number_format($item['product_price'], 2)); ?></p>
                         <p class="price">Stock: <?php echo htmlspecialchars($item['stock_quantity']); ?></p>
                    </div>
                    <div class="cart-item-actions">
                        <form action="update_cart_item.php" method="POST" style="display:inline-flex; align-items:center;">
                            <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" max="<?php echo htmlspecialchars($item['stock_quantity']); ?>" aria-label="Quantity">
                            <button type="submit" class="btn-update">Update</button>
                        </form>
                        <form action="remove_cart_item.php" method="POST" style="display:inline;">
                            <input type="hidden" name="cart_item_id" value="<?php echo htmlspecialchars($item['cart_item_id']); ?>">
                            <button type="submit" class="btn-remove" onclick="return confirm('Are you sure you want to remove this item?');">Remove</button>
                        </form>
                    </div>
                    <div style="min-width: 80px; text-align:right; margin-left:15px;">
                        <strong>$<?php echo htmlspecialchars(number_format($item['product_price'] * $item['quantity'], 2)); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="cart-summary">
                <table style="float:right; text-align:right;">
                    <tr><td>Cart Total:</td><td>$<?php echo htmlspecialchars(number_format($cart_total, 2)); ?></td></tr>
                    <tr><td>Delivery Fee:</td><td>$<?php echo htmlspecialchars(number_format((float)($settings['delivery_fee'] ?? 0), 2)); ?></td></tr>
                    <tr><td><strong>Total:</strong></td><td><strong>$<?php echo htmlspecialchars(number_format($cart_total + (float)($settings['delivery_fee'] ?? 0), 2)); ?></strong></td></tr>
                </table>
                <div style="clear:both;"></div>
                <?php $min_order = (float)($settings['min_order_amount'] ?? 0); ?>
                <?php if ($cart_total < $min_order): ?>
                    <div class="feed-alert feed-alert-error">Minimum order amount is $<?php echo number_format($min_order, 2); ?>. Please add more items to your cart.</div>
                    <button class="btn-checkout" disabled>Proceed to Checkout</button>
                <?php else: ?>
                    <a href="checkout_page.php" class="btn-checkout">Proceed to Checkout</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html> 
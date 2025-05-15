<?php
session_start();
include_once 'db_connection.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['shop_message'] = 'Please login to proceed to checkout.';
    $_SESSION['shop_message_type'] = 'warning';
    header('Location: login.php?redirect=checkout_page.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$cart_items = [];
$cart_total = 0;
$page_error = null;

$shipping_details = $_SESSION['checkout_form_data'] ?? [
    'shipping_name' => $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'] ?? '',
    'shipping_address_line1' => '',
    'shipping_address_line2' => '',
    'shipping_city' => '',
    'shipping_state' => '',
    'shipping_postal_code' => '',
    'shipping_country' => '',
    'payment_method' => 'cod' // Default payment method
];
$selected_payment_method = $shipping_details['payment_method'];
unset($_SESSION['checkout_form_data']);

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                sci.id as cart_item_id, 
                sci.quantity, 
                p.id as product_id, 
                p.name as product_name, 
                p.price as product_price, 
                p.image_path
            FROM shopping_cart_items sci
            JOIN products p ON sci.product_id = p.id
            WHERE sci.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            $_SESSION['cart_message'] = 'Your cart is empty. Please add items before proceeding to checkout.';
            $_SESSION['cart_message_type'] = 'info';
            header('Location: cart_page.php');
            exit;
        }

        foreach ($cart_items as $item) {
            $cart_total += $item['product_price'] * $item['quantity'];
        }

    } catch (PDOException $e) {
        error_log("Error fetching cart items for checkout: " . $e->getMessage());
        $page_error = "Could not load your cart for checkout. Please try again later.";
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
    <title>Checkout - AgriSync</title>
    <link rel="stylesheet" href="user_dashboard.css"> 
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .checkout-section {
            margin-bottom: 30px;
        }
        .checkout-section h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .order-summary-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            font-size: 0.9em;
        }
        .order-summary-item:last-child {
            border-bottom: none;
        }
        .order-summary-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 15px;
        }
        .order-summary-details {
            flex-grow: 1;
        }
        .order-summary-details h5 {
            margin: 0 0 5px 0;
            font-size: 1em;
        }
        .order-summary-price, .order-summary-quantity, .order-summary-subtotal {
            color: #555;
        }
        .order-summary-subtotal {
            min-width: 70px;
            text-align: right;
            font-weight: bold;
        }
        .checkout-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #007bff;
            text-align: right;
        }
        .checkout-total h3 {
            margin: 0 0 15px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .payment-method-option {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .payment-method-option input[type="radio"] {
            margin-right: 10px;
        }
        .payment-method-option.selected {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .payment-method-description {
            font-size: 0.85em;
            color: #666;
            margin-left: 25px; /* Align with radio button text */
        }

        .btn-place-order {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            display: inline-block; 
            margin-top: 10px; 
        }
        .btn-place-order:hover {
            background-color: #218838;
        }
        .checkout-actions {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .coming-soon-note {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            margin-bottom: 20px;
            text-align: center;
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

    <div class="checkout-container">
        <h1>Checkout</h1>

        <?php if (isset($_SESSION['checkout_message'])): ?>
            <div class="feed-alert feed-alert-<?php echo $_SESSION['checkout_message_type'] ?? 'info'; ?>">
                <?php echo htmlspecialchars($_SESSION['checkout_message']); ?>
            </div>
            <?php 
                if(isset($_SESSION['checkout_form_errors'])) {
                    echo '<ul style="color:red;">';
                    foreach($_SESSION['checkout_form_errors'] as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    unset($_SESSION['checkout_form_errors']);
                }
                unset($_SESSION['checkout_message']);
                unset($_SESSION['checkout_message_type']);
            ?>
        <?php endif; ?>

        <?php if ($page_error): ?>
            <div class="feed-alert feed-alert-error"><?php echo htmlspecialchars($page_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($cart_items) && !$page_error): ?>
            
            <form action="process_order.php" method="POST" id="checkout-form">
                <div class="checkout-section">
                    <h2>Shipping Details</h2>
                    <div class="form-group">
                        <label for="shipping_name">Full Name</label>
                        <input type="text" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($shipping_details['shipping_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_address_line1">Address Line 1</label>
                        <input type="text" id="shipping_address_line1" name="shipping_address_line1" value="<?php echo htmlspecialchars($shipping_details['shipping_address_line1']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="shipping_address_line2">Address Line 2 (Optional)</label>
                        <input type="text" id="shipping_address_line2" name="shipping_address_line2" value="<?php echo htmlspecialchars($shipping_details['shipping_address_line2']); ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_city">City</label>
                            <input type="text" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($shipping_details['shipping_city']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_state">State/Province</label>
                            <input type="text" id="shipping_state" name="shipping_state" value="<?php echo htmlspecialchars($shipping_details['shipping_state']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_postal_code">Postal Code</label>
                            <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($shipping_details['shipping_postal_code']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_country">Country</label>
                            <input type="text" id="shipping_country" name="shipping_country" value="<?php echo htmlspecialchars($shipping_details['shipping_country']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="checkout-section">
                    <h2>Order Summary</h2>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-summary-item">
                            <img src="<?php echo htmlspecialchars(!empty($item['image_path']) ? $item['image_path'] : 'placeholder-image.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <div class="order-summary-details">
                                <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                <span class="order-summary-price">Price: $<?php echo htmlspecialchars(number_format($item['product_price'], 2)); ?></span>
                                <span class="order-summary-quantity"> | Qty: <?php echo htmlspecialchars($item['quantity']); ?></span>
                            </div>
                            <div class="order-summary-subtotal">
                                $<?php echo htmlspecialchars(number_format($item['product_price'] * $item['quantity'], 2)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="checkout-total">
                        <h3>Total: $<?php echo htmlspecialchars(number_format($cart_total, 2)); ?></h3>
                    </div>
                </div>
                
                <div class="checkout-section">
                    <h2>Payment Method</h2>
                    <div class="payment-method-option <?php echo ($selected_payment_method == 'cod') ? 'selected' : ''; ?>" onclick="selectPaymentMethod('cod')">
                        <input type="radio" id="payment_cod" name="payment_method" value="cod" <?php echo ($selected_payment_method == 'cod') ? 'checked' : ''; ?> required>
                        <label for="payment_cod">Cash on Delivery (COD)</label>
                        <div class="payment-method-description">Pay with cash upon delivery of your order.</div>
                    </div>
                    <div class="payment-method-option <?php echo ($selected_payment_method == 'gcash') ? 'selected' : ''; ?>" onclick="selectPaymentMethod('gcash')">
                        <input type="radio" id="payment_gcash" name="payment_method" value="gcash" <?php echo ($selected_payment_method == 'gcash') ? 'checked' : ''; ?> required>
                        <label for="payment_gcash">GCash</label>
                        <div class="payment-method-description">Pay using GCash. (API integration coming soon - this option is for display purposes).</div>
                    </div>
                     <div id="gcash_instructions" style="display: <?php echo ($selected_payment_method == 'gcash') ? 'block' : 'none'; ?>; margin-top:10px; padding:10px; background-color:#e9ecef; border-radius:4px;">
                        <p><strong>GCash Payment Instructions:</strong> Further instructions for GCash payment will appear here or you will be redirected after placing the order.</p>
                    </div>
                </div>

                <div class="checkout-actions">
                    <a href="cart_page.php" class="btn-secondary" style="padding: 10px 15px; text-decoration:none; background-color:#6c757d; color:white; border-radius:5px;">&laquo; Back to Cart</a>
                    <button type="submit" class="btn-place-order">Place Order</button>
                </div>
            </form>
        
        <?php elseif (!$page_error): ?>
            <div class="feed-alert feed-alert-info">Your cart is empty. <a href="shop_page.php">Continue shopping</a>.</div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
    <script>
        function selectPaymentMethod(method) {
            document.getElementById('payment_' + method).checked = true;
            document.querySelectorAll('.payment-method-option').forEach(function(el) {
                el.classList.remove('selected');
            });
            document.querySelector('input[name="payment_method"][value="' + method + '"]').closest('.payment-method-option').classList.add('selected');
            
            // Show/hide GCash specific instructions
            var gcashInstructions = document.getElementById('gcash_instructions');
            if (method === 'gcash') {
                gcashInstructions.style.display = 'block';
            } else {
                gcashInstructions.style.display = 'none';
            }
        }
        // Initialize selection based on PHP value
        window.onload = function() {
            var initialMethod = '<?php echo $selected_payment_method; ?>';
            if(initialMethod) {
                 selectPaymentMethod(initialMethod);
            }
        };
    </script>
</body>
</html> 
<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Update general settings
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE setting_key = ?");
        
        $settings = [
            'site_name' => $_POST['site_name'],
            'contact_email' => $_POST['contact_email'],
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'payment_gateway' => $_POST['payment_gateway'],
            'gcash_number' => $_POST['gcash_number'],
            'delivery_fee' => $_POST['delivery_fee'],
            'min_order_amount' => $_POST['min_order_amount'],
            'terms_conditions' => $_POST['terms_conditions'],
            'privacy_policy' => $_POST['privacy_policy']
        ];

        foreach ($settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }

        $success_message = "Settings updated successfully!";
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .settings-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .settings-section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4e944f;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }

        .submit-btn {
            background-color: #4e944f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .submit-btn:hover {
            background-color: #357a38;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="admin-header">
            <h1>Settings & Configurations</h1>
            <p>Manage your application settings and configurations</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="settings-section">
                <h2>General Settings</h2>
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? '') == '1' ? 'checked' : ''; ?>>
                        <label for="maintenance_mode">Maintenance Mode</label>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2>Payment Settings</h2>
                <div class="form-group">
                    <label for="payment_gateway">Payment Gateway</label>
                    <select id="payment_gateway" name="payment_gateway">
                        <option value="gcash" <?php echo ($settings['payment_gateway'] ?? '') == 'gcash' ? 'selected' : ''; ?>>GCash</option>
                        <option value="cod" <?php echo ($settings['payment_gateway'] ?? '') == 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="gcash_number">GCash Number</label>
                    <input type="text" id="gcash_number" name="gcash_number" value="<?php echo htmlspecialchars($settings['gcash_number'] ?? ''); ?>">
                </div>
            </div>

            <div class="settings-section">
                <h2>Delivery Settings</h2>
                <div class="form-group">
                    <label for="delivery_fee">Delivery Fee (₱)</label>
                    <input type="number" id="delivery_fee" name="delivery_fee" value="<?php echo htmlspecialchars($settings['delivery_fee'] ?? '0'); ?>" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="min_order_amount">Minimum Order Amount (₱)</label>
                    <input type="number" id="min_order_amount" name="min_order_amount" value="<?php echo htmlspecialchars($settings['min_order_amount'] ?? '0'); ?>" min="0" step="0.01">
                </div>
            </div>

            <div class="settings-section">
                <h2>Terms & Policies</h2>
                <div class="form-group">
                    <label for="terms_conditions">Terms and Conditions</label>
                    <textarea id="terms_conditions" name="terms_conditions"><?php echo htmlspecialchars($settings['terms_conditions'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="privacy_policy">Privacy Policy</label>
                    <textarea id="privacy_policy" name="privacy_policy"><?php echo htmlspecialchars($settings['privacy_policy'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" name="update_settings" class="submit-btn">Save Settings</button>
            </div>
        </form>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin_dashboard.php" style="color: #4e944f; text-decoration: none;">Back to Dashboard</a>
        </div>
    </div>
</body>
</html> 
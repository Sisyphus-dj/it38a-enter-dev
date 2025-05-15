<?php
include_once 'db_connection.php';
$settings = [];
$stmt = $pdo->query('SELECT * FROM settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Contact Us - <?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?></title>
</head>
<body>
    <h1>Contact Us</h1>
    <p>Contact us at: <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>">
        <?php echo htmlspecialchars($settings['contact_email']); ?>
    </a></p>
</body>
</html> 
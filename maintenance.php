<?php
// This file is included when maintenance mode is on
include_once 'db_connection.php';
$settings = [];
$stmt = $pdo->query('SELECT * FROM settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}
$site_name = $settings['site_name'] ?? 'AgriSync';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Maintenance - <?php echo htmlspecialchars($site_name); ?></title>
</head>
<body>
    <h1>We'll be back soon!</h1>
    <p><?php echo htmlspecialchars($site_name); ?> is currently undergoing maintenance. Please check back later.</p>
</body>
</html> 
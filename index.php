<?php
session_start();
include_once 'db_connection.php';
$settings = [];
$stmt = $pdo->query('SELECT * FROM settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}
// Maintenance mode check
if (($settings['maintenance_mode'] ?? '0') == '1' && (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin')) {
    include 'maintenance.php';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?></h1>
    <p>Welcome to <?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?>!</p>
</body>
</html> 
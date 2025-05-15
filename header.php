<?php
include_once 'db_connection.php';
$settings = [];
$stmt = $pdo->query('SELECT * FROM settings');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['value'];
}
?>
<header>
    <h1><?php echo htmlspecialchars($settings['site_name'] ?? 'AgriSync'); ?></h1>
</header> 
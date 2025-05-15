<?php
session_start();
include_once 'db_connection.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); 
    exit;
}

$user = $_SESSION['user']; 

// Fetch announcements
$stmt = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC');
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch knowledge hub articles
$stmt = $pdo->query('SELECT * FROM knowledge_hub ORDER BY created_at DESC');
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - AgriSync Admin</title>
    <link rel="stylesheet" href="styles.css"> 
    <style>
        :root {
            --primary-color: #4e944f;
            --secondary-color: #357a38;
            --background-color: #f5f5f5;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 30px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-nav {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .admin-nav a {
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: bold;
        }

        .admin-nav a:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .content-section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .content-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--text-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .content-card {
            background-color: var(--background-color);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .content-card h3 {
            margin-top: 0;
            color: var(--text-color);
        }

        .content-card p {
            margin: 10px 0;
            color: #666;
        }

        .content-card .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .content-card .actions a {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }

        .content-card .actions a:hover {
            background-color: var(--secondary-color);
        }

        .add-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .add-button:hover {
            background-color: var(--secondary-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
            display: inline-block;
        }

        .status-active { background-color: #98fb98; }
        .status-draft { background-color: #ffd700; }
        .status-archived { background-color: #ffb6c1; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Content Management</h1>
        </div>

        <div class="admin-nav">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Manage Users</a>
            <a href="admin_products.php">Manage Products</a>
            <a href="admin_orders.php">Manage Orders</a>
            <a href="admin_content.php">Content Management</a>
            <a href="admin_settings.php">Settings</a>
        </div>

        <div class="content-section">
            <h2>Landing Page Content</h2>
            <a href="edit_landing.php" class="add-button">Edit Landing Page</a>
            <div class="content-grid">
                <div class="content-card">
                    <h3>Hero Section</h3>
                    <p>Main banner and headline</p>
                    <div class="actions">
                        <a href="edit_hero.php">Edit</a>
                        <a href="preview_hero.php">Preview</a>
                    </div>
                </div>
                <div class="content-card">
                    <h3>Featured Products</h3>
                    <p>Highlighted products section</p>
                    <div class="actions">
                        <a href="edit_featured.php">Edit</a>
                        <a href="preview_featured.php">Preview</a>
                    </div>
                </div>
                <div class="content-card">
                    <h3>About Section</h3>
                    <p>Company information and mission</p>
                    <div class="actions">
                        <a href="edit_about.php">Edit</a>
                        <a href="preview_about.php">Preview</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h2>Announcements</h2>
            <a href="add_announcement.php" class="add-button">Add New Announcement</a>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)) . '...'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($announcement['status']); ?>">
                                    <?php echo htmlspecialchars($announcement['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></td>
                            <td>
                                <a href="edit_announcement.php?id=<?php echo $announcement['id']; ?>">Edit</a> |
                                <a href="delete_announcement.php?id=<?php echo $announcement['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="content-section">
            <h2>Knowledge Hub</h2>
            <a href="add_article.php" class="add-button">Add New Article</a>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($article['title']); ?></td>
                            <td><?php echo htmlspecialchars($article['category']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($article['status']); ?>">
                                    <?php echo htmlspecialchars($article['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($article['created_at'])); ?></td>
                            <td>
                                <a href="edit_article.php?id=<?php echo $article['id']; ?>">Edit</a> |
                                <a href="preview_article.php?id=<?php echo $article['id']; ?>">Preview</a> |
                                <a href="delete_article.php?id=<?php echo $article['id']; ?>" 
                                   onclick="return confirm('Are you sure you want to delete this article?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" style="color: var(--primary-color); text-decoration: none;">Logout</a>
        </div>
    </div>
</body>
</html> 
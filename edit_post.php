<?php
session_start();
include_once 'db_connection.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['feed_message'] = "You must be logged in to edit posts.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['feed_message'] = "Invalid post ID specified for editing.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: user_dashboard.php#feed-section');
    exit;
}

$post_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user']['id'];
$post_data = null;

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
        $stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $post_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post_data) {
            $_SESSION['feed_message'] = "Post not found or you do not have permission to edit it.";
            $_SESSION['feed_message_type'] = "error";
            header('Location: user_dashboard.php#feed-section');
            exit;
        }

        if ($post_data['user_id'] != $current_user_id) {
            $_SESSION['feed_message'] = "You are not authorized to edit this post.";
            $_SESSION['feed_message_type'] = "error";
            header('Location: user_dashboard.php#feed-section');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error in edit_post.php: " . $e->getMessage());
        $_SESSION['feed_message'] = "An error occurred while fetching post data. Please try again.";
        $_SESSION['feed_message_type'] = "error";
        header('Location: user_dashboard.php#feed-section');
        exit;
    }
} else {
    $_SESSION['feed_message'] = "Database connection not available.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: user_dashboard.php#feed-section');
    exit;
}

// If we reach here, $post_data contains the post information
$user = $_SESSION['user']; // For navbar consistency if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - AgriSync</title>
    <link rel="stylesheet" href="user_dashboard.css"> <!-- Assuming same stylesheet -->
    <style>
        /* Add some specific styles for edit page if needed */
        .edit-post-container {
            max-width: 700px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .current-image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .form-group-photo label,
        .form-group-link label {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation Menu (copied from user_dashboard.php for consistency) -->
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
        <a href="user_dashboard.php" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="user_dashboard.php">Home</a></li>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
        </ul>
    </nav>
    <div class="nav-spacer"></div>

    <div class="edit-post-container">
        <h2>Edit Your Post</h2>

        <?php
        // Display any session messages (e.g., from a failed update attempt)
        if (isset($_SESSION['edit_form_message'])) {
            $message_type = $_SESSION['edit_form_message_type'] ?? 'info';
            echo "<div class='feed-alert feed-alert-{$message_type}'>" . $_SESSION['edit_form_message'] . "</div>";
            unset($_SESSION['edit_form_message']);
            unset($_SESSION['edit_form_message_type']);
        }
        ?>

        <form action="update_post.php" method="POST" enctype="multipart/form-data" class="create-post-form">
            <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_data['id']); ?>">
            <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($post_data['image_path'] ?? ''); ?>">

            <div>
                <label for="post_type">Type of Post:</label>
                <input type="text" id="post_type" name="post_type_display" value="<?php echo ucfirst(htmlspecialchars($post_data['post_type'])); ?>" readonly class="form-control-readonly">
                <!-- Keep the actual post_type for submission if needed, or handle based on this readonly display -->
                <input type="hidden" name="post_type" value="<?php echo htmlspecialchars($post_data['post_type']); ?>">
            </div>

            <div>
                <label for="text_content">Your thoughts/fact/caption:</label>
                <textarea name="text_content" id="text_content" rows="5" placeholder="Update your thoughts..."><?php echo htmlspecialchars($post_data['text_content'] ?? ''); ?></textarea>
            </div>

            <div>
                <label for="bisaya_content">Bisaya Translation:</label>
                <textarea name="bisaya_content" id="bisaya_content" rows="5" placeholder="Ibutang ang Bisaya nga hubad dinhi..."><?php echo htmlspecialchars($post_data['bisaya_content'] ?? ''); ?></textarea>
            </div>

            <!-- Fields specific to post type -->
            <div class="form-group-photo" <?php if ($post_data['post_type'] !== 'photo') echo 'style="display: none;"'; ?>>
                <label for="image_upload">Upload New Photo (Optional - Replaces existing):</label>
                <?php if ($post_data['post_type'] === 'photo' && !empty($post_data['image_path'])) : ?>
                    <p>Current Photo:</p>
                    <img src="<?php echo htmlspecialchars($post_data['image_path']); ?>" alt="Current photo" class="current-image-preview">
                    <div>
                        <input type="checkbox" name="remove_existing_image" id="remove_existing_image" value="1">
                        <label for="remove_existing_image" style="font-weight: normal; display: inline;">Remove current photo</label>
                    </div>
                <?php endif; ?>
                <input type="file" name="image_upload" id="image_upload" accept="image/*">
            </div>

            <div class="form-group-link" <?php if ($post_data['post_type'] !== 'link') echo 'style="display: none;"'; ?>>
                <label for="link_url">Link URL:</label>
                <input type="url" name="link_url" id="link_url" placeholder="https://example.com" value="<?php echo htmlspecialchars($post_data['link_url'] ?? ''); ?>">
                 <!-- Link metadata (title, desc, image) is re-fetched on update by update_post.php if URL changes -->
            </div>
            
            <button type="submit" class="btn">Update Post</button>
            <a href="user_dashboard.php#post-<?php echo htmlspecialchars($post_data['id']); ?>" class="btn-cancel" style="margin-left:10px; background-color: #6c757d; color:white; padding: 10px 15px; text-decoration:none; border-radius:4px;">Cancel</a>
        </form>
    </div>

    <!-- Footer (simplified or copied) -->
    <footer>
        <div class="footer-content">
            <p>© <?php echo date("Y"); ?> AgriSync. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html> 
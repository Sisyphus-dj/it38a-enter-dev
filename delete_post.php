<?php
session_start();
include_once 'db_connection.php'; // Ensure this path is correct

if (!isset($_SESSION['user'])) {
    $_SESSION['feed_message'] = "You must be logged in to delete posts.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: login.php'); // Or user_dashboard.php if you prefer to show message there
    exit;
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['feed_message'] = "Invalid post ID specified.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: user_dashboard.php#feed-section');
    exit;
}

$post_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user']['id'];

if (isset($pdo)) {
    try {
        // Fetch the post to verify ownership and get image path
        $stmt = $pdo->prepare("SELECT user_id, image_path FROM posts WHERE id = :id");
        $stmt->bindParam(':id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $_SESSION['feed_message'] = "Post not found.";
            $_SESSION['feed_message_type'] = "error";
            header('Location: user_dashboard.php#feed-section');
            exit;
        }

        // Check if the current user owns the post
        if ($post['user_id'] != $current_user_id) {
            $_SESSION['feed_message'] = "You are not authorized to delete this post.";
            $_SESSION['feed_message_type'] = "error";
            header('Location: user_dashboard.php#feed-section');
            exit;
        }

        // Proceed with deletion
        // 1. Delete the image file if it exists
        if (!empty($post['image_path'])) {
            $image_file_path = __DIR__ . '/' . $post['image_path']; // Assuming image_path is relative to the script's directory e.g., uploads/file.jpg
            if (file_exists($image_file_path)) {
                if (!unlink($image_file_path)) {
                    // Failed to delete file, but proceed to delete DB record anyway. Log this error.
                    error_log("Failed to delete image file: " . $image_file_path . " for post ID: " . $post_id);
                    // You might choose to set a less severe error message here or a partial success message
                }
            } else {
                 error_log("Image file not found for deletion: " . $image_file_path . " for post ID: " . $post_id);
            }
        }

        // 2. Delete the post from the database
        $stmt_delete = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt_delete->bindParam(':id', $post_id, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            $_SESSION['feed_message'] = "Post deleted successfully!";
            $_SESSION['feed_message_type'] = "success";
        } else {
            $_SESSION['feed_message'] = "Failed to delete post from database.";
            $_SESSION['feed_message_type'] = "error";
        }

    } catch (PDOException $e) {
        error_log("Database error in delete_post.php: " . $e->getMessage());
        $_SESSION['feed_message'] = "An error occurred while trying to delete the post. Please try again.";
        $_SESSION['feed_message_type'] = "error";
    }
} else {
    $_SESSION['feed_message'] = "Database connection not available.";
    $_SESSION['feed_message_type'] = "error";
}

header('Location: user_dashboard.php#feed-section');
exit;

?> 
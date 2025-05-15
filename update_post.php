<?php
session_start();
include_once 'db_connection.php';

// Constants from create_post.php - consider moving to a config/helpers file
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Function to fetch and parse link metadata (copied from create_post.php)
function get_link_metadata($url) {
    $metadata = [
        'title' => null,
        'description' => null,
        'image_url' => null
    ];
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (compatible; AgriSyncBot/1.0; +http://yourdomain.com/bot.html)\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);
    if ($html === false) return $metadata;

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $tags = $doc->getElementsByTagName('meta');
    foreach ($tags as $tag) {
        $property = $tag->getAttribute('property');
        $name = $tag->getAttribute('name');
        $content = $tag->getAttribute('content');
        if (!empty($content)) {
            if (($property == 'og:title' || $name == 'title') && $metadata['title'] === null) $metadata['title'] = trim($content);
            if (($property == 'og:description' || $name == 'description') && $metadata['description'] === null) $metadata['description'] = trim($content);
            if (($property == 'og:image' || $name == 'image') && $metadata['image_url'] === null) $metadata['image_url'] = trim($content);
        }
    }
    if ($metadata['title'] === null) {
        $title_tags = $doc->getElementsByTagName('title');
        if ($title_tags->length > 0) $metadata['title'] = trim($title_tags->item(0)->nodeValue);
    }
    return $metadata;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        // This case should ideally be caught by edit_post.php, but double check
        $_SESSION['feed_message'] = "Authentication required.";
        $_SESSION['feed_message_type'] = "error";
        header('Location: login.php');
        exit;
    }

    $current_user_id = $_SESSION['user']['id'];

    // Retrieve and sanitize form data
    $post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $post_type = filter_input(INPUT_POST, 'post_type', FILTER_SANITIZE_STRING); // From hidden field
    $text_content = trim(filter_input(INPUT_POST, 'text_content', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $link_url_new = filter_input(INPUT_POST, 'link_url', FILTER_VALIDATE_URL);
    $existing_image_path = filter_input(INPUT_POST, 'existing_image_path', FILTER_SANITIZE_STRING);
    $remove_existing_image = isset($_POST['remove_existing_image']) ? (bool)$_POST['remove_existing_image'] : false;

    $errors = [];
    $new_image_path_for_db = $existing_image_path; // Start with existing, may change

    if (!$post_id) {
        $errors[] = "Invalid Post ID for update.";
    } else {
        // Fetch original post to verify ownership and for comparison (e.g., original link_url)
        try {
            $stmt_orig = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
            $stmt_orig->bindParam(':id', $post_id, PDO::PARAM_INT);
            $stmt_orig->execute();
            $original_post = $stmt_orig->fetch(PDO::FETCH_ASSOC);

            if (!$original_post) {
                $errors[] = "Post not found for update.";
            } elseif ($original_post['user_id'] != $current_user_id) {
                $errors[] = "You are not authorized to update this post.";
            }
        } catch (PDOException $e) {
            $errors[] = "Error fetching original post: " . $e->getMessage();
        }
    }

    // --- Validations (similar to create_post, adapted for update) ---
    if ($post_type === 'fact' && empty($text_content)) {
        $errors[] = "Text content is required for a fact post.";
    }
    if ($post_type === 'link' && empty($link_url_new)) {
        $errors[] = "Link URL is required for a link post.";
    } elseif ($post_type === 'link' && $link_url_new === false && !empty($_POST['link_url'])) { // false from FILTER_VALIDATE_URL means invalid
        $errors[] = "The provided link URL is not valid.";
    }

    // --- Image Handling (only for 'photo' posts) ---
    if ($post_type === 'photo' && empty($errors)) {
        if ($remove_existing_image) {
            if (!empty($existing_image_path) && file_exists($existing_image_path)) {
                if (!unlink($existing_image_path)) {
                    $errors[] = "Could not remove existing image. Please check permissions.";
                    error_log("Failed to delete existing image during update: " . $existing_image_path);
                }
            }
            $new_image_path_for_db = null; // Set to null in DB
        } elseif (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
            // New image uploaded, process it (similar to create_post.php)
            $file_tmp_path = $_FILES['image_upload']['tmp_name'];
            $file_name = $_FILES['image_upload']['name'];
            $file_size = $_FILES['image_upload']['size'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actual_mime_type = finfo_file($finfo, $file_tmp_path);
            finfo_close($finfo);

            if (!in_array($actual_mime_type, $allowed_mime_types) || !in_array($file_extension, $allowed_extensions)) {
                $errors[] = "Invalid new file type. Only JPG, PNG, GIF, WEBP are allowed.";
            } elseif ($file_size > MAX_FILE_SIZE) {
                $errors[] = "New file is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.";
            } else {
                $unique_file_name = uniqid('', true) . '.' . $file_extension;
                $destination_path = UPLOAD_DIR . $unique_file_name;
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

                if (is_writable(UPLOAD_DIR)) {
                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        // New image successfully uploaded, delete old one if it existed
                        if (!empty($existing_image_path) && file_exists($existing_image_path)) {
                            if(!unlink($existing_image_path)) {
                                error_log("Failed to delete old image after new upload: " . $existing_image_path);
                            }
                        }
                        $new_image_path_for_db = $destination_path;
                    } else {
                        $errors[] = "Failed to move new uploaded file.";
                    }
                } else {
                    $errors[] = "Upload directory is not writable for new image.";
                }
            }
        } // No new image uploaded and not removing existing, $new_image_path_for_db remains $existing_image_path
    }

    // --- Link Metadata Handling (only for 'link' posts) ---
    $link_title_db = $original_post['link_title'] ?? null;
    $link_description_db = $original_post['link_description'] ?? null;
    $link_image_url_db = $original_post['link_image_url'] ?? null;

    if ($post_type === 'link' && !empty($link_url_new) && $link_url_new !== ($original_post['link_url'] ?? null) && empty($errors)) {
        $fetched_metadata = get_link_metadata($link_url_new);
        $link_title_db = $fetched_metadata['title'] ?: $link_url_new; // Fallback to URL if title not found
        $link_description_db = $fetched_metadata['description'];
        $link_image_url_db = $fetched_metadata['image_url'];
    } elseif ($post_type === 'link' && empty($link_url_new)) { // If link URL was cleared
        $link_title_db = null;
        $link_description_db = null;
        $link_image_url_db = null;
    }

    // --- Database Update ---
    if (empty($errors) && isset($pdo)) {
        try {
            $sql = "UPDATE posts SET 
                        text_content = :text_content,
                        image_path = :image_path, 
                        link_url = :link_url, 
                        link_title = :link_title, 
                        link_description = :link_description, 
                        link_image_url = :link_image_url
                    WHERE id = :post_id AND user_id = :user_id";
            
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':text_content', $text_content, PDO::PARAM_STR);
            $stmt->bindParam(':image_path', $new_image_path_for_db, PDO::PARAM_STR);
            $stmt->bindParam(':link_url', $link_url_new, PDO::PARAM_STR);
            $stmt->bindParam(':link_title', $link_title_db, PDO::PARAM_STR);
            $stmt->bindParam(':link_description', $link_description_db, PDO::PARAM_STR);
            $stmt->bindParam(':link_image_url', $link_image_url_db, PDO::PARAM_STR);
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT); // Ensure only owner updates
            
            if ($stmt->execute()) {
                $_SESSION['feed_message'] = "Post updated successfully!";
                $_SESSION['feed_message_type'] = "success";
                header('Location: user_dashboard.php#post-' . $post_id);
                exit;
            } else {
                $errors[] = "Failed to update post in the database. Rows affected: " . $stmt->rowCount();
            }
        } catch (PDOException $e) {
            $errors[] = "Database update error: " . $e->getMessage();
            error_log("Database update error in update_post.php: " . $e->getMessage());
        }
    }

    // If there were errors, store them in session and redirect back to edit form
    if (!empty($errors)) {
        $_SESSION['edit_form_message'] = implode("<br>", $errors);
        $_SESSION['edit_form_message_type'] = "error";
        // Store form data in session to repopulate form (basic, file uploads won't repopulate)
        $_SESSION['form_data_edit'] = $_POST;
        header('Location: edit_post.php?id=' . $post_id);
        exit;
    }

} else {
    // Not a POST request
    $_SESSION['feed_message'] = "Invalid request method for update.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: user_dashboard.php');
    exit;
}
?> 
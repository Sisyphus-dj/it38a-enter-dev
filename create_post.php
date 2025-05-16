<?php
session_start();
include_once 'db_connection.php'; // Ensure this path is correct

// Define the upload directory for images
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Function to fetch and parse link metadata
function get_link_metadata($url) {
    $metadata = [
        'title' => null,
        'description' => null,
        'image_url' => null
    ];

    // Set up a stream context to send a user-agent header, which some sites require
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (compatible; AgriSyncBot/1.0; +http://yourdomain.com/bot.html)\r\n"
        ]
    ];
    $context = stream_context_create($options);

    // Suppress errors for file_get_contents for cleaner handling
    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        return $metadata; // Could not fetch URL
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($html);

    $tags = $doc->getElementsByTagName('meta');
    foreach ($tags as $tag) {
        $property = $tag->getAttribute('property');
        $name = $tag->getAttribute('name');
        $content = $tag->getAttribute('content');

        if (!empty($content)) {
            if ($property == 'og:title' || $name == 'title') {
                if ($metadata['title'] === null) $metadata['title'] = trim($content);
            }
            if ($property == 'og:description' || $name == 'description') {
                if ($metadata['description'] === null) $metadata['description'] = trim($content);
            }
            if ($property == 'og:image' || $name == 'image') {
                if ($metadata['image_url'] === null) $metadata['image_url'] = trim($content);
            }
        }
    }

    // Fallback to <title> tag if no meta title found
    if ($metadata['title'] === null) {
        $title_tags = $doc->getElementsByTagName('title');
        if ($title_tags->length > 0) {
            $metadata['title'] = trim($title_tags->item(0)->nodeValue);
        }
    }
    return $metadata;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in (basic check, user_id comes from form)
    if (!isset($_SESSION['user']) && !isset($_POST['user_id'])) {
        // Redirect to login or show an error if user_id is not available
        header('Location: login.php?error=auth_required');
        exit;
    }

    // Get user_id from form (assuming it's trustworthy as it's a hidden field set by server)
    // Or, for more security, use $_SESSION['user']['id'] if available and prefer that.
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $post_type = filter_input(INPUT_POST, 'post_type', FILTER_SANITIZE_STRING);
    $text_content = trim(filter_input(INPUT_POST, 'text_content', FILTER_SANITIZE_FULL_SPECIAL_CHARS)); // Allow more chars for emojis
    $link_url = filter_input(INPUT_POST, 'link_url', FILTER_VALIDATE_URL);
    $bisaya_content = trim(filter_input(INPUT_POST, 'bisaya_content', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    $image_path = null;
    $link_title = null;
    $link_description = null;
    $link_image_url = null;
    $errors = [];

    // --- Validations ---
    if (!$user_id) {
        $errors[] = "User ID is missing or invalid.";
        // Potentially log this as it might indicate tampering if session user is expected
    }

    if (!in_array($post_type, ['fact', 'photo', 'link'])) {
        $errors[] = "Invalid post type selected.";
    }

    if ($post_type === 'fact' && empty($text_content)) {
        $errors[] = "Text content is required for a fact post.";
    }

    if ($post_type === 'link') {
        if (empty($link_url)) {
            $errors[] = "Link URL is required for a link post.";
        } elseif (!$link_url) { // filter_input returns false if validation fails
             $errors[] = "The provided link URL is not valid.";
        } else {
            // Fetch link metadata
            $fetched_metadata = get_link_metadata($link_url);
            $link_title = $fetched_metadata['title'];
            $link_description = $fetched_metadata['description'];
            $link_image_url = $fetched_metadata['image_url'];
            // If no title fetched, use the URL itself as a fallback title
            if (empty($link_title)) {
                $link_title = $link_url;
            }
        }
    }

    if ($post_type === 'photo') {
        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['image_upload']['tmp_name'];
            $file_name = $_FILES['image_upload']['name'];
            $file_size = $_FILES['image_upload']['size'];
            $file_type = $_FILES['image_upload']['type']; // MIME type from browser
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // More robust MIME type check using finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actual_mime_type = finfo_file($finfo, $file_tmp_path);
            finfo_close($finfo);

            if (!in_array($actual_mime_type, $allowed_mime_types) || !in_array($file_extension, $allowed_extensions)) {
                $errors[] = "Invalid file type. Only JPG, PNG, GIF, WEBP are allowed.";
            } elseif ($file_size > MAX_FILE_SIZE) {
                $errors[] = "File is too large. Maximum size is " . (MAX_FILE_SIZE / 1024 / 1024) . "MB.";
            } else {
                // Create a unique filename
                $unique_file_name = uniqid('', true) . '.' . $file_extension;
                $destination_path = UPLOAD_DIR . $unique_file_name;

                // Ensure upload directory exists and is writable
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true); // Create directory if it doesn't exist
                }

                if (is_writable(UPLOAD_DIR)) {
                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        $image_path = $destination_path;
                    } else {
                        $errors[] = "Failed to move uploaded file. Check permissions.";
                        // Log detailed error: error_log("Failed to move uploaded file: " . $file_name);
                    }
                } else {
                    $errors[] = "Upload directory is not writable.";
                    // Log detailed error: error_log("Upload directory " . UPLOAD_DIR . " is not writable.");
                }
            }
        } else {
            // Handle file upload errors
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
                UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
                UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
                UPLOAD_ERR_NO_FILE    => "No file was uploaded (required for photo post type).",
                UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
            ];
            $error_code = $_FILES['image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;
             // Only add error if it's a photo post and no file was successfully processed.
            if ($post_type === 'photo' && $image_path === null) {
                 $errors[] = $upload_errors[$error_code] ?? "An unknown error occurred during file upload.";
            }
        }
    }
    
    // If no text content for photo or link, set to NULL or empty string based on DB
    if (($post_type === 'photo' || $post_type === 'link') && empty($text_content)) {
        $text_content = null; // Or an empty string '' if your DB column doesn't allow NULL
    }


    // --- Database Insertion ---
    if (empty($errors) && isset($pdo)) {
        try {
            $sql = "INSERT INTO posts (user_id, post_type, text_content, bisaya_content, image_path, link_url, link_title, link_description, link_image_url, created_at) 
                    VALUES (:user_id, :post_type, :text_content, :bisaya_content, :image_path, :link_url, :link_title, :link_description, :link_image_url, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':post_type' => $post_type,
                ':text_content' => $text_content,
                ':bisaya_content' => $bisaya_content,
                ':image_path' => $image_path,
                ':link_url' => $link_url,
                ':link_title' => $link_title,
                ':link_description' => $link_description,
                ':link_image_url' => $link_image_url
            ]);

            // Success
            $_SESSION['feed_message'] = "Post created successfully!";
            $_SESSION['feed_message_type'] = "success";
            header('Location: user_dashboard.php#feed-section'); // Redirect back to the feed
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage(); // Be cautious about showing raw DB errors to users
            // error_log("Database error in create_post.php: " . $e->getMessage());
        }
    }

    // If there were errors, store them in session and redirect back with errors
    if (!empty($errors)) {
        $_SESSION['feed_message'] = implode("<br>", $errors);
        $_SESSION['feed_message_type'] = "error";
        // Optionally, store form data in session to repopulate form
        $_SESSION['form_data'] = $_POST; 
        header('Location: user_dashboard.php#create-post-form'); // Redirect back to the form
        exit;
    }

} else {
    // Not a POST request, redirect or show error
    $_SESSION['feed_message'] = "Invalid request method.";
    $_SESSION['feed_message_type'] = "error";
    header('Location: user_dashboard.php');
    exit;
}

?> 
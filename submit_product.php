<?php
session_start();
include_once 'db_connection.php';

// Constants for product image uploads
define('PRODUCT_UPLOAD_DIR', 'uploads/products/'); // Dedicated directory for product images
define('PRODUCT_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB, same as post images for now
$product_allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$product_allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        $_SESSION['form_submission_message'] = "Authentication required to submit products.";
        $_SESSION['form_submission_message_type'] = "error";
        header('Location: add_product.php'); // Or login.php
        exit;
    }

    $user_id = $_SESSION['user']['id'];
    $errors = [];
    $_SESSION['form_data_product'] = $_POST; // Store submitted data for repopulation on error

    // Retrieve and sanitize form data
    $product_name = trim(filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING));
    $product_description = trim(filter_input(INPUT_POST, 'product_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $product_price = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);

    // --- Validations ---
    if (empty($product_name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($product_description)) {
        $errors[] = "Product description is required.";
    }
    if ($product_price === false || $product_price < 0) {
        $errors[] = "Invalid product price. Must be a non-negative number.";
    }
    if ($stock_quantity === false || $stock_quantity < 0) {
        $errors[] = "Invalid stock quantity. Must be a non-negative integer.";
    }
    if ($category_id === false && !empty($_POST['category_id'])) { // Category ID provided but not a valid int
        $errors[] = "Invalid category selected.";
    } elseif (empty($_POST['category_id'])) { // If category is optional and not selected
        $category_id = null; 
    }
    
    // Validate category_id exists if provided (optional based on your DB schema for products.category_id)
    if ($category_id !== null && isset($pdo)) {
        $stmt_cat_check = $pdo->prepare("SELECT id FROM categories WHERE id = :category_id");
        $stmt_cat_check->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt_cat_check->execute();
        if ($stmt_cat_check->rowCount() == 0) {
            $errors[] = "Selected category does not exist.";
        }
    }

    // --- Image Handling ---
    $image_path_for_db = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['product_image']['tmp_name'];
        $file_name = $_FILES['product_image']['name'];
        $file_size = $_FILES['product_image']['size'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actual_mime_type = finfo_file($finfo, $file_tmp_path);
        finfo_close($finfo);

        if (!in_array($actual_mime_type, $product_allowed_mime_types) || !in_array($file_extension, $product_allowed_extensions)) {
            $errors[] = "Invalid image file type. Only JPG, PNG, GIF, WEBP are allowed.";
        } elseif ($file_size > PRODUCT_MAX_FILE_SIZE) {
            $errors[] = "Image file is too large. Maximum size is " . (PRODUCT_MAX_FILE_SIZE / 1024 / 1024) . "MB.";
        } else {
            $unique_file_name = uniqid('prod_', true) . '.' . $file_extension;
            $destination_path = PRODUCT_UPLOAD_DIR . $unique_file_name;

            if (!is_dir(PRODUCT_UPLOAD_DIR)) {
                if (!mkdir(PRODUCT_UPLOAD_DIR, 0755, true)) {
                    $errors[] = "Failed to create product image directory. Check server permissions.";
                    error_log("Failed to create directory: " . PRODUCT_UPLOAD_DIR);
                }
            }

            if (empty($errors) && is_writable(PRODUCT_UPLOAD_DIR)) { // Check errors again before moving
                if (move_uploaded_file($file_tmp_path, $destination_path)) {
                    $image_path_for_db = $destination_path;
                } else {
                    $errors[] = "Failed to move uploaded product image. Check server permissions.";
                    error_log("Failed to move uploaded file to: " . $destination_path);
                }
            } elseif (empty($errors)) { // is_writable failed
                $errors[] = "Product image upload directory is not writable.";
                 error_log("Product image upload directory is not writable: " . PRODUCT_UPLOAD_DIR);
            }
        }
    } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Other upload errors (e.g., too large as per php.ini, partial upload etc.)
        $upload_errors_map = [
            UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.",
            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder for uploads.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
        ];
        $error_code = $_FILES['product_image']['error'];
        $errors[] = $upload_errors_map[$error_code] ?? "An unknown error occurred during image upload.";
    }

    // --- Database Insertion ---
    if (empty($errors) && isset($pdo)) {
        try {
            $sql = "INSERT INTO products (user_id, name, description, price, category_id, stock_quantity, image_path, created_at, updated_at) 
                    VALUES (:user_id, :name, :description, :price, :category_id, :stock_quantity, :image_path, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':name', $product_name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $product_description, PDO::PARAM_STR);
            $stmt->bindParam(':price', $product_price, PDO::PARAM_STR); // PDO handles float to string for decimal
            $stmt->bindParam(':category_id', $category_id, $category_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':image_path', $image_path_for_db, $image_path_for_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                unset($_SESSION['form_data_product']); // Clear form data on success
                $_SESSION['shop_message'] = "Product added successfully!";
                $_SESSION['shop_message_type'] = "success";
                header('Location: shop_page.php');
                exit;
            } else {
                $errors[] = "Failed to save product to the database.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage(); // Dev only, more generic for prod
            error_log("Database error in submit_product.php: " . $e->getMessage());
        }
    }

    // If there were errors, redirect back to add_product.php
    if (!empty($errors)) {
        $_SESSION['form_submission_message'] = implode("<br>", $errors);
        $_SESSION['form_submission_message_type'] = "error";
        header('Location: add_product.php');
        exit;
    }

} else {
    // Not a POST request, redirect to shop page or add product page
    $_SESSION['shop_message'] = "Invalid request method.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}
?> 
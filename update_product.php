<?php
session_start();
include_once 'db_connection.php';

// Constants for product image uploads (same as submit_product.php)
define('PRODUCT_UPLOAD_DIR', 'uploads/products/');
define('PRODUCT_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
$product_allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$product_allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user'])) {
        $_SESSION['product_form_message'] = "Authentication required to update products.";
        $_SESSION['product_form_message_type'] = "error";
        // Potentially redirect to login or the specific edit page with an error
        // For now, let's assume edit_product.php should have caught this.
        header('Location: login.php'); 
        exit;
    }

    $current_user_id = $_SESSION['user']['id'];
    $errors = [];

    // Retrieve and sanitize form data
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $product_name = trim(filter_input(INPUT_POST, 'product_name', FILTER_SANITIZE_STRING));
    $product_description = trim(filter_input(INPUT_POST, 'product_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $product_price = filter_input(INPUT_POST, 'product_price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $existing_image_path = filter_input(INPUT_POST, 'existing_image_path', FILTER_SANITIZE_STRING);
    $remove_existing_image = isset($_POST['remove_existing_image']) ? (bool)$_POST['remove_existing_image'] : false;

    $new_image_path_for_db = $existing_image_path; // Initialize with current path

    // --- Initial Validations & Authorization --- 
    if (!$product_id) {
        $errors[] = "Invalid Product ID for update.";
    } else {
        // Fetch original product to verify ownership
        try {
            if (!isset($pdo)) {
                 $errors[] = "Database connection is not available.";
            } else {
                $stmt_orig = $pdo->prepare("SELECT user_id, image_path FROM products WHERE id = :id");
                $stmt_orig->bindParam(':id', $product_id, PDO::PARAM_INT);
                $stmt_orig->execute();
                $original_product = $stmt_orig->fetch(PDO::FETCH_ASSOC);

                if (!$original_product) {
                    $errors[] = "Product not found for update.";
                } elseif ($original_product['user_id'] != $current_user_id) {
                    $errors[] = "You are not authorized to update this product.";
                }
                // If $existing_image_path was empty from form but DB has it, use DB one.
                if (empty($existing_image_path) && !empty($original_product['image_path'])){
                    $existing_image_path = $original_product['image_path'];
                    $new_image_path_for_db = $existing_image_path;
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Error fetching original product: " . $e->getMessage();
            error_log("DB Error fetching product in update_product: " . $e->getMessage());
        }
    }

    // --- Field Specific Validations (if no major errors yet) ---
    if (empty($errors)) {
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
        if ($category_id === false && !empty($_POST['category_id'])) {
            $errors[] = "Invalid category selected.";
        } elseif (empty($_POST['category_id'])) {
            $category_id = null;
        }

        if ($category_id !== null && isset($pdo)) {
            $stmt_cat_check = $pdo->prepare("SELECT id FROM categories WHERE id = :category_id");
            $stmt_cat_check->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt_cat_check->execute();
            if ($stmt_cat_check->rowCount() == 0) {
                $errors[] = "Selected category does not exist.";
            }
        }
    }

    // --- Image Handling (if no major errors yet) ---
    if (empty($errors)) {
        if ($remove_existing_image) {
            if (!empty($existing_image_path) && file_exists($existing_image_path)) {
                if (!unlink($existing_image_path)) {
                    $errors[] = "Could not remove existing image. Please check permissions.";
                    error_log("Failed to delete existing product image: " . $existing_image_path);
                }
            }
            $new_image_path_for_db = null; 
        } elseif (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_path = $_FILES['product_image']['tmp_name'];
            $file_name = $_FILES['product_image']['name'];
            $file_size = $_FILES['product_image']['size'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $actual_mime_type = finfo_file($finfo, $file_tmp_path);
            finfo_close($finfo);

            if (!in_array($actual_mime_type, $product_allowed_mime_types) || !in_array($file_extension, $product_allowed_extensions)) {
                $errors[] = "Invalid new image file type. Only JPG, PNG, GIF, WEBP are allowed.";
            } elseif ($file_size > PRODUCT_MAX_FILE_SIZE) {
                $errors[] = "New image file is too large. Maximum size is " . (PRODUCT_MAX_FILE_SIZE / 1024 / 1024) . "MB.";
            } else {
                $unique_file_name = uniqid('prod_', true) . '.' . $file_extension;
                $destination_path = PRODUCT_UPLOAD_DIR . $unique_file_name;

                if (!is_dir(PRODUCT_UPLOAD_DIR)) {
                    if (!mkdir(PRODUCT_UPLOAD_DIR, 0755, true)) {
                        $errors[] = "Failed to create product image directory.";
                         error_log("Failed to create directory: " . PRODUCT_UPLOAD_DIR);
                    }
                }
                
                if (empty($errors) && is_writable(PRODUCT_UPLOAD_DIR)){
                    if (move_uploaded_file($file_tmp_path, $destination_path)) {
                        if (!empty($existing_image_path) && file_exists($existing_image_path)) {
                            if(!unlink($existing_image_path)){
                                 error_log("Failed to delete old product image after new upload: " . $existing_image_path);
                            }
                        }
                        $new_image_path_for_db = $destination_path;
                    } else {
                        $errors[] = "Failed to move new uploaded product image.";
                        error_log("Failed to move uploaded file to: " . $destination_path);
                    }
                } elseif(empty($errors)) {
                     $errors[] = "Product image upload directory is not writable.";
                     error_log("Product image upload directory is not writable: " . PRODUCT_UPLOAD_DIR);
                }
            }
        } // No new image and not removing: $new_image_path_for_db remains $existing_image_path (already set)
    }

    // --- Database Update ---
    if (empty($errors) && isset($pdo)) {
        try {
            $sql = "UPDATE products SET 
                        name = :name,
                        description = :description,
                        price = :price,
                        category_id = :category_id,
                        stock_quantity = :stock_quantity,
                        image_path = :image_path,
                        updated_at = NOW()
                    WHERE id = :product_id AND user_id = :user_id";
            
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':name', $product_name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $product_description, PDO::PARAM_STR);
            $stmt->bindParam(':price', $product_price, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $category_id, $category_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':image_path', $new_image_path_for_db, $new_image_path_for_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['shop_message'] = "Product updated successfully!";
                $_SESSION['shop_message_type'] = "success";
                header('Location: shop_page.php#product-' . $product_id);
                exit;
            } else {
                $errors[] = "Failed to update product in the database.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database update error: " . $e->getMessage();
            error_log("Database update error in update_product.php: " . $e->getMessage());
        }
    }

    // If there were errors, redirect back to edit_product.php
    if (!empty($errors)) {
        $_SESSION['product_form_message'] = implode("<br>", $errors);
        $_SESSION['product_form_message_type'] = "error";
        // Note: $_POST data is not explicitly passed back for repopulation here.
        // edit_product.php re-fetches from DB. For more complex repopulation, 
        // you might store $_POST in session, but file inputs are tricky.
        header('Location: edit_product.php?id=' . $product_id);
        exit;
    }

} else {
    $_SESSION['shop_message'] = "Invalid request method for update.";
    $_SESSION['shop_message_type'] = "error";
    header('Location: shop_page.php');
    exit;
}
?> 
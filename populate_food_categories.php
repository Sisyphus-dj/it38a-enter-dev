<?php
// populate_food_categories.php
// This script populates the 'Food' category and its subcategories.
// Run this script once by accessing it through your browser.

include_once 'db_connection.php'; // Ensure $pdo is available

if (!isset($pdo)) {
    die("Database connection failed. Please check db_connection.php. Make sure this script is in the same directory.");
}

echo "Starting category population script...<br><br>";

$food_category_data = [
    'name' => 'Food',
    'slug' => 'food',
    'description' => 'Various food products',
    'hierarchy_level' => 0,
    'children' => [
        [
            'name' => 'Fruits',
            'slug' => 'fruits',
            'description' => 'Fresh and frozen fruits',
            'hierarchy_level' => 1,
        ],
        [
            'name' => 'Vegetables',
            'slug' => 'vegetables',
            'description' => 'Fresh and frozen vegetables',
            'hierarchy_level' => 1,
        ],
        [
            'name' => 'Grains',
            'slug' => 'grains',
            'description' => 'Cereals, rice, pasta, and other grain products',
            'hierarchy_level' => 1,
        ],
        [
            'name' => 'Protein Foods',
            'slug' => 'protein-foods',
            'description' => 'Meat, poultry, seafood, beans, peas, lentils, nuts, seeds, and soy products',
            'hierarchy_level' => 1,
        ],
        [
            'name' => 'Dairy',
            'slug' => 'dairy',
            'description' => 'Milk, yogurt, cheese, and fortified soy beverages',
            'hierarchy_level' => 1,
        ],
    ]
];

try {
    $pdo->beginTransaction();

    // --- Parent Category: Food ---
    $stmt_check_parent = $pdo->prepare("SELECT id, path FROM categories WHERE slug = :slug");
    $stmt_check_parent->execute([':slug' => $food_category_data['slug']]);
    $parent_category = $stmt_check_parent->fetch(PDO::FETCH_ASSOC);
    
    $parent_id = null;
    $parent_path = '';

    if ($parent_category) {
        $parent_id = $parent_category['id'];
        // Ensure path is correctly formatted if it exists
        $parent_path = rtrim($parent_category['path'], '/') . '/'; 
        echo "Parent category '{$food_category_data['name']}' (Slug: {$food_category_data['slug']}) already exists with ID: $parent_id. Using existing.<br>";
    } else {
        $sql_parent = "INSERT INTO categories (name, slug, description, parent_id, hierarchy_level) 
                       VALUES (:name, :slug, :description, NULL, :hierarchy_level)";
        $stmt_parent = $pdo->prepare($sql_parent);
        $stmt_parent->execute([
            ':name' => $food_category_data['name'],
            ':slug' => $food_category_data['slug'],
            ':description' => $food_category_data['description'],
            ':hierarchy_level' => $food_category_data['hierarchy_level']
        ]);
        $parent_id = $pdo->lastInsertId();
        $parent_path = "/{$parent_id}/";
        
        $stmt_update_path = $pdo->prepare("UPDATE categories SET path = :path WHERE id = :id");
        $stmt_update_path->execute([':path' => $parent_path, ':id' => $parent_id]);
        echo "Parent category '{$food_category_data['name']}' (Slug: {$food_category_data['slug']}) inserted with ID: $parent_id.<br>";
    }

    // --- Child Categories ---
    if ($parent_id) { // Proceed only if parent ID is valid
        foreach ($food_category_data['children'] as $child_data) {
            $stmt_check_child = $pdo->prepare("SELECT id FROM categories WHERE slug = :slug");
            $stmt_check_child->execute([':slug' => $child_data['slug']]);
            $existing_child = $stmt_check_child->fetch(PDO::FETCH_ASSOC);

            if ($existing_child) {
                // Optional: Could also check if it's already a child of *this* parent_id
                // For now, if slug exists, we skip to avoid potential conflicts.
                echo "Subcategory '{$child_data['name']}' (Slug: {$child_data['slug']}) already exists globally. Skipping insertion to avoid conflicts.<br>";
                continue;
            }

            $sql_child = "INSERT INTO categories (name, slug, description, parent_id, hierarchy_level)
                          VALUES (:name, :slug, :description, :parent_id, :hierarchy_level)";
            $stmt_child = $pdo->prepare($sql_child);
            
            $stmt_child->execute([
                ':name' => $child_data['name'],
                ':slug' => $child_data['slug'],
                ':description' => $child_data['description'],
                ':parent_id' => $parent_id,
                ':hierarchy_level' => $child_data['hierarchy_level']
            ]);
            $child_id = $pdo->lastInsertId();
            $child_path = $parent_path . $child_id . '/';

            $stmt_update_child_path = $pdo->prepare("UPDATE categories SET path = :path WHERE id = :id");
            $stmt_update_child_path->execute([':path' => $child_path, ':id' => $child_id]);
            echo "Subcategory '{$child_data['name']}' (Slug: {$child_data['slug']}) inserted with ID: $child_id under parent '{$food_category_data['name']}'.<br>";
        }
    } else {
        echo "Could not determine Parent ID for '{$food_category_data['name']}'. Subcategories not inserted.<br>";
    }
    

    $pdo->commit();
    echo "<br>Food categories and subcategories processed successfully!<br>";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error populating categories: " . $e->getMessage()); // Log detailed error
    echo "<br><strong style='color:red;'>Error populating categories. Check server error logs for details. Message: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
}

echo "<br>Script finished.<br>";
?> 
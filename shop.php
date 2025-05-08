<?php
// shop.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSync - Shop</title>
    <link rel="stylesheet" href="styles.css">
    <script src="scripts.js" defer></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section id="shop">
        <h2>Shop</h2>
        <form id="productForm">
            <input type="text" id="productName" placeholder="Product Name" required>
            <input type="number" id="productPrice" placeholder="Product Price" required>
            <button type="submit">Add Product</button>
        </form>

        <input type="text" id="searchBar" placeholder="Search Products...">
        
        <div id="product-list">
            <!-- Products will be rendered here by JavaScript -->
        </div>

        <div id="totalPrice">
            Total Price: $0.00
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>

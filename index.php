<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSync</title>
    <link rel="stylesheet" href="styles.css">
    
    <!-- ✅ Latest FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>

    <script src="scripts.js" defer></script>
</head>
<body>
    <header>
        <h1>AgriSync</h1>
        <nav>
            <ul>
                <li><a href="#home"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="#about"><i class="fa-solid fa-seedling"></i> About</a></li>
                <li><a href="#contact"><i class="fa-solid fa-envelope"></i> Contact</a></li>
                <li><a href="#profile"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="#shop"><i class="fa-solid fa-store"></i> Shop</a></li>
                <li><a href="#basket"><i class="fa-solid fa-basket-shopping"></i> Basket</a></li>
            </ul>
        </nav>
    </header>

    <!-- Home Section -->
    <section id="home">
        <h2>Welcome to AgriSync</h2>
        <p>Your partner in sustainable farming.</p>

        <h3>Create a New Post</h3>
        <form id="feedForm">
            <input type="text" id="postTitle" placeholder="Post Title" required>
            <textarea id="postContent" placeholder="Post Content" required></textarea>
            <button type="submit">Add Post</button>
        </form>

        <div id="feed">
            <h3>Latest Updates:</h3>

            <ul>
                <li>🌾 New sustainable farming techniques introduced!</li>
                <li>🚜 Upcoming webinar on soil health and crop rotation.</li>
                <li>🌱 Organic fertilizer solutions now available.</li>
                <li>🌍 Join us for World Agriculture Day!</li>
            </ul>
            <div id="post-list">
                <!-- Posts will be rendered here by JavaScript -->
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about">
        <h2>About Us</h2>
        <p>AgriSync is dedicated to providing innovative solutions for modern farmers.</p>
    </section>

    <!-- Shop Section -->
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

    <!-- Profile Section -->
    <section id="profile">
        <h2>My Profile</h2>
        <p>Profile details will be displayed here.</p>
    </section>

    <!-- Basket Section -->
    <section id="basket">
        <h2>Basket</h2>
        <p>Your selected items will appear here.</p>
    </section>

    <!-- Contact Section -->
    <section id="contact">
        <h2>Contact Us</h2>
        <form id="contactForm">
            <input type="text" id="name" placeholder="Your Name">
            <input type="email" id="email" placeholder="Your Email">
            <textarea id="message" placeholder="Your Message"></textarea>
            <button type="submit">Send Message</button>
        </form>
    </section>

    <footer>
        <p>&copy; 2025 AgriSync. All rights reserved.</p>
    </footer>
</body>
</html>

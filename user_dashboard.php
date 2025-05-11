<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSync - Your Partner in Sustainable Farming</title>
    <link rel="stylesheet" href="user_dashboard.css"> 
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar" role="navigation" aria-label="Main Navigation">
    <a href="#" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="#">Home</a></li>

            <li>
                <button aria-haspopup="true" aria-expanded="false">Support</button>
                <div class="dropdown-content" role="menu" aria-label="Support submenu">
                    <a href="#">FAQs</a>
                    <a href="#">Contact Support</a>
                </div>
            </li>

            <li><a href="#">About Us</a></li>
            <li><a href="#" class="shop-btn">Shop</a></li>
            <li><button class="contact-btn">Contact Us</button></li>
        </ul>
    </nav>

    <!-- Spacer to prevent content hiding behind fixed navbar -->
    <div class="nav-spacer"></div>

    <!-- Header Section -->
    <header>
        <h1>AgriSync - Your Partner in Sustainable Farming</h1>
        <h2>Cultivating a Greener Future</h2>
        <div class="fact-box">
            <p>Did you know? Sustainable farming can increase crop production by up to 50% while conserving water and soil health.</p>

    </header>
     </div>
         <div style="text-align: center; margin-top: 20px;">
    <a href="#" class="shop-now-btn">Shop Now</a>
</div>

    <div class="nav-spacer"></div>

    <!-- Content Sections -->
    <main>
        <section class="content-sections">
            <article class="topic">
                <img src="organic-farming.jpg" alt="Lush organic fields">
                <h3>Organic Farming Techniques</h3>
                <p>Tips on how to implement organic practices for better yield.</p>
            </article>

            <article class="topic">
                <img src="soil-health.jpg" alt="Healthy soil with plants growing">
                <h3>Soil Health Management</h3>
                <p>Importance of soil testing and organic amendments.</p>
            </article>

            <article class="topic">
                <img src="water-conservation.jpg" alt="Drip irrigation system">
                <h3>Water Conservation Strategies</h3>
                <p>Techniques to use water more efficiently while farming.</p>
            </article>

            <article class="topic">
                <img src="crop-rotation.jpg" alt="Diverse crops in rotation">
                <h3>Crop Rotation Benefits</h3>
                <p>How rotating crops can enhance soil fertility.</p>
            </article>

            <article class="topic">
                <img src="pest-management.jpg" alt="Beneficial insects in fields">
                <h3>Pest Management Approaches</h3>
                <p>Natural pest control methods that boost biodiversity.</p>
            </article>
        </section>

        <!-- Sign-Up Section -->
        <section class="signup">
            <h2>Join us in making a difference!</h2>
            <form action="#" method="POST">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Enter your name" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>

                <button type="submit">Sign Up</button>
            </form>
        </section>
    </main>

    <!-- Footer Section -->
    <footer>
        <div class="footer-content">
            <img src="logo.png" alt="AgriSync Logo">
            <p>© 2025 AgriSync. All Rights Reserved.</p>
            <nav>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms and Conditions</a>
            </nav>
        </div>
    </footer>
</body>
</html>
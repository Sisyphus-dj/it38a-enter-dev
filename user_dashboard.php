<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
include_once 'db_connection.php'; // Ensure this path is correct and $pdo is initialized in it

if (isset($_SESSION['feed_message'])) {
    $message_type = $_SESSION['feed_message_type'] ?? 'info'; // Default to 'info'
    echo "<div class='feed-alert feed-alert-{$message_type}'>" . $_SESSION['feed_message'] . "</div>";
    unset($_SESSION['feed_message']);
    unset($_SESSION['feed_message_type']);
    if (isset($_SESSION['form_data'])) { // Clear form data if it was stored
        unset($_SESSION['form_data']);
    }
}
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
        <a href="user_dashboard.php" class="app-name">AgriSync</a>
        <ul class="nav-menu">
            <li><a href="user_dashboard.php">Home</a></li>
            <li><a href="shop_page.php" class="shop-btn">Shop</a></li>
            <?php if ($user): ?>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Profile</button>
                <div class="dropdown-content" role="menu" aria-label="Profile submenu">
                    <a href="logout.php">Logout</a>
                </div>
            </li>
            <li>
                <button aria-haspopup="true" aria-expanded="false">Support</button>
                <div class="dropdown-content" role="menu" aria-label="Support submenu">
                    <a href="#">FAQs</a>
                    <a href="#">Customer Support</a>
                </div>
            </li>
            <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
            <?php endif; ?>
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
    <a href="shop_page.php" class="shop-now-btn">Shop Now</a>
</div>

    <div class="nav-spacer"></div>

    <!-- Main Content -->
    <main>
        <!-- New Feed Section -->
        <section class="feed-section">
            <h2>Community Feed</h2>

            <!-- Form to Create New Post -->
            <div class="create-post-form">
                <h3>Share something with the community:</h3>
                <form action="create_post.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                    
                    <div>
                        <label for="post_type">Type of Post:</label>
                        <select name="post_type" id="post_type" required>
                            <option value="fact">Fact/Text</option>
                            <option value="photo">Photo</option>
                            <option value="link">Link</option>
                        </select>
                    </div>

                    <div>
                        <label for="text_content">Your thoughts/fact:</label>
                        <textarea name="text_content" id="text_content" rows="3" placeholder="Share a fact, a thought, or a description for your photo/link..."></textarea>
                    </div>

                    <div class="form-group-photo" style="display: none;">
                        <label for="image_upload">Upload Photo:</label>
                        <input type="file" name="image_upload" id="image_upload" accept="image/*">
                    </div>

                    <div class="form-group-link" style="display: none;">
                        <label for="link_url">Link URL:</label>
                        <input type="url" name="link_url" id="link_url" placeholder="https://example.com">
                    </div>
                    
                    <button type="submit" class="btn">Post to Feed</button>
                </form>
            </div>

            <!-- Display Feed Posts -->
            <div class="feed-posts">
                <h3>Latest Posts</h3>
                <?php
                // Fetch posts from the database
                // Make sure db_connection.php is included if not already
                // (Assuming $pdo is available from db_connection.php which should be included at the top of the file if needed)
                // For now, we are just setting up the structure. The actual fetching logic will depend on db_connection.php being included.
                
                // Ensure db_connection is included. If it's not already, add this at the top with other includes:
                // include_once 'db_connection.php';

                if (isset($pdo)) { // Check if $pdo is available
                    try {
                        $stmt = $pdo->query("SELECT posts.*, users.first_name, users.last_name FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC LIMIT 20");
                        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($posts) > 0) {
                            foreach ($posts as $post) {
                                echo "<article class='feed-post' id='post-" . htmlspecialchars($post['id']) . "'>";
                                echo "<div class='post-header'>";
                                echo "<h4>Posted by: " . htmlspecialchars($post['first_name']) . " " . htmlspecialchars($post['last_name']) . "</h4>";
                                echo "<small>On: " . date("F j, Y, g:i a", strtotime($post['created_at'])) . "</small>";
                                echo "</div>"; // end post-header
                                
                                // Display text content if available
                                if (!empty($post['text_content'])) {
                                    echo "<div class='post-caption'>" . nl2br(htmlspecialchars($post['text_content'])) . "</div>";
                                }

                                // Display based on post type
                                if ($post['post_type'] === 'photo' && !empty($post['image_path'])) {
                                    echo "<div class='post-media'><img src='" . htmlspecialchars($post['image_path']) . "' alt='User uploaded photo'></div>";
                                } elseif ($post['post_type'] === 'link' && !empty($post['link_url'])) {
                                    echo "<div class='post-link-preview'>";
                                    if (!empty($post['link_image_url'])) {
                                        echo "<img src='" . htmlspecialchars($post['link_image_url']) . "' alt='Link preview' class='link-preview-image'>";
                                    }
                                    echo "<div class='link-preview-content'>";
                                    echo "<h5><a href='" . htmlspecialchars($post['link_url']) . "' target='_blank'>" . htmlspecialchars($post['link_title'] ?: $post['link_url']) . "</a></h5>";
                                    if (!empty($post['link_description'])) {
                                        echo "<p>" . nl2br(htmlspecialchars($post['link_description'])) . "</p>";
                                    }
                                    echo "<a href='" . htmlspecialchars($post['link_url']) . "' target='_blank' class='visit-link-btn'>Visit Link</a>";
                                    echo "</div>"; // end link-preview-content
                                    echo "</div>"; // end post-link-preview
                                }

                                // Edit and Delete buttons (only if user owns the post)
                                if (isset($_SESSION['user']['id']) && $post['user_id'] == $_SESSION['user']['id']) {
                                    echo "<div class='post-actions'>";
                                    echo "<a href='edit_post.php?id=" . htmlspecialchars($post['id']) . "' class='btn-edit'>Edit</a>";
                                    echo "<a href='delete_post.php?id=" . htmlspecialchars($post['id']) . "' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this post?\");'>Delete</a>";
                                    echo "</div>";
                                }
                                echo "</article>";
                            }
                        } else {
                            echo "<p>No posts yet. Be the first to share!</p>";
                        }
                    } catch (PDOException $e) {
                        echo "<p>Error fetching posts: " . $e->getMessage() . "</p>"; // Consider logging this instead of showing to user
                    }
                } else {
                    echo "<p>Database connection not available. Posts cannot be displayed.</p>"; // Placeholder
                }
                ?>
            </div>
        </section>
        <script>
            // JavaScript to show/hide Photo and Link fields based on post type selection
            document.addEventListener('DOMContentLoaded', function() {
                const postTypeSelect = document.getElementById('post_type');
                const photoGroup = document.querySelector('.form-group-photo');
                const linkGroup = document.querySelector('.form-group-link');
                const textContentArea = document.getElementById('text_content');

                function toggleFields() {
                    const selectedType = postTypeSelect.value;
                    if (selectedType === 'photo') {
                        photoGroup.style.display = 'block';
                        linkGroup.style.display = 'none';
                        textContentArea.placeholder = 'Optional: Add a description for your photo...';
                    } else if (selectedType === 'link') {
                        photoGroup.style.display = 'none';
                        linkGroup.style.display = 'block';
                        textContentArea.placeholder = 'Optional: Add a description for your link...';
                    } else { // fact or other
                        photoGroup.style.display = 'none';
                        linkGroup.style.display = 'none';
                        textContentArea.placeholder = 'Share a fact or your thoughts...';
                    }
                }
                postTypeSelect.addEventListener('change', toggleFields);
                toggleFields(); // Initial call to set correct fields on page load
            });
        </script>
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
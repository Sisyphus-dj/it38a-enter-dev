<?php
// contact.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriSync - Contact</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section id="contact">
        <h2>Contact Us</h2>
        <form id="contactForm">
            <input type="text" id="name" placeholder="Your Name">
            <input type="email" id="email" placeholder="Your Email">
            <textarea id="message" placeholder="Your Message"></textarea>
            <button type="submit">Send Message</button>
        </form>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>

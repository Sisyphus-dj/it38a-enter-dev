<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once 'db_connection.php';
    include_once 'helpers.php';

    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = sanitize($_POST['address'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $role = sanitize($_POST['role'] ?? 'user'); 
    $error = '';
    $success = '';

    
    if (!$first_name || !$last_name) {
        $error = 'First and Last name are required.';
    } elseif ($age && ($age < 1 || $age > 150)) {
        $error = 'Please enter a valid age.';
    } elseif (!$email) {
        $error = 'Valid email is required.';
    } elseif (!$password) {
        $error = 'Password is required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered. Please login or use another email.';
        } else {
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            
            $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, age, email, password, address, contact_number, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $first_name,
                $last_name,
                $age ?: null,
                $email,
                $hashed_password,
                $address ?: null,
                $contact_number ?: null,
                $role 
            ]);

            $success = 'Registration successful. You can now login.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css"> 
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <div>
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div>
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
            <div>
                <label for="age">Age:</label>
                <input type="number" id="age" name="age">
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div>
                <label for="address">Address:</label>
                <textarea id="address" name="address"></textarea>
            </div>
            <div>
                <label for="contact_number">Contact Number:</label>
                <input type="text" id="contact_number" name="contact_number">
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
        <p class="toggle-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</body>
</html>
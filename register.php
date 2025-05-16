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
            echo "<script>
                alert('Registration successful! You can now login.');
                window.location.href = 'login.php';
            </script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register | AgriSync</title>
  <style>
    body {
      margin: 0;
      background: #eaf7ea;
      font-family: Arial, sans-serif;
      padding-top: 70px; /* Space for fixed header */
    }
    /* Fixed header */
    .header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background: #4e944f;
      color: #fff;
      text-align: center;
      padding: 20px 0 10px 0;
      font-size: 2rem;
      font-weight: bold;
      letter-spacing: 2px;
      z-index: 1000;
      box-shadow: 0 2px 6px rgba(0,0,0,0.07);
    }
    /* Center and widen the form */
    .register-container {
      max-width: 600px;
      margin: 40px auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.07);
      padding: 40px 40px 30px 40px;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }
    .register-title {
      font-size: 2rem;
      font-weight: bold;
      color: #4e944f;
      text-align: center;
      margin-bottom: 25px;
      letter-spacing: 1px;
    }
    .register-form label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #333;
    }
    .register-form input[type="text"],
    .register-form input[type="email"],
    .register-form input[type="password"],
    .register-form input[type="number"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #a6d8a8;
      border-radius: 5px;
      font-size: 1rem;
      box-sizing: border-box;
      background: #f7fff7;
    }
    .register-form input[type="submit"] {
      background: #4e944f;
      color: #fff;
      border: none;
      padding: 12px;
      border-radius: 5px;
      font-size: 1.1rem;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
    }
    .register-form input[type="submit"]:hover {
      background: #357a38;
    }
    @media (max-width: 700px) {
      .register-container {
        max-width: 95vw;
        padding: 20px 5vw;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    AgriSync
  </div>

  <div class="register-container">
    <div class="register-title">REGISTER</div>
    <form class="register-form" method="post" action="register.php">
      <label for="first_name">First Name:</label>
      <input type="text" name="first_name" id="first_name" required>

      <label for="last_name">Last Name:</label>
      <input type="text" name="last_name" id="last_name" required>

      <label for="age">Age:</label>
      <input type="number" name="age" id="age" min="1" required>

      <label for="email">Email:</label>
      <input type="email" name="email" id="email" required>

      <label for="contact_number">Contact Number:</label>
      <input type="text" name="contact_number" id="contact_number" required>

      <label for="address">Address:</label>
      <input type="text" name="address" id="address" required>

      <label for="password">Password:</label>
      <input type="password" name="password" id="password" required>

      <label for="confirm_password">Confirm Password:</label>
      <input type="password" name="confirm_password" id="confirm_password" required>

      <input type="submit" value="Register">
    </form>
    <p class="toggle-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
  </div>
</body>
</html>


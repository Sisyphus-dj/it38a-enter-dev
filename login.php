<?php
session_start();
include_once 'db_connection.php';
include_once 'helpers.php';

$error = ''; // Initialize error variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) {
        $error = 'Email is required and must be a valid email address.';
    } elseif (empty($password)) {
        $error = 'Password is required.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php'); 
            } else {
                header('Location: user_dashboard.php'); 
            }
            exit;
        } else {
            // Check if the email exists to give a more specific error
            $stmt_check_email = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetch()) {
                $error = 'Incorrect password.';
            } else {
                $error = 'Invalid email or password.'; // Or more generic: 'Account not found or incorrect password.'
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4F8CFF;
            --background-color: #16161a;
            --card-bg: #232946;
            --text-color: #eaeaea;
            --border-color: #2e2e3a;
            --card-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        body {
            background: var(--background-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .container {
            max-width: 400px;
            margin: 60px auto;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            padding: 32px 28px;
        }
        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 24px;
        }
        form div {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 6px;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background: #1a1a2e;
            color: var(--text-color);
        }
        .btn {
            width: 100%;
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover {
            background: #38bdf8;
        }
        .toggle-link {
            text-align: center;
            margin-top: 18px;
        }
        .toggle-link a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .toggle-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form action="login.php" method="POST">
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <p class="toggle-link">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>

    <?php if (!empty($error)): ?>
    <script>
        alert('<?php echo $error; ?>');
    </script>
    <?php endif; ?>

</body>
</html>
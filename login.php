<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check for login attempt
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db_connect.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "All fields are required";
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Set a secure cookie for persistent login
                $token = bin2hex(random_bytes(32)); // Generate a secure random token
                $expiry = time() + (10 * 60); // 10 minutes from now
                setcookie('session_token', $token, [
                    'expires' => $expiry,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => true,
                    'samesite' => 'Strict'
                ]);

                // Store the token in the database
                $token_stmt = $conn->prepare("UPDATE users SET session_token = ?, token_expiry = FROM_UNIXTIME(?) WHERE id = ?");
                $token_stmt->bind_param("sii", $token, $expiry, $user['id']);
                $token_stmt->execute();
                $token_stmt->close();

                // Update last_login timestamp
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: users.php");
                } elseif ($user['role'] === 'manager') {
                    header("Location: dashboard.php");
                } elseif ($user['role'] === 'staff') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini-Mart Inventory - Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">

</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Mini-Mart Inventory</h1>
                <p>Sign in to manage your inventory</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required />
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p>&copy; 2025 Mini-Mart Inventory System</p>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>

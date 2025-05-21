<?php
session_start();

// Check if logout action is requested
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // Clear the session token from database if it exists
    if (isset($_SESSION['user_id'])) {
        require_once 'includes/db_connect.php';
        $stmt = $conn->prepare("UPDATE users SET session_token = NULL, token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }

    // Clear the session cookie
    setcookie('session_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => true,
        'samesite' => 'Strict'
    ]);

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: login.php");
    exit();
} else {
    // If user is logged in and tries to access logout.php without logout action, redirect to dashboard
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    } else {
        // If user is not logged in, redirect to login page
        header("Location: login.php");
        exit();
    }
}
?>

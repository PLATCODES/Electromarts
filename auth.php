<?php
require_once 'config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Check if user is regular user
function isUser() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'user';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login first";
        header('Location: ../login.php');
        exit();
    }
}

// Redirect admin to admin area
function redirectIfAdmin() {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
        exit();
    }
}

// Redirect user to user area
function redirectIfUser() {
    if (isUser()) {
        header('Location: user/dashboard.php');
        exit();
    }
}

// Restrict access - only for admin
function adminOnly() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = "Access denied. Admin only.";
        header('Location: ../index.php');
        exit();
    }
}

// Restrict access - only for users
function userOnly() {
    requireLogin();
    if (!isUser()) {
        $_SESSION['error'] = "Access denied. Users only.";
        header('Location: ../index.php');
        exit();
    }
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        global $conn;
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT * FROM users WHERE id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        return mysqli_fetch_assoc($result);
    }
    return null;
}
?>

<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    // Add system log for logout
    addSystemLog($user_id, 'LOGOUT', 'User logged out: ' . $username);
    
    // Clear cart data from local storage (via JavaScript later)
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Delete session cookie if it exists
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
}

// Redirect to login page with success message
$_SESSION['success'] = "You have been logged out successfully.";
header('Location: ../login.php');
exit();
?>

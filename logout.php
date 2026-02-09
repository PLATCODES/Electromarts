<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    addSystemLog($user_id, 'LOGOUT', 'User logged out');
    
    // Clear all session variables
    session_unset();
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
header('Location: login.php');
exit();
?>

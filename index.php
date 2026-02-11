<?php
require_once '../includes/auth.php';

// If already logged in as admin, redirect to dashboard
if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// If logged in but not admin, redirect to home
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// If not logged in, redirect to login
header('Location: ../login.php');
exit();
?>

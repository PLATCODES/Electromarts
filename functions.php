<?php
require_once 'config.php';

// Sanitize input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($data))));
}

// Generate unique order number
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
}

// Get all categories
function getAllCategories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get all devices with category name
function getAllDevices() {
    global $conn;
    $sql = "SELECT d.*, c.name as category_name 
            FROM devices d 
            LEFT JOIN categories c ON d.category = c.name 
            WHERE d.is_available = 1 
            ORDER BY d.created_at DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get device by ID
function getDeviceById($id) {
    global $conn;
    $id = sanitize($id);
    $sql = "SELECT d.*, c.name as category_name 
            FROM devices d 
            LEFT JOIN categories c ON d.category = c.name 
            WHERE d.id = '$id'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Get devices by category
function getDevicesByCategory($category) {
    global $conn;
    $category = sanitize($category);
    $sql = "SELECT * FROM devices WHERE category = '$category' AND is_available = 1";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get user orders with details
function getUserOrders($user_id) {
    global $conn;
    $user_id = sanitize($user_id);
    $sql = "SELECT o.*, 
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
            FROM orders o 
            WHERE o.user_id = '$user_id' 
            ORDER BY o.order_date DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get order details with items
function getOrderDetails($order_id) {
    global $conn;
    $order_id = sanitize($order_id);
    
    // Get order info
    $sql = "SELECT o.*, u.username, u.email, u.full_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = '$order_id'";
    $order = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    
    // Get order items
    $sql = "SELECT oi.*, d.name as device_name, d.brand 
            FROM order_items oi 
            JOIN devices d ON oi.device_id = d.id 
            WHERE oi.order_id = '$order_id'";
    $items = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
    
    return ['order' => $order, 'items' => $items];
}

// Get all orders for admin
function getAllOrders() {
    global $conn;
    $sql = "SELECT o.*, u.username, u.full_name,
                   (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.order_date DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get all users (for admin)
function getAllUsers() {
    global $conn;
    $sql = "SELECT * FROM users ORDER BY created_at DESC";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Get user by ID
function getUserById($id) {
    global $conn;
    $id = sanitize($id);
    $sql = "SELECT * FROM users WHERE id = '$id'";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Get system logs (for admin)
function getSystemLogs($limit = 50) {
    global $conn;
    $limit = sanitize($limit);
    $sql = "SELECT l.*, u.username 
            FROM system_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC 
            LIMIT $limit";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Add system log
function addSystemLog($user_id, $action, $description = '') {
    global $conn;
    $user_id = sanitize($user_id);
    $action = sanitize($action);
    $description = sanitize($description);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO system_logs (user_id, action, description, ip_address) 
            VALUES ('$user_id', '$action', '$description', '$ip_address')";
    return mysqli_query($conn, $sql);
}
?>

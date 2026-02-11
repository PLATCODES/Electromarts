<?php
require_once '../includes/auth.php';
adminOnly();

require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = sanitize($_POST['order_id']);
    $status = sanitize($_POST['status']);
    
    $sql = "UPDATE orders SET status = '$status' WHERE id = '$order_id'";
    if (mysqli_query($conn, $sql)) {
        addSystemLog($_SESSION['user_id'], 'UPDATE_ORDER_STATUS', 'Updated order ' . $order_id . ' to ' . $status);
        $message = "Order status updated successfully";
    } else {
        $error = "Error updating order: " . mysqli_error($conn);
    }
}

// Handle delete order
if (isset($_GET['delete'])) {
    $order_id = sanitize($_GET['delete']);
    
    // First delete order items
    $sql1 = "DELETE FROM order_items WHERE order_id = '$order_id'";
    $sql2 = "DELETE FROM orders WHERE id = '$order_id'";
    
    if (mysqli_query($conn, $sql1) && mysqli_query($conn, $sql2)) {
        addSystemLog($_SESSION['user_id'], 'DELETE_ORDER', 'Deleted order ID: ' . $order_id);
        $message = "Order deleted successfully";
    } else {
        $error = "Error deleting order: " . mysqli_error($conn);
    }
}

// Get all orders
$orders = getAllOrders();

// Get order details if viewing single order
$order_details = null;
if (isset($_GET['view'])) {
    $order_id = sanitize($_GET['view']);
    $order_details = getOrderDetails($order_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - EOS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_devices.php"><i class="fas fa-laptop"></i> Manage Devices</a></li>
                <li><a href="manage_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
            <div style="margin-top: 2rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 5px;">
                <p style="font-size: 0.9rem;">Logged in as: <strong><?php echo $_SESSION['username']; ?></strong></p>
                <p style="font-size: 0.9rem;">Role: <span class="status-badge status-approved">Admin</span></p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>
                <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['view']) && $order_details): ?>
                <!-- Order Details View -->
                <div class="table-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2><i class="fas fa-file-invoice"></i> Order Details: <?php echo $order_details['order']['order_number']; ?></h2>
                        <a href="manage_orders.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                    </div>
                    
                    <!-- Order Information -->
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 2rem;">
                        <h3>Order Information</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong>Order Number:</strong><br>
                                <?php echo $order_details['order']['order_number']; ?>
                            </div>
                            <div>
                                <strong>Customer:</strong><br>
                                <?php echo $order_details['order']['full_name']; ?><br>
                                <?php echo $order_details['order']['email']; ?>
                            </div>
                            <div>
                                <strong>Order Date:</strong><br>
                                <?php echo date('F d, Y H:i', strtotime($order_details['order']['order_date'])); ?>
                            </div>
                            <div>
                                <strong>Total Amount:</strong><br>
                                <span style="font-size: 1.2rem; font-weight: bold; color: #2ecc71;">
                                    $<?php echo $order_details['order']['total_amount']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Update Status Form -->
                        <form method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="order_id" value="<?php echo $_GET['view']; ?>">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <strong>Current Status:</strong>
                                <span class="status-badge status-<?php echo $order_details['order']['status']; ?>" style="font-size: 1rem;">
                                    <?php echo ucfirst($order_details['order']['status']); ?>
                                </span>
                                <select name="status" style="padding: 0.5rem;">
                                    <option value="pending" <?php echo $order_details['order']['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $order_details['order']['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $order_details['order']['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="completed" <?php echo $order_details['order']['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <button type="submit" name="update_status" class="btn">
                                    <i class="fas fa-sync"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Order Items -->
                    <h3>Order Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Brand</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_details['items'] as $item): ?>
                            <tr>
                                <td><?php echo $item['device_name']; ?></td>
                                <td><?php echo $item['brand']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo $item['unit_price']; ?></td>
                                <td>$<?php echo $item['subtotal']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php else: ?>
                <!-- Orders List -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_number']; ?></td>
                                <td><?php echo $order['full_name'] ?: $order['username']; ?></td>
                                <td><?php echo $order['item_count']; ?> item(s)</td>
                                <td>$<?php echo $order['total_amount']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="?view=<?php echo $order['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 0.3rem; font-size: 0.9rem;">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $order['status'] == 'approved' ? 'selected' : ''; ?>>Approve</option>
                                                <option value="rejected" <?php echo $order['status'] == 'rejected' ? 'selected' : ''; ?>>Reject</option>
                                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Complete</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                        <a href="?delete=<?php echo $order['id']; ?>" 
                                           class="btn btn-danger" 
                                           style="padding: 0.3rem 0.8rem; font-size: 0.9rem;"
                                           onclick="return confirmDelete('Are you sure you want to delete this order?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../script.js"></script>
</body>
</html>

<?php
require_once '../includes/auth.php';
userOnly();

require_once '../includes/functions.php';

// Get user's orders
$orders = getUserOrders($_SESSION['user_id']);

// Get order details if viewing single order
$order_details = null;
if (isset($_GET['view'])) {
    $order_id = sanitize($_GET['view']);
    $order_details = getOrderDetails($order_id);
    
    // Verify that the order belongs to the current user
    if ($order_details['order']['user_id'] != $_SESSION['user_id']) {
        $_SESSION['error'] = "Access denied";
        header('Location: my_orders.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - EOS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3><i class="fas fa-user-circle"></i> User Panel</h3>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="place_order.php"><i class="fas fa-cart-plus"></i> Place Order</a></li>
                <li><a href="my_orders.php" class="active"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
            <div style="margin-top: 2rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 5px;">
                <?php
                $user = getCurrentUser();
                ?>
                <p style="font-size: 0.9rem;">Welcome, <strong><?php echo $user['full_name'] ?: $user['username']; ?></strong></p>
                <p style="font-size: 0.9rem;">Role: <span class="status-badge" style="background: #9b59b6;">User</span></p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
                <div>
                    <a href="place_order.php" class="btn btn-success"><i class="fas fa-plus"></i> New Order</a>
                    <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['view']) && $order_details): ?>
                <!-- Order Details View -->
                <div class="table-container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2><i class="fas fa-file-invoice"></i> Order Details: <?php echo $order_details['order']['order_number']; ?></h2>
                        <a href="my_orders.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Orders</a>
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
                                <strong>Order Date:</strong><br>
                                <?php echo date('F d, Y H:i', strtotime($order_details['order']['order_date'])); ?>
                            </div>
                            <div>
                                <strong>Status:</strong><br>
                                <span class="status-badge status-<?php echo $order_details['order']['status']; ?>">
                                    <?php echo ucfirst($order_details['order']['status']); ?>
                                </span>
                            </div>
                            <div>
                                <strong>Total Amount:</strong><br>
                                <span style="font-size: 1.2rem; font-weight: bold; color: #2ecc71;">
                                    $<?php echo $order_details['order']['total_amount']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($order_details['order']['notes']): ?>
                            <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 5px;">
                                <strong>Notes:</strong><br>
                                <?php echo nl2br(htmlspecialchars($order_details['order']['notes'])); ?>
                            </div>
                        <?php endif; ?>
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
                        <tfoot>
                            <tr style="background: #f8f9fa;">
                                <td colspan="4" style="text-align: right; font-weight: bold;">Total:</td>
                                <td style="font-weight: bold; color: #2ecc71;">
                                    $<?php echo $order_details['order']['total_amount']; ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
            <?php else: ?>
                <!-- Orders List -->
                <div class="table-container">
                    <?php if (empty($orders)): ?>
                        <div style="text-align: center; padding: 3rem;">
                            <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                            <h3>No Orders Yet</h3>
                            <p style="color: #7f8c8d;">You haven't placed any orders yet.</p>
                            <a href="place_order.php" class="btn btn-success" style="margin-top: 1rem;">
                                <i class="fas fa-cart-plus"></i> Place Your First Order
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order #</th>
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
                                    <td><?php echo $order['item_count']; ?> item(s)</td>
                                    <td>$<?php echo $order['total_amount']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <a href="?view=<?php echo $order['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../script.js"></script>
</body>
</html>

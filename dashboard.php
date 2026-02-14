<?php
require_once '../includes/auth.php';
userOnly();

require_once '../includes/functions.php';

// Get user info
$user = getCurrentUser();

// Get user's recent orders
$orders = getUserOrders($_SESSION['user_id']);
$recent_orders = array_slice($orders, 0, 3);

// Get all devices
$devices = getAllDevices();
$recent_devices = array_slice($devices, 0, 6);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - EOS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3><i class="fas fa-user-circle"></i> User Panel</h3>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="place_order.php"><i class="fas fa-cart-plus"></i> Place Order</a></li>
                <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
            <div style="margin-top: 2rem; padding: 1rem; background: rgba(255,255,255,0.1); border-radius: 5px;">
                <p style="font-size: 0.9rem;">Welcome, <strong><?php echo $user['full_name'] ?: $user['username']; ?></strong></p>
                <p style="font-size: 0.9rem;">Role: <span class="status-badge" style="background: #9b59b6;">User</span></p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-tachometer-alt"></i> My Dashboard</h1>
                <a href="../index.php" class="btn"><i class="fas fa-home"></i> Home</a>
            </div>

            <!-- User Info -->
            <div style="background: white; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2><i class="fas fa-user"></i> My Account</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div>
                        <strong>Username:</strong><br>
                        <?php echo $user['username']; ?>
                    </div>
                    <div>
                        <strong>Email:</strong><br>
                        <?php echo $user['email']; ?>
                    </div>
                    <div>
                        <strong>Full Name:</strong><br>
                        <?php echo $user['full_name'] ?: 'Not set'; ?>
                    </div>
                    <div>
                        <strong>Phone:</strong><br>
                        <?php echo $user['phone'] ?: 'Not set'; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo count($orders); ?></h3>
                    <p><i class="fas fa-shopping-bag"></i> Total Orders</p>
                </div>
                <div class="stat-card">
                    <?php
                    $pending = array_filter($orders, function($order) {
                        return $order['status'] == 'pending';
                    });
                    ?>
                    <h3><?php echo count($pending); ?></h3>
                    <p><i class="fas fa-clock"></i> Pending Orders</p>
                </div>
                <div class="stat-card">
                    <?php
                    $approved = array_filter($orders, function($order) {
                        return $order['status'] == 'approved';
                    });
                    ?>
                    <h3><?php echo count($approved); ?></h3>
                    <p><i class="fas fa-check-circle"></i> Approved Orders</p>
                </div>
                <div class="stat-card">
                    <?php
                    $completed = array_filter($orders, function($order) {
                        return $order['status'] == 'completed';
                    });
                    ?>
                    <h3><?php echo count($completed); ?></h3>
                    <p><i class="fas fa-check-double"></i> Completed Orders</p>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="table-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2><i class="fas fa-history"></i> Recent Orders</h2>
                    <a href="my_orders.php" class="btn">View All Orders</a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <p style="text-align: center; padding: 2rem;">You haven't placed any orders yet. <a href="place_order.php">Place your first order!</a></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
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
                                    <a href="my_orders.php?view=<?php echo $order['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Featured Devices -->
            <div style="margin-top: 2rem;">
                <h2><i class="fas fa-bolt"></i> Featured Devices</h2>
                <div class="device-grid">
                    <?php foreach ($recent_devices as $device): ?>
                    <div class="device-card">
                        <div class="device-image">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <div class="device-content">
                            <h3><?php echo $device['name']; ?></h3>
                            <p style="color: #666; font-size: 0.9rem;"><?php echo substr($device['description'], 0, 60); ?>...</p>
                            <div class="device-price">$<?php echo $device['price']; ?></div>
                            <div class="device-stock">Stock: <?php echo $device['stock']; ?> available</div>
                            <div style="margin-top: 0.5rem;">
                                <?php if ($device['is_available']): ?>
                                    <a href="place_order.php?device=<?php echo $device['id']; ?>" class="btn" style="width: 100%;">
                                        <i class="fas fa-cart-plus"></i> Order Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn" style="width: 100%; background: #95a5a6; cursor: not-allowed;" disabled>
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
</body>
</html>

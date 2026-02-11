<?php
require_once '../includes/auth.php';
adminOnly();

require_once '../includes/functions.php';

// Get statistics
$stats = array();

// Total users
$sql = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
$result = mysqli_query($conn, $sql);
$stats['total_users'] = mysqli_fetch_assoc($result)['total'];

// Total devices
$sql = "SELECT COUNT(*) as total FROM devices";
$result = mysqli_query($conn, $sql);
$stats['total_devices'] = mysqli_fetch_assoc($result)['total'];

// Total orders
$sql = "SELECT COUNT(*) as total FROM orders";
$result = mysqli_query($conn, $sql);
$stats['total_orders'] = mysqli_fetch_assoc($result)['total'];

// Pending orders
$sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
$result = mysqli_query($conn, $sql);
$stats['pending_orders'] = mysqli_fetch_assoc($result)['total'];

// Get recent orders
$recent_orders = getAllOrders();
$recent_orders = array_slice($recent_orders, 0, 5);

// Get recent users
$recent_users = getAllUsers();
$recent_users = array_slice($recent_users, 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EOS</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_devices.php"><i class="fas fa-laptop"></i> Manage Devices</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Manage Orders</a></li>
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
                <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                <a href="../index.php" class="btn"><i class="fas fa-home"></i> Visit Site</a>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p><i class="fas fa-users"></i> Total Users</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['total_devices']; ?></h3>
                    <p><i class="fas fa-laptop"></i> Total Devices</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['total_orders']; ?></h3>
                    <p><i class="fas fa-shopping-cart"></i> Total Orders</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_orders']; ?></h3>
                    <p><i class="fas fa-clock"></i> Pending Orders</p>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="table-container">
                <h2><i class="fas fa-clock"></i> Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
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
                            <td><?php echo $order['full_name'] ?: $order['username']; ?></td>
                            <td>$<?php echo $order['total_amount']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <a href="manage_orders.php?view=<?php echo $order['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="manage_orders.php" class="btn">View All Orders</a>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="table-container" style="margin-top: 2rem;">
                <h2><i class="fas fa-user-plus"></i> Recent Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['full_name'] ?: 'Not set'; ?></td>
                            <td><?php echo $user['phone'] ?: 'Not set'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="manage_users.php" class="btn">Manage All Users</a>
                </div>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
</body>
</html>

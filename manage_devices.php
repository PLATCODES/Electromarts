<?php
require_once '../includes/auth.php';
adminOnly();

require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle add device
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_device'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $price = sanitize($_POST['price']);
    $stock = sanitize($_POST['stock']);
    
    if (empty($name) || empty($price)) {
        $error = "Name and price are required";
    } else {
        $sql = "INSERT INTO devices (name, description, category, brand, model, price, stock) 
                VALUES ('$name', '$description', '$category', '$brand', '$model', '$price', '$stock')";
        
        if (mysqli_query($conn, $sql)) {
            $device_id = mysqli_insert_id($conn);
            addSystemLog($_SESSION['user_id'], 'ADD_DEVICE', 'Added device: ' . $name);
            $message = "Device added successfully";
        } else {
            $error = "Error adding device: " . mysqli_error($conn);
        }
    }
}

// Handle delete device
if (isset($_GET['delete'])) {
    $device_id = sanitize($_GET['delete']);
    
    // Check if device exists in orders
    $check_sql = "SELECT COUNT(*) as count FROM order_items WHERE device_id = '$device_id'";
    $check_result = mysqli_query($conn, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if ($check_data['count'] > 0) {
        $error = "Cannot delete device that has existing orders. Mark as unavailable instead.";
    } else {
        $sql = "DELETE FROM devices WHERE id = '$device_id'";
        if (mysqli_query($conn, $sql)) {
            addSystemLog($_SESSION['user_id'], 'DELETE_DEVICE', 'Deleted device ID: ' . $device_id);
            $message = "Device deleted successfully";
        } else {
            $error = "Error deleting device: " . mysqli_error($conn);
        }
    }
}

// Handle update device status (available/unavailable)
if (isset($_GET['toggle'])) {
    $device_id = sanitize($_GET['toggle']);
    
    // Get current status
    $sql = "SELECT is_available FROM devices WHERE id = '$device_id'";
    $result = mysqli_query($conn, $sql);
    $device = mysqli_fetch_assoc($result);
    
    $new_status = $device['is_available'] ? 0 : 1;
    $status_text = $new_status ? 'available' : 'unavailable';
    
    $sql = "UPDATE devices SET is_available = '$new_status' WHERE id = '$device_id'";
    if (mysqli_query($conn, $sql)) {
        addSystemLog($_SESSION['user_id'], 'UPDATE_DEVICE_STATUS', 'Marked device as ' . $status_text . ' ID: ' . $device_id);
        $message = "Device marked as " . $status_text;
    }
}

// Get all devices
$devices = getAllDevices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Devices - EOS</title>
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
                <li><a href="manage_devices.php" class="active"><i class="fas fa-laptop"></i> Manage Devices</a></li>
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
                <h1><i class="fas fa-laptop"></i> Manage Devices</h1>
                <div>
                    <button onclick="document.getElementById('addDeviceModal').style.display='block'" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add New Device
                    </button>
                    <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Device Modal -->
            <div id="addDeviceModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
                <div style="background-color: white; margin: 10% auto; padding: 2rem; width: 80%; max-width: 500px; border-radius: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h2><i class="fas fa-plus"></i> Add New Device</h2>
                        <button onclick="document.getElementById('addDeviceModal').style.display='none'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Device Name *</label>
                            <input type="text" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category">
                                    <option value="Computers">Computers</option>
                                    <option value="Phones">Phones</option>
                                    <option value="Audio">Audio</option>
                                    <option value="Gaming">Gaming</option>
                                    <option value="Tablets">Tablets</option>
                                    <option value="Accessories">Accessories</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model">
                            </div>
                            
                            <div class="form-group">
                                <label>Price ($) *</label>
                                <input type="number" name="price" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock" value="0">
                        </div>
                        
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" name="add_device" class="btn btn-success">
                                <i class="fas fa-save"></i> Save Device
                            </button>
                            <button type="button" onclick="document.getElementById('addDeviceModal').style.display='none'" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Devices Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><?php echo $device['id']; ?></td>
                            <td><?php echo $device['name']; ?></td>
                            <td><?php echo $device['category']; ?></td>
                            <td><?php echo $device['brand'] ?: 'N/A'; ?></td>
                            <td>$<?php echo $device['price']; ?></td>
                            <td><?php echo $device['stock']; ?></td>
                            <td>
                                <?php if ($device['is_available']): ?>
                                    <span class="status-badge status-approved">Available</span>
                                <?php else: ?>
                                    <span class="status-badge status-rejected">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($device['created_at'])); ?></td>
                            <td>
                                <a href="?toggle=<?php echo $device['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                    <?php if ($device['is_available']): ?>
                                        <i class="fas fa-eye-slash" title="Mark as unavailable"></i>
                                    <?php else: ?>
                                        <i class="fas fa-eye" title="Mark as available"></i>
                                    <?php endif; ?>
                                </a>
                                <a href="?edit=<?php echo $device['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $device['id']; ?>" 
                                   class="btn btn-danger" 
                                   style="padding: 0.3rem 0.8rem; font-size: 0.9rem;"
                                   onclick="return confirmDelete('Are you sure you want to delete this device?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('addDeviceModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<?php
require_once '../includes/auth.php';
userOnly();

require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $device_id = sanitize($_POST['device_id']);
    $quantity = sanitize($_POST['quantity']);
    $notes = sanitize($_POST['notes']);
    
    // Get device details
    $device = getDeviceById($device_id);
    
    if (!$device) {
        $error = "Device not found";
    } elseif ($device['stock'] < $quantity) {
        $error = "Not enough stock available. Available: " . $device['stock'];
    } elseif ($quantity <= 0) {
        $error = "Quantity must be at least 1";
    } else {
        // Calculate total
        $total = $device['price'] * $quantity;
        
        // Generate order number
        $order_number = generateOrderNumber();
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create order
            $sql1 = "INSERT INTO orders (user_id, order_number, total_amount, notes) 
                     VALUES ('{$_SESSION['user_id']}', '$order_number', '$total', '$notes')";
            mysqli_query($conn, $sql1);
            $order_id = mysqli_insert_id($conn);
            
            // Add order item
            $sql2 = "INSERT INTO order_items (order_id, device_id, quantity, unit_price, subtotal) 
                     VALUES ('$order_id', '$device_id', '$quantity', '{$device['price']}', '$total')";
            mysqli_query($conn, $sql2);
            
            // Update device stock
            $new_stock = $device['stock'] - $quantity;
            $sql3 = "UPDATE devices SET stock = '$new_stock' WHERE id = '$device_id'";
            mysqli_query($conn, $sql3);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Add system log
            addSystemLog($_SESSION['user_id'], 'PLACE_ORDER', 'Placed order #' . $order_number . ' for ' . $quantity . ' x ' . $device['name']);
            
            $message = "Order placed successfully! Your order number is: <strong>$order_number</strong>";
            
            // Clear form
            $_POST = array();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}

// Get all available devices
$devices = getAllDevices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - EOS</title>
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
                <li><a href="place_order.php" class="active"><i class="fas fa-cart-plus"></i> Place Order</a></li>
                <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
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
                <h1><i class="fas fa-cart-plus"></i> Place New Order</h1>
                <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Order Form -->
                <div>
                    <div class="table-container">
                        <h2><i class="fas fa-shopping-cart"></i> Order Form</h2>
                        
                        <form method="POST" onsubmit="return validateForm('order-form')" id="order-form">
                            <div class="form-group">
                                <label for="device_id"><i class="fas fa-laptop"></i> Select Device *</label>
                                <select id="device_id" name="device_id" required onchange="updateDeviceInfo(this.value)">
                                    <option value="">-- Select a device --</option>
                                    <?php foreach ($devices as $device): ?>
                                        <?php if ($device['is_available'] && $device['stock'] > 0): ?>
                                            <option value="<?php echo $device['id']; ?>" 
                                                    data-price="<?php echo $device['price']; ?>"
                                                    data-stock="<?php echo $device['stock']; ?>">
                                                <?php echo $device['name']; ?> - $<?php echo $device['price']; ?> (Stock: <?php echo $device['stock']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity"><i class="fas fa-boxes"></i> Quantity *</label>
                                <input type="number" id="quantity" name="quantity" min="1" value="1" required oninput="calculateTotal()">
                                <small>Available: <span id="stock-info">0</span></small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Unit Price</label>
                                <input type="text" id="unit_price" value="0" readonly style="background: #f5f5f5;">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calculator"></i> Total Amount</label>
                                <input type="text" id="total_amount" value="0" readonly style="background: #f5f5f5; font-size: 1.2rem; font-weight: bold;">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions..."></textarea>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Available Devices -->
                <div>
                    <div class="table-container">
                        <h2><i class="fas fa-list"></i> Available Devices</h2>
                        
                        <div style="margin-bottom: 1rem;">
                            <input type="text" id="search-devices" placeholder="Search devices..." style="width: 100%; padding: 0.5rem;" onkeyup="searchDevices()">
                        </div>
                        
                        <div id="devices-list" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($devices as $device): ?>
                                <?php if ($device['is_available'] && $device['stock'] > 0): ?>
                                    <div class="device-card" style="margin-bottom: 1rem; cursor: pointer;" 
                                         onclick="selectDevice(<?php echo $device['id']; ?>, <?php echo $device['price']; ?>, <?php echo $device['stock']; ?>)"
                                         data-name="<?php echo strtolower($device['name']); ?>"
                                         data-desc="<?php echo strtolower($device['description']); ?>">
                                        <div style="display: flex; gap: 1rem; align-items: center;">
                                            <div style="width: 60px; height: 60px; background: #3498db; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: white;">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0;"><?php echo $device['name']; ?></h4>
                                                <p style="margin: 0.2rem 0; color: #666; font-size: 0.9rem;"><?php echo substr($device['description'], 0, 80); ?>...</p>
                                                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                                                    <span style="font-weight: bold; color: #2ecc71;">$<?php echo $device['price']; ?></span>
                                                    <span style="color: #7f8c8d;">Stock: <?php echo $device['stock']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php
                            $available_devices = array_filter($devices, function($device) {
                                return $device['is_available'] && $device['stock'] > 0;
                            });
                            
                            if (empty($available_devices)): ?>
                                <p style="text-align: center; padding: 2rem; color: #7f8c8d;">
                                    <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                                    No devices available at the moment.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
    <script>
        // Device selection functionality
        function selectDevice(deviceId, price, stock) {
            document.getElementById('device_id').value = deviceId;
            document.getElementById('unit_price').value = '$' + price;
            document.getElementById('stock-info').textContent = stock;
            document.getElementById('quantity').max = stock;
            calculateTotal();
            
            // Highlight selected device
            document.querySelectorAll('#devices-list .device-card').forEach(card => {
                card.style.border = '2px solid transparent';
            });
            event.currentTarget.style.border = '2px solid #3498db';
        }
        
        function updateDeviceInfo(deviceId) {
            if (!deviceId) {
                document.getElementById('unit_price').value = '0';
                document.getElementById('stock-info').textContent = '0';
                document.getElementById('quantity').value = '1';
                calculateTotal();
                return;
            }
            
            const select = document.getElementById('device_id');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const stock = selectedOption.getAttribute('data-stock');
            
            document.getElementById('unit_price').value = '$' + price;
            document.getElementById('stock-info').textContent = stock;
            document.getElementById('quantity').max = stock;
            calculateTotal();
        }
        
        function calculateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const priceText = document.getElementById('unit_price').value;
            const price = parseFloat(priceText.replace('$', '')) || 0;
            const total = quantity * price;
            
            document.getElementById('total_amount').value = '$' + total.toFixed(2);
        }
        
        function searchDevices() {
            const searchTerm = document.getElementById('search-devices').value.toLowerCase();
            const devices = document.querySelectorAll('#devices-list .device-card');
            
            devices.forEach(device => {
                const name = device.getAttribute('data-name');
                const desc = device.getAttribute('data-desc');
                
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    device.style.display = 'block';
                } else {
                    device.style.display = 'none';
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateDeviceInfo(document.getElementById('device_id').value);
        });
    </script>
</body>
</html>
<?php
require_once '../includes/auth.php';
userOnly();

require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $device_id = sanitize($_POST['device_id']);
    $quantity = sanitize($_POST['quantity']);
    $notes = sanitize($_POST['notes']);
    
    // Get device details
    $device = getDeviceById($device_id);
    
    if (!$device) {
        $error = "Device not found";
    } elseif ($device['stock'] < $quantity) {
        $error = "Not enough stock available. Available: " . $device['stock'];
    } elseif ($quantity <= 0) {
        $error = "Quantity must be at least 1";
    } else {
        // Calculate total
        $total = $device['price'] * $quantity;
        
        // Generate order number
        $order_number = generateOrderNumber();
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create order
            $sql1 = "INSERT INTO orders (user_id, order_number, total_amount, notes) 
                     VALUES ('{$_SESSION['user_id']}', '$order_number', '$total', '$notes')";
            mysqli_query($conn, $sql1);
            $order_id = mysqli_insert_id($conn);
            
            // Add order item
            $sql2 = "INSERT INTO order_items (order_id, device_id, quantity, unit_price, subtotal) 
                     VALUES ('$order_id', '$device_id', '$quantity', '{$device['price']}', '$total')";
            mysqli_query($conn, $sql2);
            
            // Update device stock
            $new_stock = $device['stock'] - $quantity;
            $sql3 = "UPDATE devices SET stock = '$new_stock' WHERE id = '$device_id'";
            mysqli_query($conn, $sql3);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Add system log
            addSystemLog($_SESSION['user_id'], 'PLACE_ORDER', 'Placed order #' . $order_number . ' for ' . $quantity . ' x ' . $device['name']);
            
            $message = "Order placed successfully! Your order number is: <strong>$order_number</strong>";
            
            // Clear form
            $_POST = array();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}

// Get all available devices
$devices = getAllDevices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - EOS</title>
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
                <li><a href="place_order.php" class="active"><i class="fas fa-cart-plus"></i> Place Order</a></li>
                <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
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
                <h1><i class="fas fa-cart-plus"></i> Place New Order</h1>
                <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Order Form -->
                <div>
                    <div class="table-container">
                        <h2><i class="fas fa-shopping-cart"></i> Order Form</h2>
                        
                        <form method="POST" onsubmit="return validateForm('order-form')" id="order-form">
                            <div class="form-group">
                                <label for="device_id"><i class="fas fa-laptop"></i> Select Device *</label>
                                <select id="device_id" name="device_id" required onchange="updateDeviceInfo(this.value)">
                                    <option value="">-- Select a device --</option>
                                    <?php foreach ($devices as $device): ?>
                                        <?php if ($device['is_available'] && $device['stock'] > 0): ?>
                                            <option value="<?php echo $device['id']; ?>" 
                                                    data-price="<?php echo $device['price']; ?>"
                                                    data-stock="<?php echo $device['stock']; ?>">
                                                <?php echo $device['name']; ?> - $<?php echo $device['price']; ?> (Stock: <?php echo $device['stock']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity"><i class="fas fa-boxes"></i> Quantity *</label>
                                <input type="number" id="quantity" name="quantity" min="1" value="1" required oninput="calculateTotal()">
                                <small>Available: <span id="stock-info">0</span></small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Unit Price</label>
                                <input type="text" id="unit_price" value="0" readonly style="background: #f5f5f5;">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calculator"></i> Total Amount</label>
                                <input type="text" id="total_amount" value="0" readonly style="background: #f5f5f5; font-size: 1.2rem; font-weight: bold;">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions..."></textarea>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Available Devices -->
                <div>
                    <div class="table-container">
                        <h2><i class="fas fa-list"></i> Available Devices</h2>
                        
                        <div style="margin-bottom: 1rem;">
                            <input type="text" id="search-devices" placeholder="Search devices..." style="width: 100%; padding: 0.5rem;" onkeyup="searchDevices()">
                        </div>
                        
                        <div id="devices-list" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($devices as $device): ?>
                                <?php if ($device['is_available'] && $device['stock'] > 0): ?>
                                    <div class="device-card" style="margin-bottom: 1rem; cursor: pointer;" 
                                         onclick="selectDevice(<?php echo $device['id']; ?>, <?php echo $device['price']; ?>, <?php echo $device['stock']; ?>)"
                                         data-name="<?php echo strtolower($device['name']); ?>"
                                         data-desc="<?php echo strtolower($device['description']); ?>">
                                        <div style="display: flex; gap: 1rem; align-items: center;">
                                            <div style="width: 60px; height: 60px; background: #3498db; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: white;">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0;"><?php echo $device['name']; ?></h4>
                                                <p style="margin: 0.2rem 0; color: #666; font-size: 0.9rem;"><?php echo substr($device['description'], 0, 80); ?>...</p>
                                                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                                                    <span style="font-weight: bold; color: #2ecc71;">$<?php echo $device['price']; ?></span>
                                                    <span style="color: #7f8c8d;">Stock: <?php echo $device['stock']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php
                            $available_devices = array_filter($devices, function($device) {
                                return $device['is_available'] && $device['stock'] > 0;
                            });
                            
                            if (empty($available_devices)): ?>
                                <p style="text-align: center; padding: 2rem; color: #7f8c8d;">
                                    <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                                    No devices available at the moment.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
    <script>
        // Device selection functionality
        function selectDevice(deviceId, price, stock) {
            document.getElementById('device_id').value = deviceId;
            document.getElementById('unit_price').value = '$' + price;
            document.getElementById('stock-info').textContent = stock;
            document.getElementById('quantity').max = stock;
            calculateTotal();
            
            // Highlight selected device
            document.querySelectorAll('#devices-list .device-card').forEach(card => {
                card.style.border = '2px solid transparent';
            });
            event.currentTarget.style.border = '2px solid #3498db';
        }
        
        function updateDeviceInfo(deviceId) {
            if (!deviceId) {
                document.getElementById('unit_price').value = '0';
                document.getElementById('stock-info').textContent = '0';
                document.getElementById('quantity').value = '1';
                calculateTotal();
                return;
            }
            
            const select = document.getElementById('device_id');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const stock = selectedOption.getAttribute('data-stock');
            
            document.getElementById('unit_price').value = '$' + price;
            document.getElementById('stock-info').textContent = stock;
            document.getElementById('quantity').max = stock;
            calculateTotal();
        }
        
        function calculateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const priceText = document.getElementById('unit_price').value;
            const price = parseFloat(priceText.replace('$', '')) || 0;
            const total = quantity * price;
            
            document.getElementById('total_amount').value = '$' + total.toFixed(2);
        }
        
        function searchDevices() {
            const searchTerm = document.getElementById('search-devices').value.toLowerCase();
            const devices = document.querySelectorAll('#devices-list .device-card');
            
            devices.forEach(device => {
                const name = device.getAttribute('data-name');
                const desc = device.getAttribute('data-desc');
                
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    device.style.display = 'block';
                } else {
                    device.style.display = 'none';
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateDeviceInfo(document.getElementById('device_id').value);
        });
    </script>
</body>
</html>
<?php
require_once '../includes/auth.php';
userOnly();

require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $device_id = sanitize($_POST['device_id']);
    $quantity = sanitize($_POST['quantity']);
    $notes = sanitize($_POST['notes']);
    
    // Get device details
    $device = getDeviceById($device_id);
    
    if (!$device) {
        $error = "Device not found";
    } elseif ($device['stock'] < $quantity) {
        $error = "Not enough stock available. Available: " . $device['stock'];
    } elseif ($quantity <= 0) {
        $error = "Quantity must be at least 1";
    } else {
        // Calculate total
        $total = $device['price'] * $quantity;
        
        // Generate order number
        $order_number = generateOrderNumber();
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Create order
            $sql1 = "INSERT INTO orders (user_id, order_number, total_amount, notes) 
                     VALUES ('{$_SESSION['user_id']}', '$order_number', '$total', '$notes')";
            mysqli_query($conn, $sql1);
            $order_id = mysqli_insert_id($conn);
            
            // Add order item
            $sql2 = "INSERT INTO order_items (order_id, device_id, quantity, unit_price, subtotal) 
                     VALUES ('$order_id', '$device_id', '$quantity', '{$device['price']}', '$total')";
            mysqli_query($conn, $sql2);
            
            // Update device stock
            $new_stock = $device['stock'] - $quantity;
            $sql3 = "UPDATE devices SET stock = '$new_stock' WHERE id = '$device_id'";
            mysqli_query($conn, $sql3);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Add system log
            addSystemLog($_SESSION['user_id'], 'PLACE_ORDER', 'Placed order #' . $order_number . ' for ' . $quantity . ' x ' . $device['name']);
            
            $message = "Order placed successfully! Your order number is: <strong>$order_number</strong>";
            
            // Clear form
            $_POST = array();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}

// Get all available devices
$devices = getAllDevices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - EOS</title>
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
                <li><a href="place_order.php" class="active"><i class="fas fa-cart-plus"></i> Place Order</a></li>
                <li><a href="my_orders.php"><i class="fas fa-shopping-bag"></i> My Orders</a></li>
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
                <h1><i class="fas fa-cart-plus"></i> Place New Order</h1>
                <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Order Form -->
                <div>
                    <div class="table-container">
                        <h2><i class="fas fa-shopping-cart"></i> Order Form</h2>
                        
                        <form method="POST" onsubmit="return validateForm('order-form')" id="order-form">
                            <div class="form-group">
                                <label for="device_id"><i class="fas fa-laptop"></i> Select Device *</label>
                                <select id="device_id" name="device_id" required onchange="updateDeviceInfo(this.value)">
                                    <option value="">-- Select a device --</option>
                                    <?php foreach ($devices as $device): ?>
                                        <?php if ($device['is_available'] && $device['stock'] > 0): ?>
                                            <option value="<?php echo $device['id']; ?>" 
                                                    data-price="<?php echo $device['price']; ?>"
                                                    data-stock="<?php echo $device['stock']; ?>">
                                                <?php echo $device['name']; ?> - $<?php echo $device['price']; ?> (Stock: <?php echo $device['stock']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity"><i class="fas fa-boxes"></i> Quantity *</label>
                                <input type="number" id="quantity" name="quantity" min="1" value="1" required oninput="calculateTotal()">
                                <small>Available: <span id="stock-info">0</span></small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign"></i> Unit Price</label>
                                <input type="text" id="unit_price" value="0" readonly style="background: #f5f5f5;">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calculator"></i> Total Amount</label>
                                <input type="text" id="total_amount" value="0" readonly style="background: #f5f5f5; font-size: 1.2rem; font-weight: bold;">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions..."></textarea>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Place Order
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Available Devices -->
                <div>
                    <div class="table-container">
                        <h2><i class="fas fa-list"></i> Available Devices</h2>
                        
                        <div style="margin-bottom: 1rem;">
                            <input type="text" id="search-devices" placeholder="Search devices..." style="width: 100%; padding: 0.5rem;" onkeyup="searchDevices()">
                        </div>
                        
                        <div id="devices-list" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($devices as $device): ?>
                                <?php if ($device['is_available'] && $device['stock'] > 0): ?>
                                    <div class="device-card" style="margin-bottom: 1rem; cursor: pointer;" 
                                         onclick="selectDevice(<?php echo $device['id']; ?>, <?php echo $device['price']; ?>, <?php echo $device['stock']; ?>)"
                                         data-name="<?php echo strtolower($device['name']); ?>"
                                         data-desc="<?php echo strtolower($device['description']); ?>">
                                        <div style="display: flex; gap: 1rem; align-items: center;">
                                            <div style="width: 60px; height: 60px; background: #3498db; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: white;">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                            <div style="flex: 1;">
                                                <h4 style="margin: 0;"><?php echo $device['name']; ?></h4>
                                                <p style="margin: 0.2rem 0; color: #666; font-size: 0.9rem;"><?php echo substr($device['description'], 0, 80); ?>...</p>
                                                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                                                    <span style="font-weight: bold; color: #2ecc71;">$<?php echo $device['price']; ?></span>
                                                    <span style="color: #7f8c8d;">Stock: <?php echo $device['stock']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <?php
                            $available_devices = array_filter($devices, function($device) {
                                return $device['is_available'] && $device['stock'] > 0;
                            });
                            
                            if (empty($available_devices)): ?>
                                <p style="text-align: center; padding: 2rem; color: #7f8c8d;">
                                    <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 1rem;"></i><br>
                                    No devices available at the moment.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
    <script>
        // Device selection functionality
        function selectDevice(deviceId, price, stock) {
            document.getElementById('device_id').value = deviceId;
            document.getElementById('unit_price').value = '$' + price;
            document.getElementById('stock-info').textContent = stock;
            document.getElementById('quantity').max = stock;
            calculateTotal();
            
            // Highlight selected device
            document.querySelectorAll('#devices-list .device-card').forEach(card => {
                card.style.border = '2px solid transparent';
            });
            event.currentTarget.style.border = '2px solid #3498db';
        }
        
        function updateDeviceInfo(deviceId) {
            if (!deviceId) {
                document.getElementById('unit_price').value = '0';
                document.getElementById('stock-info').textContent = '0';
                document.getElementById('quantity').value = '1';
                calculateTotal();
                return;
            }
            
            const select = document.getElementById('device_id');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            const stock = selectedOption.getAttribute('data-stock');
            
            document.getElementById('unit_price').value = '$' + price;
            document.getElementById('stock-info').textContent = stock;
            document.getElementById('quantity').max = stock;
            calculateTotal();
        }
        
        function calculateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const priceText = document.getElementById('unit_price').value;
            const price = parseFloat(priceText.replace('$', '')) || 0;
            const total = quantity * price;
            
            document.getElementById('total_amount').value = '$' + total.toFixed(2);
        }
        
        function searchDevices() {
            const searchTerm = document.getElementById('search-devices').value.toLowerCase();
            const devices = document.querySelectorAll('#devices-list .device-card');
            
            devices.forEach(device => {
                const name = device.getAttribute('data-name');
                const desc = device.getAttribute('data-desc');
                
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    device.style.display = 'block';
                } else {
                    device.style.display = 'none';
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateDeviceInfo(document.getElementById('device_id').value);
        });
    </script>
</body>
</html>
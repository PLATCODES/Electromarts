<?php
require_once '../includes/auth.php';
adminOnly();

require_once '../includes/functions.php';

$message = '';
$error = '';

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = sanitize($_GET['delete']);
    
    // Don't allow deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account";
    } else {
        $sql = "DELETE FROM users WHERE id = '$user_id'";
        if (mysqli_query($conn, $sql)) {
            addSystemLog($_SESSION['user_id'], 'DELETE_USER', 'Deleted user ID: ' . $user_id);
            $message = "User deleted successfully";
        } else {
            $error = "Error deleting user: " . mysqli_error($conn);
        }
    }
}

// Handle edit user (update role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = sanitize($_POST['user_id']);
    $role = sanitize($_POST['role']);
    
    // Don't allow changing your own role to user
    if ($user_id == $_SESSION['user_id'] && $role == 'user') {
        $error = "You cannot change your own role to user";
    } else {
        $sql = "UPDATE users SET role = '$role', updated_at = NOW() WHERE id = '$user_id'";
        if (mysqli_query($conn, $sql)) {
            addSystemLog($_SESSION['user_id'], 'UPDATE_USER_ROLE', 'Updated role to ' . $role . ' for user ID: ' . $user_id);
            $message = "User role updated successfully";
        } else {
            $error = "Error updating user: " . mysqli_error($conn);
        }
    }
}

// Get all users
$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - EOS</title>
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
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
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
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <?php echo $user['username']; ?>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="status-badge" style="background: #3498db; padding: 0.2rem 0.5rem; font-size: 0.8rem;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo $user['full_name'] ?: 'Not set'; ?></td>
                            <td><?php echo $user['phone'] ?: 'Not set'; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="role" onchange="this.form.submit()" 
                                            <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <input type="hidden" name="update_role" value="1">
                                </form>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="?view=<?php echo $user['id']; ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.9rem;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" 
                                       class="btn btn-danger" 
                                       style="padding: 0.3rem 0.8rem; font-size: 0.9rem;"
                                       onclick="return confirmDelete('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../script.js"></script>
</body>
</html>

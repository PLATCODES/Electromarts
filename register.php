<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if user exists
        $check_sql = "SELECT id FROM users WHERE email = '$email' OR username = '$username'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Username or email already exists';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (username, email, password, full_name, phone, address) 
                    VALUES ('$username', '$email', '$hashed_password', '$full_name', '$phone', '$address')";
            
            if (mysqli_query($conn, $sql)) {
                $user_id = mysqli_insert_id($conn);
                addSystemLog($user_id, 'REGISTER', 'New user registered: ' . $username);
                $success = 'Registration successful! You can now login.';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Registration failed: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Electronic Ordering System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><i class="fas fa-laptop"></i> EOS</h1>
            <div class="nav-links">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="auth-container">
            <h2 style="text-align: center; margin-bottom: 1.5rem;">Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateForm('register-form')">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username *</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name"><i class="fas fa-id-card"></i> Full Name</label>
                    <input type="text" id="full_name" name="full_name">
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" required>
                        <button type="button" onclick="togglePasswordVisibility('password')" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password *</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" onclick="togglePasswordVisibility('confirm_password')" 
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>

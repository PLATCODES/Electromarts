<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electronic Ordering System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><i class="fas fa-laptop"></i> Electronic Ordering System</h1>
            <div class="nav-links">
                <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                        <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
                    <?php else: ?>
                        <a href="user/dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container">
            <h2>Welcome to Electronic Ordering System</h2>
            <p>Order the latest electronic devices with ease. Browse, select, and order your favorite gadgets in just a few clicks!</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="cta-button">Get Started Now <i class="fas fa-arrow-right"></i></a>
            <?php else: ?>
                <a href="<?php echo $_SESSION['user_role'] == 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'; ?>" 
                   class="cta-button">Go to Dashboard <i class="fas fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>
    </header>

    <section class="features">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Why Choose Our System?</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Fast Ordering</h3>
                    <p>Place orders quickly with our streamlined process</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure</h3>
                    <p>Your data and transactions are safe with us</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Friendly</h3>
                    <p>Access from any device, anywhere</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Our team is always ready to help you</p>
                </div>
            </div>
        </div>
    </section>

    <section class="container" style="padding: 4rem 0;">
        <h2 style="text-align: center; margin-bottom: 2rem;">Available Categories</h2>
        <?php
        require_once 'includes/functions.php';
        $categories = getAllCategories();
        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <?php foreach ($categories as $category): ?>
                <div style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h3><i class="fas fa-microchip"></i> <?php echo htmlspecialchars($category['name']); ?></h3>
                    <p style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($category['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Electronic Ordering System. All rights reserved.</p>
            <p>Simple, Fast, and Reliable Electronic Device Ordering</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>

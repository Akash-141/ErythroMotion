<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = $root_path_prefix ?? ''; // Ensure $root_path_prefix is defined

$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['quantity'])) {
                $cart_item_count += $item['quantity'];
            } else {
                // Fallback for older cart structure
                $cart_item_count = count($_SESSION['cart']);
                break;
            }
        }
    }
}
?>
<nav class="site-navbar">
    <div class="navbar-logo-container">
        <a href="<?php echo $root_path_prefix; ?>index.php" class="navbar-logo-link">
            <img src="<?php echo $root_path_prefix; ?>images/logo.png" alt="ErythroMotion Logo" class="navbar-logo-img">
        </a>
    </div>

    <ul class="navbar-links">
        <li><a href="<?php echo $root_path_prefix; ?>index.php">Home</a></li>
        <li class="nav-item-dropdown">
            <a href="#">Fitness</a>
            <ul class="dropdown-menu-content">
                <li><a href="<?php echo $root_path_prefix; ?>fitness_guide.php">Fitness Guide</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>exercise.php">Exercise List</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>mental_health.php">Mental Health & Well-being</a></li>
            </ul>
        </li>
        <li class="nav-item-dropdown">
            <a href="#">Health Tools</a>
            <ul class="dropdown-menu-content">
                <li><a href="<?php echo $root_path_prefix; ?>bmi.php">BMI Calculator</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>weather_health.php">Weather & Health</a></li>
            </ul>
        </li>
        <li class="nav-item-dropdown">
            <a href="#">Blood Management</a>
            <ul class="dropdown-menu-content">
                <li><a href="<?php echo $root_path_prefix; ?>blood_donation.php">Blood Donation</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>donor_list.php">Donor List</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>search_donor.php">Search For Donor</a></li>
            </ul>
        </li>
        <li class="nav-item-dropdown">
            <a href="#">Health Resources</a>
            <ul class="dropdown-menu-content">
                <li><a href="<?php echo $root_path_prefix; ?>diet_nutrition.php">Diet & Nutrition</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>blog.php">Blog</a></li> <!-- UPDATED LINK -->
            </ul>
        </li>
        <li><a href="<?php echo $root_path_prefix; ?>motionmart.php">Motion Mart</a></li>
        <li><a href="<?php echo $root_path_prefix; ?>contact.php">Contact Us</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item-dropdown">
                <a href="<?php echo $root_path_prefix; ?>profile.php">Profile</a>
                <ul class="dropdown-menu-content">
                    <li><a href="<?php echo $root_path_prefix; ?>profile.php#order-history-section">My Orders</a></li>
                    <li><a href="<?php echo $root_path_prefix; ?>profile.php#workout-plan-section">My Workout Plan</a></li>
                    <li><a href="<?php echo $root_path_prefix; ?>wishlist.php">My Wishlist</a></li>
                    <li><a href="<?php echo $root_path_prefix; ?>request_received.php">Requests Received</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="<?php echo $root_path_prefix; ?>admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>
    </ul>

    <div class="navbar-right-group">
        <div class="cart-section">
            <a href="<?php echo $root_path_prefix; ?>cart.php" class="cart-link">
                <svg class="cart-icon-svg" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-text">Cart</span>
                <?php if ($cart_item_count > 0): ?>
                    <span class="cart-count-badge"><?php echo $cart_item_count; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="navbar-auth">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="<?php echo $root_path_prefix; ?>logout.php" class="auth-button logout-button">Logout</a>
            <?php else: ?>
                <a href="<?php echo $root_path_prefix; ?>login.php" class="auth-button login-button">Login</a>
                <a href="<?php echo $root_path_prefix; ?>signup.php" class="auth-button signup-button">Signup</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- NEW: Hamburger Menu Toggler -->
    <button class="navbar-toggler" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<!-- NEW: Script connection -->
<script src="<?php echo $root_path_prefix; ?>js/navbar.js"></script>

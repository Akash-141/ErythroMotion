<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Order Successful - ErythroMotion";

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

// Ensure $root_path_prefix is available for the navbar if it's set by other including scripts
// For a standalone page like this, it's usually an empty string if in root.
$root_path_prefix = $root_path_prefix ?? ''; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/variables.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/navbar.css"> <!-- [cite: navbar.css] -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/footer.css"> <!-- [cite: footer.css] -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/order_success.css"> <!-- [cite: order_success.css] -->
     <!-- Link to cart.css if button styles are primarily defined there and not globally -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/cart.css"> <!-- [cite: cart.css] For .button-primary and .button-secondary -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; }
        /* Ensure button styles from cart.css are effective or replicate them in order_success.css if needed */
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; // [cite: navbar_php_wishlist_link] ?>

    <main class="order-success-main">
        <div class="order-success-container">
            <div class="success-icon">✓</div>
            <h1>Thank You For Your Order!</h1>
            <?php if ($order_id): ?>
                <p>Your order #<?php echo htmlspecialchars($order_id); ?> has been placed successfully.</p>
            <?php else: ?>
                <p>Your order has been placed successfully.</p>
            <?php endif; ?>
            <p>We will process it shortly. You will receive an email confirmation soon (feature to be implemented).</p>
            <div class="order-success-actions">
                <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="button-secondary">Continue Shopping</a>
                <a href="<?php echo $root_path_prefix; ?>profile.php#order-history-section" class="button-primary">View My Orders</a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>

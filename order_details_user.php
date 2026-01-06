<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

include __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]
$page_title = "Order Details - ErythroMotion";
$user_id = $_SESSION['user_id'];
$errors = [];
$order = null;
$order_items = [];

$order_id_from_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id_from_get) {
    $errors[] = "No order ID specified or invalid ID format.";
} else {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Fetch the main order details, ensuring it belongs to the current user
        $stmt_order = $pdo->prepare("
            SELECT * FROM orders 
            WHERE order_id = :order_id AND user_id = :user_id
        "); // [cite: DDL.sql]
        $stmt_order->bindParam(':order_id', $order_id_from_get, PDO::PARAM_INT);
        $stmt_order->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_order->execute();
        $order = $stmt_order->fetch();

        if (!$order) {
            $errors[] = "Order not found or you do not have permission to view this order.";
        } else {
            $page_title = "Details for Order #" . htmlspecialchars($order['order_id']) . " - ErythroMotion";
            // Fetch the items for this order
            $stmt_items = $pdo->prepare("
                SELECT oi.*, eq.name AS equipment_name, eq.image_url 
                FROM order_items oi 
                LEFT JOIN equipments eq ON oi.equipment_id = eq.equipment_id 
                WHERE oi.order_id = :order_id
            "); // [cite: DDL.sql]
            $stmt_items->bindParam(':order_id', $order['order_id'], PDO::PARAM_INT);
            $stmt_items->execute();
            $order_items = $stmt_items->fetchAll();
        }

    } catch (PDOException $e) {
        $errors[] = "Database error: Could not retrieve order details. " . $e->getMessage();
        // In a production environment, log $e->getMessage() and show a generic error to the user.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/variables.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/navbar.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/order_details_user.css"> <!-- We will create this CSS file next -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
        }
        main { 
            flex-grow: 1; 
            padding: var(--spacing-lg) var(--spacing-md); /* [cite: variables.css] */
            background-color: var(--white); /* [cite: variables.css] */
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; // [cite: navbar.php] ?>

    <main>
        <div class="order-details-user-container">
            <h1 class="page-main-title"><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                    <p><a href="<?php echo $root_path_prefix; ?>profile.php#order-history-section">Back to My Profile</a></p>
                </div>
            <?php elseif ($order): ?>
                <div class="order-summary-box">
                    <h2>Order Summary</h2>
                    <div class="order-info-grid">
                        <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></p>
                        <p><strong>Total Amount:</strong> $<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></p>
                        <p><strong>Order Status:</strong> <span class="order-status status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $order['order_status']))); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></p>
                    </div>
                </div>

                <div class="shipping-details-box">
                    <h2>Shipping Address</h2>
                    <p><strong>Recipient:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                    <p><?php echo htmlspecialchars($order['shipping_address_line1']); ?></p>
                    <?php if (!empty($order['shipping_address_line2'])): ?>
                        <p><?php echo htmlspecialchars($order['shipping_address_line2']); ?></p>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                </div>

                <div class="order-items-box">
                    <h2>Items in this Order</h2>
                    <?php if (!empty($order_items)): ?>
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price (at purchase)</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td data-label="Product">
                                            <div class="product-info-cell">
                                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'images/equipments/placeholder_thumb.jpg'; ?>" alt="<?php echo htmlspecialchars($item['equipment_name'] ?? 'Item'); ?>" class="item-thumbnail-small">
                                                <span><?php echo htmlspecialchars($item['equipment_name'] ?? 'Equipment N/A'); ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Quantity"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td data-label="Price">$<?php echo htmlspecialchars(number_format($item['price_at_purchase'], 2)); ?></td>
                                        <td data-label="Subtotal">$<?php echo htmlspecialchars(number_format($item['price_at_purchase'] * $item['quantity'], 2)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No items found for this order. This might indicate an issue.</p>
                    <?php endif; ?>
                </div>
                
                <div class="profile-actions">
                    <a href="<?php echo $root_path_prefix; ?>profile.php#order-history-section" class="button-secondary">Back to My Orders</a>
                </div>

            <?php else: ?>
                 <p>Could not display order details. Please try again or contact support if the issue persists.</p>
                 <p><a href="<?php echo $root_path_prefix; ?>profile.php#order-history-section">Back to My Profile</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>

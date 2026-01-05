<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../"; 

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php';
$page_title = "Order Details - Admin";
$errors = [];
$feedback_message = "";
$feedback_type = "";

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header("Location: manage_orders.php?status=error&msg=" . urlencode("Invalid Order ID specified."));
    exit();
}

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status']) && empty($errors)) {
    $new_status = trim($_POST['order_status'] ?? '');
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if (in_array($new_status, $allowed_statuses)) {
        try {
            $sql_update = "UPDATE orders SET order_status = :order_status, updated_at = CURRENT_TIMESTAMP WHERE order_id = :order_id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':order_status', $new_status);
            $stmt_update->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_update->execute();
            $feedback_message = "Order status updated to " . htmlspecialchars($new_status) . " successfully.";
            $feedback_type = "success";
        } catch (PDOException $e) {
            $feedback_message = "Error updating order status: " . $e->getMessage();
            $feedback_type = "error";
        }
    } else {
        $feedback_message = "Invalid order status selected.";
        $feedback_type = "error";
    }
    // Redirect to self with GET parameters to show message and clear POST
    header("Location: order_details.php?id=" . $order_id . "&status=" . urlencode($feedback_type) . "&msg=" . urlencode($feedback_message));
    exit();
}

if(isset($_GET['status']) && isset($_GET['msg'])) {
    $feedback_message = htmlspecialchars(urldecode($_GET['msg']));
    $feedback_type = htmlspecialchars($_GET['status']);
}


$order = null;
$order_items = [];

if (empty($errors)) {
    try {
        $stmt_order = $pdo->prepare("
            SELECT o.*, u.username AS customer_username, u.email AS customer_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.user_id 
            WHERE o.order_id = :order_id
        ");
        $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt_order->execute();
        $order = $stmt_order->fetch();

        if (!$order) {
            $errors[] = "Order not found.";
        } else {
            $page_title = "Order Details #" . htmlspecialchars($order['order_id']) . " - Admin";
            $stmt_items = $pdo->prepare("
                SELECT oi.*, eq.name AS equipment_name, eq.image_url 
                FROM order_items oi 
                LEFT JOIN equipments eq ON oi.equipment_id = eq.equipment_id 
                WHERE oi.order_id = :order_id
            ");
            $stmt_items->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_items->execute();
            $order_items = $stmt_items->fetchAll();
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching order details: " . $e->getMessage();
    }
}

$order_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_layout.css"> 
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_order_details.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
            padding-top: var(--navbar-height);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="manage_orders.php" class="admin-button plain-button">Back to Orders List</a>
                </div>

                <?php if (!empty($feedback_message)): ?>
                    <div class="message global-message <?php echo $feedback_type === 'success' ? 'success-message' : 'error-message'; ?>">
                        <p><?php echo $feedback_message; ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors) && !$order): // Show general errors if order itself not found ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($order): ?>
                    <div class="order-details-grid">
                        <section class="order-info-section styled-admin-box">
                            <h2>Order Information</h2>
                            <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
                            <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['order_date']))); ?></p>
                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?> (<?php echo htmlspecialchars($order['customer_username']); ?>)</p>
                            <p><strong>Customer Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p><strong>Total Amount:</strong> $<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></p>
                            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></p>
                            <p><strong>Current Status:</strong> <span class="order-status status-<?php echo htmlspecialchars(strtolower($order['order_status'])); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></p>
                        </section>

                        <section class="shipping-info-section styled-admin-box">
                            <h2>Shipping Address</h2>
                            <p><strong>Recipient:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping_address_line1']); ?></p>
                            <?php if (!empty($order['shipping_address_line2'])): ?>
                                <p><?php echo htmlspecialchars($order['shipping_address_line2']); ?></p>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                        </section>
                    </div>

                    <section class="order-items-section styled-admin-box">
                        <h2>Ordered Items</h2>
                        <?php if (!empty($order_items)): ?>
                            <table class="admin-table order-items-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Price at Purchase</th>
                                        <th>Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'images/equipments/placeholder_thumb.jpg'; ?>" alt="<?php echo htmlspecialchars($item['equipment_name'] ?? 'Item'); ?>" class="item-thumbnail">
                                            </td>
                                            <td><?php echo htmlspecialchars($item['equipment_name'] ?? 'Equipment N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td>$<?php echo htmlspecialchars(number_format($item['price_at_purchase'], 2)); ?></td>
                                            <td>$<?php echo htmlspecialchars(number_format($item['price_at_purchase'] * $item['quantity'], 2)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No items found for this order.</p>
                        <?php endif; ?>
                    </section>

                    <section class="update-status-section styled-admin-box">
                        <h2>Update Order Status</h2>
                        <form action="order_details.php?id=<?php echo $order_id; ?>" method="POST" class="admin-form">
                            <div class="form-group">
                                <label for="order_status">New Status:</label>
                                <select name="order_status" id="order_status" required>
                                    <?php foreach ($order_statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($order['order_status'] == $status) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="update_order_status" class="admin-button add-new-button">Update Status</button>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
include __DIR__ . '/includes/db_connect.php';
$page_title = "Checkout - ErythroMotion";
$errors = [];
$form_data = $_POST; 

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($root_path_prefix . "checkout.php"));
    exit();
}

$cart_items = $_SESSION['cart'] ?? [];
if (empty($cart_items)) {
    header("Location: " . $root_path_prefix . "cart.php?message=" . urlencode("Your cart is empty."));
    exit();
}

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($errors)) {
    $shipping_name = trim($form_data['shipping_name'] ?? '');
    $shipping_address_line1 = trim($form_data['shipping_address_line1'] ?? '');
    $shipping_address_line2 = trim($form_data['shipping_address_line2'] ?? '');
    $shipping_city = trim($form_data['shipping_city'] ?? '');
    $shipping_postal_code = trim($form_data['shipping_postal_code'] ?? '');
    $shipping_phone = trim($form_data['shipping_phone'] ?? '');
    $payment_method = $form_data['payment_method'] ?? '';

    if (empty($shipping_name)) $errors[] = "Full name for shipping is required.";
    if (empty($shipping_address_line1)) $errors[] = "Address Line 1 for shipping is required.";
    if (empty($shipping_city)) $errors[] = "City for shipping is required.";
    if (empty($shipping_postal_code)) $errors[] = "Postal code for shipping is required.";
    if (empty($shipping_phone)) {
        $errors[] = "Phone number for shipping is required.";
    } elseif (!preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $shipping_phone)) {
        $errors[] = "Invalid shipping phone number format.";
    }
    if (empty($payment_method) || $payment_method !== 'cod') {
        $errors[] = "Please select a payment method.";
    }


    if (empty($errors)) {
        $cart_subtotal = 0;
        foreach ($cart_items as $id => $item) {
            $cart_subtotal += $item['price'] * $item['quantity'];
        }
        $total_amount = $cart_subtotal; 

        try {
            $pdo->beginTransaction();

            $sql_order = "INSERT INTO orders (user_id, total_amount, shipping_name, shipping_address_line1, shipping_address_line2, shipping_city, shipping_postal_code, shipping_phone, order_status, payment_method) 
                          VALUES (:user_id, :total_amount, :shipping_name, :shipping_address_line1, :shipping_address_line2, :shipping_city, :shipping_postal_code, :shipping_phone, :order_status, :payment_method)";
            $stmt_order = $pdo->prepare($sql_order);
            $order_status = 'Pending'; 
            $stmt_order->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt_order->bindParam(':total_amount', $total_amount);
            $stmt_order->bindParam(':shipping_name', $shipping_name);
            $stmt_order->bindParam(':shipping_address_line1', $shipping_address_line1);
            $stmt_order->bindParam(':shipping_address_line2', $shipping_address_line2);
            $stmt_order->bindParam(':shipping_city', $shipping_city);
            $stmt_order->bindParam(':shipping_postal_code', $shipping_postal_code);
            $stmt_order->bindParam(':shipping_phone', $shipping_phone);
            $stmt_order->bindParam(':order_status', $order_status);
            $stmt_order->bindParam(':payment_method', $payment_method);
            $stmt_order->execute();
            $order_id = $pdo->lastInsertId();

            $sql_order_item = "INSERT INTO order_items (order_id, equipment_id, quantity, price_at_purchase) 
                               VALUES (:order_id, :equipment_id, :quantity, :price_at_purchase)";
            $stmt_order_item = $pdo->prepare($sql_order_item);

            $sql_update_stock = "UPDATE equipments SET stock_quantity = stock_quantity - :quantity WHERE equipment_id = :equipment_id AND stock_quantity >= :quantity_to_check";
            $stmt_update_stock = $pdo->prepare($sql_update_stock);

            foreach ($cart_items as $equipment_id_cart => $item) {
                $stmt_order_item->bindParam(':order_id', $order_id, PDO::PARAM_INT);
                $stmt_order_item->bindParam(':equipment_id', $equipment_id_cart, PDO::PARAM_INT);
                $stmt_order_item->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                $stmt_order_item->bindParam(':price_at_purchase', $item['price']);
                $stmt_order_item->execute();

                $stmt_update_stock->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':equipment_id', $equipment_id_cart, PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':quantity_to_check', $item['quantity'], PDO::PARAM_INT); 
                $update_success = $stmt_update_stock->execute();
                if ($stmt_update_stock->rowCount() == 0) {
                     throw new Exception("Stock issue for equipment ID: " . $equipment_id_cart . ". Order rolled back.");
                }
            }

            $pdo->commit();
            unset($_SESSION['cart']); 
            header("Location: " . $root_path_prefix . "order_success.php?order_id=" . $order_id);
            exit();

        } catch (Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Order placement failed: " . $e->getMessage();
        }
    }
}

$cart_subtotal = 0;
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $cart_subtotal += $item['price'] * $item['quantity'];
        }
    }
}
$grand_total = $cart_subtotal; 

// Pre-fill form data from session if available for logged-in user, only on initial GET request
if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_SESSION['user_id'])) {
    try {
        $stmt_user = $pdo->prepare("SELECT full_name, phone_number, location FROM users WHERE user_id = :user_id");
        $stmt_user->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt_user->execute();
        $user_details = $stmt_user->fetch();
        if ($user_details) {
            $form_data['shipping_name'] = $form_data['shipping_name'] ?? $user_details['full_name'];
            $form_data['shipping_phone'] = $form_data['shipping_phone'] ?? $user_details['phone_number'];
            // Assuming 'location' can be used for address line 1 or city as a starting point
            if (empty($form_data['shipping_address_line1']) && !empty($user_details['location'])) {
                 // This is a simple prefill; a full address system would be more complex
                 // For now, let's not prefill address parts to avoid confusion if 'location' is just city/country
            }
        }
    } catch (PDOException $e) {
        // Error fetching user details for prefill, ignore for now
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="checkout-main-content">
        <div class="checkout-container">
            <h1 class="checkout-title">Checkout</h1>

            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-layout">
                <section class="shipping-details-section">
                    <h2>Shipping Information</h2>
                    <form action="<?php echo $root_path_prefix; ?>checkout.php" method="POST" class="checkout-form">
                        <div class="form-group">
                            <label for="shipping_name">Full Name:</label>
                            <input type="text" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($form_data['shipping_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_address_line1">Address Line 1:</label>
                            <input type="text" id="shipping_address_line1" name="shipping_address_line1" value="<?php echo htmlspecialchars($form_data['shipping_address_line1'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_address_line2">Address Line 2:</label>
                            <input type="text" id="shipping_address_line2" name="shipping_address_line2" value="<?php echo htmlspecialchars($form_data['shipping_address_line2'] ?? ''); ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City:</label>
                                <input type="text" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($form_data['shipping_city'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="shipping_postal_code">Postal Code:</label>
                                <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($form_data['shipping_postal_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="shipping_phone">Phone Number:</label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($form_data['shipping_phone'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group payment-method-group">
                            <h3>Payment Method</h3>
                            <div class="radio-option">
                                <input type="radio" id="payment_cod" name="payment_method" value="cod" checked required>
                                <label for="payment_cod">Cash On Delivery (COD)</label>
                            </div>
                        </div>

                        <button type="submit" class="button-primary place-order-button">Place Order</button>
                    </form>
                </section>

                <aside class="order-summary-section">
                    <h2>Order Summary</h2>
                    <?php if (!empty($cart_items)): ?>
                        <ul class="order-summary-list">
                            <?php foreach($cart_items as $item_id => $item): ?>
                                <li class="summary-item">
                                    <img src="<?php echo $root_path_prefix; ?><?php echo htmlspecialchars($item['image_url'] ?? 'images/equipments/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="summary-item-image">
                                    <div class="summary-item-details">
                                        <span class="summary-item-name"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>)</span>
                                        <span class="summary-item-price">$<?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="summary-totals">
                            <p>Subtotal: <span>$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></span></p>
                            <p class="summary-grand-total">Grand Total: <span>$<?php echo htmlspecialchars(number_format($grand_total, 2)); ?></span></p>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
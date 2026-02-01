<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "";
include __DIR__ . '/includes/db_connect.php';
$page_title = "Your Shopping Cart - ErythroMotion";

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $page_errors[] = "Database connection issue: " . $e->getMessage();
}

$feedback_message = "";
$feedback_type = "";
$page_errors = []; // Initialize page_errors

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;

    $redirect_url = $root_path_prefix . "cart.php";

    if ($action === 'add' && $equipment_id && $pdo) {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if ($quantity <= 0) $quantity = 1;

        try {
            $stmt = $pdo->prepare("SELECT name, price, image_url, stock_quantity FROM equipments WHERE equipment_id = :equipment_id");
            $stmt->bindParam(':equipment_id', $equipment_id);
            $stmt->execute();
            $equipment = $stmt->fetch();

            if ($equipment) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                $current_quantity_in_cart = $_SESSION['cart'][$equipment_id]['quantity'] ?? 0;
                $requested_total_quantity = $current_quantity_in_cart + $quantity;

                if ($requested_total_quantity <= $equipment['stock_quantity']) {
                    if (isset($_SESSION['cart'][$equipment_id])) {
                        $_SESSION['cart'][$equipment_id]['quantity'] += $quantity;
                    } else {
                        $_SESSION['cart'][$equipment_id] = [
                            'name' => $equipment['name'],
                            'price' => $equipment['price'],
                            'quantity' => $quantity,
                            'image_url' => $equipment['image_url'],
                            'stock' => $equipment['stock_quantity']
                        ];
                    }
                } else {
                    // This feedback is set in motionmart.php, so not strictly needed here
                    // but good if cart.php could also add items in future.
                }
            }
        } catch (PDOException $e) {
            // Error already handled by feedback mechanism in motionmart.php
        }
    } elseif ($action === 'remove' && $equipment_id) {
        if (isset($_SESSION['cart'][$equipment_id])) {
            unset($_SESSION['cart'][$equipment_id]);
            $feedback_message = "Item removed from your cart.";
            $feedback_type = "success";
        } else {
            $feedback_message = "Item not found in your cart to remove.";
            $feedback_type = "error";
        }
    } elseif ($action === 'update_quantity' && $equipment_id) {
        $new_quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if (isset($_SESSION['cart'][$equipment_id])) {
            if ($new_quantity <= 0) {
                unset($_SESSION['cart'][$equipment_id]);
                $feedback_message = htmlspecialchars($_SESSION['cart'][$equipment_id]['name'] ?? 'Item') . " removed from cart as quantity set to zero or less.";
                $feedback_type = "success";
            } elseif ($new_quantity > $_SESSION['cart'][$equipment_id]['stock']) {
                $feedback_message = "Cannot update quantity for " . htmlspecialchars($_SESSION['cart'][$equipment_id]['name']) . ". Only " . htmlspecialchars($_SESSION['cart'][$equipment_id]['stock']) . " items in stock.";
                $feedback_type = "error";
                $_SESSION['cart'][$equipment_id]['quantity'] = (int)$_SESSION['cart'][$equipment_id]['stock']; // Set to max available
            } else {
                $_SESSION['cart'][$equipment_id]['quantity'] = $new_quantity;
                $feedback_message = "Quantity for " . htmlspecialchars($_SESSION['cart'][$equipment_id]['name']) . " updated.";
                $feedback_type = "success";
            }
        } else {
            $feedback_message = "Item not found in your cart to update.";
            $feedback_type = "error";
        }
    }

    if (!empty($feedback_message)) {
        header("Location: " . $redirect_url . "?cart_feedback=" . urlencode($feedback_message) . "&feedback_type=" . urlencode($feedback_type));
    } else {
        header("Location: " . $redirect_url);
    }
    exit();
}

if (isset($_GET['cart_feedback']) && isset($_GET['feedback_type'])) {
    $feedback_message = htmlspecialchars(urldecode($_GET['cart_feedback']));
    $feedback_type = htmlspecialchars($_GET['feedback_type']);
}

$cart_items = $_SESSION['cart'] ?? [];
$cart_subtotal = 0;
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $cart_subtotal += $item['price'] * $item['quantity'];
        }
    }
}
$grand_total = $cart_subtotal;

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/cart.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex-grow: 1;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="cart-main-content">
        <div class="cart-container">
            <h1 class="cart-title">Your Shopping Cart</h1>

            <?php if (!empty($feedback_message)): ?>
                <div class="message global-message <?php echo $feedback_type === 'success' ? 'success-message' : 'error-message'; ?>">
                    <p><?php echo $feedback_message; ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($page_errors)): ?>
                <div class="message global-message error-message">
                    <?php foreach ($page_errors as $err): ?>
                        <p><?php echo htmlspecialchars($err); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="cart-empty-message">
                    <p>Your cart is currently empty.</p>
                    <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="button-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-items-table-container">
                    <table class="cart-items-table">
                        <thead>
                            <tr>
                                <th colspan="2">Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $id => $item): ?>
                                <tr>
                                    <td data-label="Product Image" class="item-image-cell">
                                        <img src="<?php echo $root_path_prefix; ?><?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'images/equipments/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                                    </td>
                                    <td data-label="Name" class="item-name-cell"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td data-label="Price">$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                                    <td data-label="Quantity" class="item-quantity-cell">
                                        <form method="POST" action="<?php echo $root_path_prefix; ?>cart.php" class="update-quantity-form">
                                            <input type="hidden" name="equipment_id" value="<?php echo htmlspecialchars($id); ?>">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0" max="<?php echo htmlspecialchars($item['stock']); ?>" class="quantity-input">
                                            <button type="submit" class="update-quantity-button">Update</button>
                                        </form>
                                    </td>
                                    <td data-label="Total">$<?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></td>
                                    <td data-label="Action" class="item-action-cell">
                                        <form method="POST" action="<?php echo $root_path_prefix; ?>cart.php" class="remove-item-form">
                                            <input type="hidden" name="equipment_id" value="<?php echo htmlspecialchars($id); ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="remove-item-button" title="Remove item">X</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-summary">
                    <div class="cart-totals">
                        <p>Subtotal: <span>$<?php echo htmlspecialchars(number_format($cart_subtotal, 2)); ?></span></p>
                        <p class="grand-total">Grand Total: <span>$<?php echo htmlspecialchars(number_format($grand_total, 2)); ?></span></p>
                    </div>
                    <div class="cart-actions">
                        <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="button-secondary">Continue Shopping</a>
                        <a href="<?php echo $root_path_prefix; ?>checkout.php" class="button-primary checkout-button">Proceed to Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>


</html>

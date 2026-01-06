<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "";

include __DIR__ . '/includes/db_connect.php'; 
$page_title = "MotionMart - Your Fitness Equipment Hub";
$current_user_id = $_SESSION['user_id'] ?? null;

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Could not connect to the database. Please try again later.");
}

// --- NEW: Handle Add to Cart action directly on this page ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add') {
    $equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity <= 0) $quantity = 1;

    if ($equipment_id && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT name, price, image_url, stock_quantity FROM equipments WHERE equipment_id = :equipment_id");
            $stmt->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
            $stmt->execute();
            $item = $stmt->fetch();

            if ($item) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                $current_quantity_in_cart = $_SESSION['cart'][$equipment_id]['quantity'] ?? 0;
                
                if (($current_quantity_in_cart + $quantity) <= $item['stock_quantity']) {
                    $_SESSION['cart'][$equipment_id]['quantity'] = $current_quantity_in_cart + $quantity;
                    $_SESSION['cart'][$equipment_id]['name'] = $item['name'];
                    $_SESSION['cart'][$equipment_id]['price'] = $item['price'];
                    $_SESSION['cart'][$equipment_id]['image_url'] = $item['image_url'];
                    $_SESSION['cart_feedback'] = htmlspecialchars($item['name']) . " has been added to your cart.";
                    $_SESSION['cart_feedback_type'] = "success";
                } else {
                    $_SESSION['cart_feedback'] = "Not enough stock for " . htmlspecialchars($item['name']) . ".";
                    $_SESSION['cart_feedback_type'] = "error";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['cart_feedback'] = "Error adding item to cart.";
            $_SESSION['cart_feedback_type'] = "error";
        }
    }
    // Redirect to the same page to prevent form resubmission on refresh
    header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
    exit();
}
// --- END: Add to Cart Logic ---


// Feedback messages from session
$feedback_message = $_SESSION['wishlist_feedback_message'] ?? $_SESSION['cart_feedback'] ?? $_SESSION['general_message'] ?? null;
$feedback_type = $_SESSION['wishlist_feedback_type'] ?? $_SESSION['cart_feedback_type'] ?? $_SESSION['general_message_type'] ?? null;

// Clear session messages after retrieving them
if (isset($_SESSION['wishlist_feedback_message'])) unset($_SESSION['wishlist_feedback_message']);
if (isset($_SESSION['wishlist_feedback_type'])) unset($_SESSION['wishlist_feedback_type']);
if (isset($_SESSION['cart_feedback'])) unset($_SESSION['cart_feedback']);
if (isset($_SESSION['cart_feedback_type'])) unset($_SESSION['cart_feedback_type']);
if (isset($_SESSION['general_message'])) unset($_SESSION['general_message']);
if (isset($_SESSION['general_message_type'])) unset($_SESSION['general_message_type']);


$page_errors = []; 
$equipments = [];
try {
    $stmt_equip = $pdo->query("SELECT equipment_id, name, description, category, price, image_url, brand, is_featured, stock_quantity FROM equipments ORDER BY is_featured DESC, name ASC");
    $equipments = $stmt_equip->fetchAll();
} catch (PDOException $e) {
    $page_errors[] = "Error fetching equipment: " . $e->getMessage();
}

$user_wishlist_ids = [];
if ($current_user_id && isset($pdo)) {
    try {
        $stmt_wishlist = $pdo->prepare("SELECT equipment_id FROM user_wishlist_items WHERE user_id = :user_id");
        $stmt_wishlist->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt_wishlist->execute();
        $user_wishlist_ids = $stmt_wishlist->fetchAll(PDO::FETCH_COLUMN, 0); 
    } catch (PDOException $e) {
        $page_errors[] = "Error fetching your wishlist: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/motionmart.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="motionmart-main-content">
        <header class="motionmart-header">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
            <p>Find the best gear to power your workouts and achieve your fitness goals!</p>
        </header>

        <div class="motionmart-container">
            <?php if ($feedback_message): ?>
                <div class="message global-message <?php echo ($feedback_type === 'error') ? 'error-message' : (($feedback_type === 'info') ? 'info-message' : 'success-message'); ?>">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($page_errors)): ?>
                <div class="message global-message error-message">
                    <?php foreach ($page_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($equipments) && empty($page_errors)): ?>
                <p class="no-items-message">No equipment currently available. Please check back soon!</p>
            <?php elseif (!empty($equipments)): ?>
                <div class="equipment-grid">
                    <?php foreach ($equipments as $item): 
                        $is_in_wishlist = $current_user_id ? in_array($item['equipment_id'], $user_wishlist_ids) : false;
                    ?>
                        <div class="equipment-card <?php echo $item['is_featured'] ? 'featured-item' : ''; ?>">
                            <a href="<?php echo $root_path_prefix; ?>equipment_details.php?id=<?php echo $item['equipment_id']; ?>" class="equipment-image-link">
                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'images/equipments/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="equipment-image">
                                <?php if ($item['is_featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
                            </a>
                            <div class="equipment-card-content">
                                <h3 class="equipment-name">
                                    <a href="<?php echo $root_path_prefix; ?>equipment_details.php?id=<?php echo $item['equipment_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                </h3>
                                <?php if (!empty($item['brand'])): ?>
                                    <p class="equipment-brand"><?php echo htmlspecialchars($item['brand']); ?></p>
                                <?php endif; ?>
                                <p class="equipment-price">$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
                                <p class="equipment-description-snippet">
                                    <?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 70)) . (strlen($item['description'] ?? '') > 70 ? '...' : ''); ?>
                                </p>
                                <div class="equipment-actions">
                                    <form action="<?php echo $root_path_prefix; ?>wishlist_handler.php" method="POST" class="wishlist-form-inline">
                                        <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                        <input type="hidden" name="action" value="<?php echo $is_in_wishlist ? 'remove_from_wishlist' : 'add_to_wishlist'; ?>">
                                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                        <button type="submit" class="wishlist-button <?php echo $is_in_wishlist ? 'active' : ''; ?>" title="<?php echo $is_in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                    
                                    <?php if ($item['stock_quantity'] > 0): ?>
                                    <!-- UPDATED FORM ACTION -->
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="add-to-cart-form">
                                        <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="action" value="add">
                                        <button type="submit" class="add-to-cart-button">Add To Cart</button>
                                    </form>
                                    <?php else: ?>
                                        <button type="button" class="add-to-cart-button" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

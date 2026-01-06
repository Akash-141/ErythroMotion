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
$page_title = "My Wishlist - ErythroMotion";
$user_id = $_SESSION['user_id'];
$wishlist_items = [];
$page_errors = [];

// Feedback messages from session (e.g., after adding/removing an item)
$feedback_message = $_SESSION['wishlist_feedback_message'] ?? null;
$feedback_type = $_SESSION['wishlist_feedback_type'] ?? null;
if (isset($_SESSION['wishlist_feedback_message'])) unset($_SESSION['wishlist_feedback_message']);
if (isset($_SESSION['wishlist_feedback_type'])) unset($_SESSION['wishlist_feedback_type']);

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch wishlist items for the current user
    // Joins with equipments table to get details of the wishlisted items
    $stmt = $pdo->prepare("
        SELECT w.wishlist_item_id, w.added_at, e.equipment_id, e.name, e.price, e.image_url, e.stock_quantity 
        FROM user_wishlist_items w
        JOIN equipments e ON w.equipment_id = e.equipment_id
        WHERE w.user_id = :user_id
        ORDER BY w.added_at DESC
    "); // [cite: DDL.sql] - references user_wishlist_items and equipments tables
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $wishlist_items = $stmt->fetchAll();

} catch (PDOException $e) {
    $page_errors[] = "Database error: Could not retrieve your wishlist. " . $e->getMessage();
    // In a production environment, log $e->getMessage() and show a generic user-friendly error.
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/wishlist.css"> <!-- New CSS file -->
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

    <main class="wishlist-main-content">
        <div class="wishlist-container">
            <h1 class="wishlist-title">My Wishlist</h1>

            <?php if ($feedback_message): ?>
                <div class="message global-message <?php echo ($feedback_type === 'error') ? 'error-message' : 'success-message'; ?>">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($page_errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($page_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($wishlist_items) && empty($page_errors)): ?>
                <div class="wishlist-empty-message">
                    <p>Your wishlist is currently empty.</p>
                    <p>Explore our <a href="<?php echo $root_path_prefix; ?>motionmart.php">MotionMart</a> to find items you love!</p>
                </div>
            <?php elseif (!empty($wishlist_items)): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item-card">
                            <a href="<?php echo $root_path_prefix; ?>equipment_details.php?id=<?php echo $item['equipment_id']; ?>" class="wishlist-item-image-link">
                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : 'images/equipments/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="wishlist-item-image">
                            </a>
                            <div class="wishlist-item-details">
                                <h3 class="wishlist-item-name">
                                    <a href="<?php echo $root_path_prefix; ?>equipment_details.php?id=<?php echo $item['equipment_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                </h3>
                                <p class="wishlist-item-price">$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></p>
                                <?php if ($item['stock_quantity'] > 0): ?>
                                    <p class="wishlist-item-stock stock-available">In Stock</p>
                                <?php else: ?>
                                    <p class="wishlist-item-stock stock-out">Out of Stock</p>
                                <?php endif; ?>
                                <small class="wishlist-item-added">Added on: <?php echo date("M j, Y", strtotime($item['added_at'])); ?></small>
                            </div>
                            <div class="wishlist-item-actions">
                                <!-- Add to Cart Form -->
                                <?php if ($item['stock_quantity'] > 0): ?>
                                <form action="<?php echo $root_path_prefix; ?>cart.php" method="POST" class="wishlist-add-to-cart-form">
                                    <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                    <input type="hidden" name="quantity" value="1"> <!-- Default quantity to add -->
                                    <input type="hidden" name="action" value="add">
                                     <input type="hidden" name="return_url" value="<?php echo $root_path_prefix; ?>wishlist.php"> <!-- For redirecting back to wishlist -->
                                    <button type="submit" class="button-primary add-to-cart-wishlist-btn">Add to Cart</button>
                                </form>
                                <?php else: ?>
                                     <button type="button" class="button-primary add-to-cart-wishlist-btn" disabled>Out of Stock</button>
                                <?php endif; ?>
                                
                                <!-- Remove from Wishlist Form (Functionality to be added next) -->
                                <form action="<?php echo $root_path_prefix; ?>wishlist_handler.php" method="POST" class="remove-from-wishlist-form">
                                    <input type="hidden" name="wishlist_item_id" value="<?php echo $item['wishlist_item_id']; ?>">
                                    <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; // Also pass equipment_id for potential alternative removal logic ?>">
                                    <input type="hidden" name="action" value="remove_from_wishlist">
                                    <button type="submit" class="button-danger remove-wishlist-btn">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>

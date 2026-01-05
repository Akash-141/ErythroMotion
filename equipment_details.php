<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "";

include __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]
$page_title = "Equipment Details - ErythroMotion";
$current_user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Guest';

$equipment = null;
$reviews = [];
$page_errors = [];
$is_in_wishlist = false;
$review_errors = [];
$review_success_message = "";
$has_purchased_item = false; // Initialize purchase status flag

// Feedback messages from session (consolidated)
$feedback_message = $_SESSION['wishlist_feedback_message'] ?? $_SESSION['cart_feedback_message'] ?? $_SESSION['review_feedback_message'] ?? $_SESSION['general_message'] ?? null;
$feedback_type = $_SESSION['wishlist_feedback_type'] ?? $_SESSION['cart_feedback_type'] ?? $_SESSION['review_feedback_type'] ?? $_SESSION['general_message_type'] ?? null;

// Clear session messages after retrieving them
if (isset($_SESSION['wishlist_feedback_message'])) unset($_SESSION['wishlist_feedback_message']);
if (isset($_SESSION['wishlist_feedback_type'])) unset($_SESSION['wishlist_feedback_type']);
if (isset($_SESSION['cart_feedback_message'])) unset($_SESSION['cart_feedback_message']);
if (isset($_SESSION['cart_feedback_type'])) unset($_SESSION['cart_feedback_type']);
if (isset($_SESSION['review_feedback_message'])) unset($_SESSION['review_feedback_message']);
if (isset($_SESSION['review_feedback_type'])) unset($_SESSION['review_feedback_type']);
if (isset($_SESSION['general_message'])) unset($_SESSION['general_message']);
if (isset($_SESSION['general_message_type'])) unset($_SESSION['general_message_type']);


$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($equipment_id <= 0) {
    header("Location: " . $root_path_prefix . "motionmart.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle Review Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
        if (!$current_user_id) {
            $_SESSION['review_feedback_message'] = "You must be logged in to submit a review.";
            $_SESSION['review_feedback_type'] = "error";
            header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($root_path_prefix . "equipment_details.php?id=" . $equipment_id . "#review-form-section"));
            exit();
        }

        // Check if user purchased the item before allowing review submission
        $stmt_check_purchase_for_review = $pdo->prepare("
            SELECT oi.order_item_id 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE o.user_id = :user_id AND oi.equipment_id = :equipment_id
            LIMIT 1
        "); // [cite: DDL.sql]
        $stmt_check_purchase_for_review->execute([':user_id' => $current_user_id, ':equipment_id' => $equipment_id]);
        if (!$stmt_check_purchase_for_review->fetch()) {
            $review_errors[] = "You can only review items you have purchased.";
        } else {
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]);
            $review_title = trim(filter_input(INPUT_POST, 'review_title', FILTER_SANITIZE_STRING));
            $review_text = trim(filter_input(INPUT_POST, 'review_text', FILTER_SANITIZE_STRING));

            if ($rating === false) {
                $review_errors[] = "Please select a valid rating (1-5 stars).";
            }
            if (empty($review_text)) {
                $review_errors[] = "Review text cannot be empty.";
            }
            if (strlen($review_title) > 255) {
                $review_errors[] = "Review title is too long (max 255 characters).";
            }

            // Optional: Check if user has already reviewed this item
            $stmt_check_prev_review = $pdo->prepare("SELECT review_id FROM equipment_reviews WHERE equipment_id = :equipment_id AND user_id = :user_id"); // [cite: DDL.sql]
            $stmt_check_prev_review->execute([':equipment_id' => $equipment_id, ':user_id' => $current_user_id]);
            if ($stmt_check_prev_review->fetch()) {
                $review_errors[] = "You have already submitted a review for this product.";
            }


            if (empty($review_errors)) {
                try {
                    $sql_insert_review = "INSERT INTO equipment_reviews (equipment_id, user_id, rating, review_title, review_text) 
                                          VALUES (:equipment_id, :user_id, :rating, :review_title, :review_text)";
                    $stmt_insert_review = $pdo->prepare($sql_insert_review);
                    $stmt_insert_review->execute([
                        ':equipment_id' => $equipment_id,
                        ':user_id' => $current_user_id,
                        ':rating' => $rating,
                        ':review_title' => $review_title,
                        ':review_text' => $review_text
                    ]);
                    $_SESSION['review_feedback_message'] = "Your review has been submitted successfully!";
                    $_SESSION['review_feedback_type'] = "success";
                    header("Location: " . $root_path_prefix . "equipment_details.php?id=" . $equipment_id . "&review_submitted=1#reviews-section");
                    exit();
                } catch (PDOException $e) {
                    $review_errors[] = "Failed to submit your review. Please try again. ";
                }
            }
        }
    }

    // Fetch equipment details
    $stmt = $pdo->prepare("SELECT * FROM equipments WHERE equipment_id = :equipment_id");
    $stmt->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
    $stmt->execute();
    $equipment = $stmt->fetch();

    if (!$equipment) {
        $page_errors[] = "Equipment not found.";
        $page_title = "Error - ErythroMotion";
    } else {
        $page_title = htmlspecialchars($equipment['name']) . " - ErythroMotion";

        // Fetch approved reviews
        $stmt_reviews = $pdo->prepare("
            SELECT er.*, u.username 
            FROM equipment_reviews er
            JOIN users u ON er.user_id = u.user_id
            WHERE er.equipment_id = :equipment_id AND er.is_approved = 1
            ORDER BY er.review_date DESC
        ");
        $stmt_reviews->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
        $stmt_reviews->execute();
        $reviews = $stmt_reviews->fetchAll();

        // Check if current user has purchased this item (for displaying review form)
        if ($current_user_id) {
            $stmt_check_purchase = $pdo->prepare("
                SELECT oi.order_item_id 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                WHERE o.user_id = :user_id AND oi.equipment_id = :equipment_id
                LIMIT 1
            "); // [cite: DDL.sql]
            $stmt_check_purchase->execute([':user_id' => $current_user_id, ':equipment_id' => $equipment_id]);
            if ($stmt_check_purchase->fetch()) {
                $has_purchased_item = true;
            }
        }

        // Check wishlist status (existing logic)
        if ($current_user_id) {
            $stmt_check_wishlist = $pdo->prepare("SELECT wishlist_item_id FROM user_wishlist_items WHERE user_id = :user_id AND equipment_id = :equipment_id");
            $stmt_check_wishlist->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            $stmt_check_wishlist->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
            $stmt_check_wishlist->execute();
            if ($stmt_check_wishlist->fetch()) {
                $is_in_wishlist = true;
            }
        }
    }
} catch (PDOException $e) {
    $page_errors[] = "Database error: " . $e->getMessage();
    $page_title = "Error - ErythroMotion";
}

// Cart adding logic (existing)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'add_to_cart_detail') {
    // ... (existing cart logic remains here, ensure feedback uses $_SESSION['cart_feedback_message'])
    $posted_equipment_id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : null;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity <= 0) $quantity = 1;
    if ($posted_equipment_id && $posted_equipment_id === $equipment_id && $equipment) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $current_quantity_in_cart = $_SESSION['cart'][$equipment_id]['quantity'] ?? 0;
        $requested_total_quantity = $current_quantity_in_cart + $quantity;
        if ($requested_total_quantity <= $equipment['stock_quantity']) {
            if (isset($_SESSION['cart'][$equipment_id])) $_SESSION['cart'][$equipment_id]['quantity'] += $quantity;
            else $_SESSION['cart'][$equipment_id] = ['name' => $equipment['name'], 'price' => $equipment['price'], 'quantity' => $quantity, 'image_url' => $equipment['image_url'], 'stock' => $equipment['stock_quantity']];
            $_SESSION['cart_feedback_message'] = htmlspecialchars($equipment['name']) . " (x" . $quantity . ") added to cart!";
            $_SESSION['cart_feedback_type'] = "success";
        } else {
            $can_add = $equipment['stock_quantity'] - $current_quantity_in_cart;
            $_SESSION['cart_feedback_message'] = "Cannot add " . htmlspecialchars($equipment['name']) . ". " . (($can_add > 0) ? "Only " . $can_add . " more available." : "No more items in stock.");
            $_SESSION['cart_feedback_type'] = "error";
        }
    } else {
        $_SESSION['cart_feedback_message'] = "Invalid request or equipment mismatch for cart.";
        $_SESSION['cart_feedback_type'] = "error";
    }
    header("Location: " . $root_path_prefix . "equipment_details.php?id=" . $equipment_id);
    exit();
}

// If review was just submitted, show specific session message
if (isset($_GET['review_submitted']) && $_GET['review_submitted'] == '1' && isset($_SESSION['review_feedback_message'])) {
    $feedback_message = $_SESSION['review_feedback_message'];
    $feedback_type = $_SESSION['review_feedback_type'];
    unset($_SESSION['review_feedback_message']);
    unset($_SESSION['review_feedback_type']);
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/equipment_details.css"> <!-- [cite: equipment_details_css_review_form] -->
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
    <?php include __DIR__ . '/includes/navbar.php'; // [cite: navbar_php_wishlist_link] 
    ?>

    <main class="equipment-details-main">
        <div class="equipment-details-container">
            <?php if (!empty($page_errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($page_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                    <p><a href="<?php echo $root_path_prefix; ?>motionmart.php">Back to MotionMart</a></p>
                </div>
            <?php elseif ($equipment): ?>
                <?php if ($feedback_message): ?>
                    <div class="message global-message <?php echo ($feedback_type === 'error') ? 'error-message' : (($feedback_type === 'info') ? 'info-message' : 'success-message'); ?>">
                        <p><?php echo htmlspecialchars($feedback_message); ?></p>
                    </div>
                <?php endif; ?>

                <div class="product-layout">
                    <!-- ... existing product layout code ... -->
                    <div class="product-image-column">
                        <img src="<?php echo $root_path_prefix; ?><?php echo !empty($equipment['image_url']) ? htmlspecialchars($equipment['image_url']) : 'images/equipments/placeholder_large.jpg'; ?>" alt="<?php echo htmlspecialchars($equipment['name']); ?>" class="product-main-image">
                    </div>
                    <div class="product-info-column">
                        <h1 class="product-name"><?php echo htmlspecialchars($equipment['name']); ?></h1>
                        <?php if (!empty($equipment['brand'])): ?>
                            <p class="product-brand">Brand: <?php echo htmlspecialchars($equipment['brand']); ?></p>
                        <?php endif; ?>
                        <p class="product-price">$<?php echo htmlspecialchars(number_format($equipment['price'], 2)); ?></p>
                        <div class="product-stock">
                            <?php if ($equipment['stock_quantity'] > 0 && $equipment['stock_quantity'] <= 10): ?>
                                <p class="stock-low">Only <?php echo $equipment['stock_quantity']; ?> left in stock!</p>
                            <?php elseif ($equipment['stock_quantity'] > 10): ?>
                                <p class="stock-available">In Stock</p>
                            <?php else: ?> <p class="stock-out">Out of Stock</p> <?php endif; ?>
                        </div>
                        <div class="product-description-full">
                            <h3>Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($equipment['description'] ?? 'No description available.')); ?></p>
                        </div>
                        <?php if (!empty($equipment['specifications'])): ?>
                            <div class="product-specifications">
                                <h3>Specifications</h3>
                                <p><?php echo nl2br(htmlspecialchars($equipment['specifications'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="product-actions-group">
                            <?php if ($equipment['stock_quantity'] > 0): ?>
                                <form action="<?php echo $root_path_prefix; ?>equipment_details.php?id=<?php echo $equipment_id; ?>" method="POST" class="add-to-cart-detailed-form">
                                    <input type="hidden" name="equipment_id" value="<?php echo $equipment_id; ?>">
                                    <input type="hidden" name="action" value="add_to_cart_detail">
                                    <div class="form-group quantity-selector">
                                        <label for="quantity">Quantity:</label>
                                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($equipment['stock_quantity']); ?>" required>
                                    </div>
                                    <button type="submit" class="button-primary add-to-cart-button-detail">Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <div class="add-to-cart-detailed-form"> <button type="button" class="button-primary add-to-cart-button-detail" disabled>Out of Stock</button> </div>
                            <?php endif; ?>
                            <?php if ($current_user_id): ?>
                                <form action="<?php echo $root_path_prefix; ?>wishlist_handler.php" method="POST" class="wishlist-form-detail-page">
                                    <input type="hidden" name="equipment_id" value="<?php echo $equipment['equipment_id']; ?>">
                                    <input type="hidden" name="action" value="<?php echo $is_in_wishlist ? 'remove_from_wishlist' : 'add_to_wishlist'; ?>">
                                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="wishlist-button-detail <?php echo $is_in_wishlist ? 'active' : ''; ?>" title="<?php echo $is_in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                        </svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="reviews-section" id="reviews-section">
                    <h2 class="section-title">Customer Reviews</h2>
                    <?php if (empty($reviews)): ?>
                        <p>No reviews yet for this product.</p>
                    <?php else: ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-rating">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <span class="star <?php echo ($i < $review['rating']) ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if (!empty($review['review_title'])): ?>
                                        <h4 class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></h4>
                                    <?php endif; ?>
                                    <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    <p class="review-meta">By <strong><?php echo htmlspecialchars($review['username']); ?></strong> on <?php echo date("M j, Y", strtotime($review['review_date'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="review-form-section" id="review-form-section">
                    <h2 class="section-title">Write a Review</h2>
                    <?php if (!empty($review_errors)): ?>
                        <div class="message error-message global-message">
                            <?php foreach ($review_errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($current_user_id): ?>
                        <?php if ($has_purchased_item): ?>
                            <?php // Check if user has already reviewed this item
                            $has_reviewed = false;
                            if (isset($pdo)) { // Ensure $pdo is available
                                $stmt_already_reviewed = $pdo->prepare("SELECT review_id FROM equipment_reviews WHERE equipment_id = :equipment_id AND user_id = :user_id");
                                $stmt_already_reviewed->execute([':equipment_id' => $equipment_id, ':user_id' => $current_user_id]);
                                if ($stmt_already_reviewed->fetch()) {
                                    $has_reviewed = true;
                                }
                            }
                            ?>
                            <?php if ($has_reviewed): ?>
                                <p>You have already submitted a review for this product. Thank you for your feedback!</p>
                            <?php else: ?>
                                <form action="equipment_details.php?id=<?php echo $equipment_id; ?>#review-form-section" method="POST" class="review-form">
                                    <div class="form-group rating-group">
                                        <label for="rating">Your Rating:</label>
                                        <div class="star-rating">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?> required>
                                                <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="review_title">Review Title (Optional):</label>
                                        <input type="text" id="review_title" name="review_title" value="<?php echo htmlspecialchars($_POST['review_title'] ?? ''); ?>" maxlength="255">
                                    </div>
                                    <div class="form-group">
                                        <label for="review_text">Your Review:</label>
                                        <textarea id="review_text" name="review_text" rows="5" required><?php echo htmlspecialchars($_POST['review_text'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="button-primary submit-review-button">Submit Review</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>You must purchase this item to write a review.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Please <a href="<?php echo $root_path_prefix; ?>login.php?redirect=<?php echo urlencode($root_path_prefix . 'equipment_details.php?id=' . $equipment_id . '#review-form-section'); ?>">login</a> to write a review.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>The equipment you are looking for could not be found.</p>
                <p><a href="<?php echo $root_path_prefix; ?>motionmart.php">Back to MotionMart</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] 
    ?>
</body>

</html>
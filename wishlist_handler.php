<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

// Ensure user is logged in to modify wishlist
if (!isset($_SESSION['user_id'])) {
    // If not logged in, set an error message and redirect to login
    // Or, for a less disruptive UX on add, you could redirect to login with a return_url.
    // For now, let's be strict.
    $_SESSION['general_message'] = "You need to be logged in to manage your wishlist.";
    $_SESSION['general_message_type'] = "error";
    header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? $root_path_prefix . "motionmart.php"));
    exit();
}

include __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]
$user_id = $_SESSION['user_id'];
$feedback_message = "";
$feedback_type = "error"; // Default to error

// Determine the referring page or a default fallback
$return_url = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? $root_path_prefix . "motionmart.php";
// Sanitize return_url to prevent open redirect vulnerabilities (basic example)
if (filter_var($return_url, FILTER_VALIDATE_URL) === false || 
    parse_url($return_url, PHP_URL_HOST) !== $_SERVER['HTTP_HOST']) {
    $return_url = $root_path_prefix . "motionmart.php"; // Fallback to a safe default
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $equipment_id = isset($_POST['equipment_id']) ? filter_var($_POST['equipment_id'], FILTER_VALIDATE_INT) : null;

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($action === 'add_to_wishlist' && $equipment_id) {
            // Check if item already exists in wishlist for this user
            $stmt_check = $pdo->prepare("SELECT wishlist_item_id FROM user_wishlist_items WHERE user_id = :user_id AND equipment_id = :equipment_id"); // [cite: DDL.sql]
            $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
            $stmt_check->execute();

            if ($stmt_check->fetch()) {
                $feedback_message = "This item is already in your wishlist.";
                $feedback_type = "info"; // Or error, depending on desired UX
            } else {
                // Add to wishlist
                $stmt_add = $pdo->prepare("INSERT INTO user_wishlist_items (user_id, equipment_id) VALUES (:user_id, :equipment_id)"); // [cite: DDL.sql]
                $stmt_add->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_add->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
                $stmt_add->execute();
                $feedback_message = "Item added to your wishlist successfully!";
                $feedback_type = "success";
            }
        } elseif ($action === 'remove_from_wishlist') {
            // Removal can be by wishlist_item_id (if coming from wishlist.php) 
            // or by equipment_id (if coming from product page where wishlist_item_id isn't readily available)
            $wishlist_item_id = isset($_POST['wishlist_item_id']) ? filter_var($_POST['wishlist_item_id'], FILTER_VALIDATE_INT) : null;

            if ($wishlist_item_id) {
                $stmt_remove = $pdo->prepare("DELETE FROM user_wishlist_items WHERE wishlist_item_id = :wishlist_item_id AND user_id = :user_id"); // [cite: DDL.sql]
                $stmt_remove->bindParam(':wishlist_item_id', $wishlist_item_id, PDO::PARAM_INT);
                $stmt_remove->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            } elseif ($equipment_id) { // Fallback to remove by equipment_id if wishlist_item_id is not provided
                $stmt_remove = $pdo->prepare("DELETE FROM user_wishlist_items WHERE user_id = :user_id AND equipment_id = :equipment_id"); // [cite: DDL.sql]
                $stmt_remove->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_remove->bindParam(':equipment_id', $equipment_id, PDO::PARAM_INT);
            } else {
                $feedback_message = "Invalid request: Missing item identifier for removal.";
                // No redirect here, let it fall through to the end to set session message and redirect
            }

            if (isset($stmt_remove)) {
                $stmt_remove->execute();
                if ($stmt_remove->rowCount() > 0) {
                    $feedback_message = "Item removed from your wishlist.";
                    $feedback_type = "success";
                } else {
                    $feedback_message = "Item not found in your wishlist or could not be removed.";
                    // $feedback_type remains "error" or can be set to "info"
                }
            }

        } else {
            $feedback_message = "Invalid wishlist action specified.";
        }

    } catch (PDOException $e) {
        $feedback_message = "A database error occurred. Please try again. Details: " . $e->getMessage(); // Log $e->getMessage() in production
        $feedback_type = "error";
    }

} else {
    $feedback_message = "No action specified or invalid request method.";
}

// Store feedback in session to display on the return page
$_SESSION['wishlist_feedback_message'] = $feedback_message;
$_SESSION['wishlist_feedback_type'] = $feedback_type;

// Redirect back
header("Location: " . $return_url);
exit();
?>

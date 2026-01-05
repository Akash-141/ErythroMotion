<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../";

// Admin access check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_id'])) {
    $category_id_to_delete = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);

    if ($category_id_to_delete === false || $category_id_to_delete <= 0) {
        $_SESSION['admin_message'] = "Invalid Category ID.";
        $_SESSION['admin_message_type'] = "error";
        header("Location: manage_blog_categories.php");
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // The foreign key constraint in `blog_posts` is `ON DELETE SET NULL`,
        // so we don't need to manually update posts. Deleting the category
        // will automatically set `category_id` to NULL for associated posts.
        $sql = "DELETE FROM blog_categories WHERE category_id = :category_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':category_id', $category_id_to_delete, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $_SESSION['admin_message'] = "Category deleted successfully. Posts in this category are now uncategorized.";
            } else {
                $_SESSION['admin_message'] = "Category not found or already deleted.";
                $_SESSION['admin_message_type'] = "error";
            }
        } else {
            $_SESSION['admin_message'] = "Failed to execute deletion.";
            $_SESSION['admin_message_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = "Database error: " . $e->getMessage(); // Log error in production
        $_SESSION['admin_message_type'] = "error";
    }
} else {
    // If not a POST request or category_id is not set, redirect
    $_SESSION['admin_message'] = "Invalid request.";
    $_SESSION['admin_message_type'] = "error";
}

header("Location: manage_blog_categories.php");
exit();
?>

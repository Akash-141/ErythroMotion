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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id'])) {
    $post_id_to_delete = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);

    if ($post_id_to_delete === false || $post_id_to_delete <= 0) {
        $_SESSION['admin_message'] = "Invalid Post ID.";
        $_SESSION['admin_message_type'] = "error"; // You can use this to style messages
        header("Location: manage_blog_posts.php");
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // The foreign key on `blog_comments` has ON DELETE CASCADE, so comments will be deleted automatically.
        $sql = "DELETE FROM blog_posts WHERE post_id = :post_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':post_id', $post_id_to_delete, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $_SESSION['admin_message'] = "Blog post deleted successfully.";
            } else {
                $_SESSION['admin_message'] = "Post not found or already deleted.";
                $_SESSION['admin_message_type'] = "error";
            }
        } else {
            $_SESSION['admin_message'] = "Failed to execute deletion.";
            $_SESSION['admin_message_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = "Database error: " . $e->getMessage();
        $_SESSION['admin_message_type'] = "error";
    }
} else {
    $_SESSION['admin_message'] = "Invalid request.";
    $_SESSION['admin_message_type'] = "error";
}

header("Location: manage_blog_posts.php");
exit();
?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "";

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php");
    exit();
}

include __DIR__ . '/includes/db_connect.php';
$current_user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id'])) {
    $post_id_to_delete = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);

    if ($post_id_to_delete) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Security check: Delete only if the post_id belongs to the current logged-in user
            $sql = "DELETE FROM blog_posts WHERE post_id = :post_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':post_id', $post_id_to_delete, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['feedback_message'] = "Your post has been deleted successfully.";
                $_SESSION['feedback_type'] = "success";
            } else {
                $_SESSION['feedback_message'] = "Could not delete post. It may have already been deleted, or you do not have permission.";
                $_SESSION['feedback_type'] = "error";
            }
        } catch (PDOException $e) {
            $_SESSION['feedback_message'] = "Database error. Could not delete post.";
            $_SESSION['feedback_type'] = "error";
        }
    } else {
        $_SESSION['feedback_message'] = "Invalid post ID for deletion.";
        $_SESSION['feedback_type'] = "error";
    }
} else {
    $_SESSION['feedback_message'] = "Invalid request.";
    $_SESSION['feedback_type'] = "error";
}

header("Location: profile.php#my-blog-posts-section");
exit();
?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php");
    exit();
}

include __DIR__ . '/includes/db_connect.php';
$page_title = "Edit Blog Post - ErythroMotion";
$current_user_id = $_SESSION['user_id'];
$errors = [];
$post_data = [];
$categories = [];

// Function to create a URL-friendly slug
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

$post_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id_to_edit && isset($_POST['post_id'])) {
    $post_id_to_edit = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
}

if (!$post_id_to_edit) {
    header("Location: profile.php?feedback=invalid_id");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // First, verify the post exists and belongs to the current user
    $stmt_verify = $pdo->prepare("SELECT * FROM blog_posts WHERE post_id = :post_id AND user_id = :user_id");
    $stmt_verify->execute([':post_id' => $post_id_to_edit, ':user_id' => $current_user_id]);
    $post_to_verify = $stmt_verify->fetch();

    if (!$post_to_verify) {
        // Post doesn't exist or doesn't belong to the user
        $_SESSION['feedback_message'] = "You do not have permission to edit this post, or it does not exist.";
        $_SESSION['feedback_type'] = "error";
        header("Location: profile.php#my-blog-posts-section");
        exit();
    }
    
    // Fetch categories for the dropdown
    $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
        $post_data = $_POST;
        $title = trim($post_data['title'] ?? '');
        $slug = trim($post_data['slug'] ?? '');
        $content = trim($post_data['content'] ?? '');
        $category_id = filter_var($post_data['category_id'] ?? null, FILTER_VALIDATE_INT);
        $featured_image_url = trim($post_data['featured_image_url'] ?? '');
        // When a user edits a post, it should go back to pending review
        $status = 'pending_review';

        // Validation
        if (empty($title)) $errors[] = "Post title is required.";
        if (empty($content)) $errors[] = "Post content cannot be empty.";
        if (empty($slug)) { $slug = createSlug($title); } 
        elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) { $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens."; }

        if (empty($errors)) {
            $stmt_check_slug = $pdo->prepare("SELECT post_id FROM blog_posts WHERE slug = :slug AND post_id != :post_id");
            $stmt_check_slug->execute([':slug' => $slug, ':post_id' => $post_id_to_edit]);
            if ($stmt_check_slug->fetch()) {
                $errors[] = "This slug is already in use by another post. Please choose a unique one.";
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE blog_posts SET category_id = :category_id, title = :title, slug = :slug, content = :content, featured_image_url = :featured_image_url, status = :status WHERE post_id = :post_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':category_id' => $category_id ?: null,
                ':title' => $title,
                ':slug' => $slug,
                ':content' => $content,
                ':featured_image_url' => $featured_image_url,
                ':status' => $status,
                ':post_id' => $post_id_to_edit,
                ':user_id' => $current_user_id
            ]);
            
            $_SESSION['feedback_message'] = "Your post '{$title}' has been updated and submitted for review.";
            $_SESSION['feedback_type'] = "success";
            header("Location: profile.php#my-blog-posts-section");
            exit();
        }
    } else {
        // On initial load, use the data we already verified and fetched
        $post_data = $post_to_verify;
    }
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/create_post.css"> <!-- Reusing styles from create_post -->
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: var(--spacing-lg) var(--spacing-md); background-color: #f4f7f6; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <div class="create-post-container">
            <div class="create-post-header">
                <h1>Edit Your Blog Post</h1>
                <p>Make changes to your post below. Re-submitting will send your post for admin review again.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="edit_post.php?id=<?php echo $post_id_to_edit; ?>" method="POST" class="post-form">
                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_data['post_id']); ?>">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($post_data['slug'] ?? ''); ?>" required>
                    <small>Changing this will change the post's direct link. Use only letters, numbers, and hyphens.</small>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="15" required><?php echo htmlspecialchars($post_data['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="featured_image_url">Featured Image URL (Optional)</label>
                    <input type="text" id="featured_image_url" name="featured_image_url" value="<?php echo htmlspecialchars($post_data['featured_image_url'] ?? ''); ?>" placeholder="e.g., https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($post_data['category_id']) && $post_data['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                     <a href="profile.php#my-blog-posts-section" class="button-secondary">Cancel</a>
                    <button type="submit" name="update_post" class="button-primary">Update and Submit for Review</button>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>



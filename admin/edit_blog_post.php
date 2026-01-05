<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php';
$page_title = "Edit Blog Post - Admin";
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
    header("Location: manage_blog_posts.php?message=" . urlencode("Invalid Post ID.") . "&type=error");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch categories for the dropdown
    $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll();

    // Handle form submission for updating the post
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
        $post_data = $_POST;
        $title = trim($post_data['title'] ?? '');
        $slug = trim($post_data['slug'] ?? '');
        $content = trim($post_data['content'] ?? '');
        $category_id = filter_var($post_data['category_id'] ?? null, FILTER_VALIDATE_INT);
        $status = trim($post_data['status'] ?? 'draft');
        $featured_image_url = trim($post_data['featured_image_url'] ?? '');

        if (empty($title)) $errors[] = "Post title is required.";
        if (empty($content)) $errors[] = "Post content cannot be empty.";
        if (empty($slug)) {
            $slug = createSlug($title);
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens.";
        }
        if (!in_array($status, ['draft', 'published', 'pending_review', 'archived'])) {
            $errors[] = "Invalid status selected.";
        }

        if (empty($errors)) {
            $stmt_check_slug = $pdo->prepare("SELECT post_id FROM blog_posts WHERE slug = :slug AND post_id != :post_id");
            $stmt_check_slug->execute([':slug' => $slug, ':post_id' => $post_id_to_edit]);
            if ($stmt_check_slug->fetch()) {
                $errors[] = "This slug is already in use by another post. Please choose a unique one.";
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE blog_posts SET 
                        category_id = :category_id, 
                        title = :title, 
                        slug = :slug, 
                        content = :content, 
                        featured_image_url = :featured_image_url, 
                        status = :status 
                    WHERE post_id = :post_id";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':category_id' => $category_id ?: null,
                ':title' => $title,
                ':slug' => $slug,
                ':content' => $content,
                ':featured_image_url' => $featured_image_url,
                ':status' => $status,
                ':post_id' => $post_id_to_edit
            ]);
            
            $_SESSION['admin_message'] = "Blog post '{$title}' updated successfully!";
            header("Location: manage_blog_posts.php");
            exit();
        }
    } else {
        // Fetch existing post data on initial load
        $stmt_fetch_post = $pdo->prepare("SELECT * FROM blog_posts WHERE post_id = :post_id");
        $stmt_fetch_post->execute([':post_id' => $post_id_to_edit]);
        $post_data = $stmt_fetch_post->fetch();

        if (!$post_data) {
            header("Location: manage_blog_posts.php?message=" . urlencode("Post not found.") . "&type=error");
            exit();
        }
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_layout.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_blog.css">
    <style> body { display: flex; flex-direction: column; min-height: 100vh; padding-top: var(--navbar-height); } </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container">
                <div class="admin-header">
                    <h1 class="admin-page-title">Edit Blog Post</h1>
                     <a href="manage_blog_posts.php" class="admin-button plain-button">Back to All Posts</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="edit_blog_post.php?id=<?php echo htmlspecialchars($post_id_to_edit); ?>" method="POST" class="admin-form">
                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post_data['post_id']); ?>">
                    <div class="form-group">
                        <label for="title">Post Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post_data['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($post_data['slug'] ?? ''); ?>" required>
                         <small>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</small>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="15" required><?php echo htmlspecialchars($post_data['content'] ?? ''); ?></textarea>
                    </div>
                     <div class="form-group">
                        <label for="featured_image_url">Featured Image URL</label>
                        <input type="text" id="featured_image_url" name="featured_image_url" value="<?php echo htmlspecialchars($post_data['featured_image_url'] ?? ''); ?>" placeholder="e.g., images/blog/post-image.jpg">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">Uncategorized</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($post_data['category_id']) && $post_data['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo (isset($post_data['status']) && $post_data['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo (isset($post_data['status']) && $post_data['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="pending_review" <?php echo (isset($post_data['status']) && $post_data['status'] === 'pending_review') ? 'selected' : ''; ?>>Pending Review</option>
                                <option value="archived" <?php echo (isset($post_data['status']) && $post_data['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_post" class="admin-button add-new-button">Update Post</button>
                </form>

            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

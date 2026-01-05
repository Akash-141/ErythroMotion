<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 

// Redirect user to login if they are not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

include __DIR__ . '/includes/db_connect.php';
$page_title = "Create New Blog Post - ErythroMotion";
$errors = [];
$form_data = $_POST;
$categories = [];

// Function to create a URL-friendly slug
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch categories for the dropdown
    $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
        $title = trim($form_data['title'] ?? '');
        $slug = trim($form_data['slug'] ?? '');
        $content = trim($form_data['content'] ?? '');
        $category_id = filter_var($form_data['category_id'] ?? null, FILTER_VALIDATE_INT);
        $featured_image_url = trim($form_data['featured_image_url'] ?? '');
        
        // User-submitted posts will default to 'pending_review'
        $status = 'pending_review';

        // Validation
        if (empty($title)) {
            $errors[] = "Post title is required.";
        }
        if (empty($content)) {
            $errors[] = "Post content cannot be empty.";
        }
        if (empty($slug)) {
            $slug = createSlug($title);
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens.";
        }

        if (empty($errors)) {
            $stmt_check_slug = $pdo->prepare("SELECT post_id FROM blog_posts WHERE slug = :slug");
            $stmt_check_slug->execute([':slug' => $slug]);
            if ($stmt_check_slug->fetch()) {
                $errors[] = "This slug is already in use. Please try a different title or specify a unique slug.";
            }
        }

        if (empty($errors)) {
            $sql = "INSERT INTO blog_posts (user_id, category_id, title, slug, content, featured_image_url, status) 
                    VALUES (:user_id, :category_id, :title, :slug, :content, :featured_image_url, :status)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':category_id' => $category_id ?: null,
                ':title' => $title,
                ':slug' => $slug,
                ':content' => $content,
                ':featured_image_url' => $featured_image_url,
                ':status' => $status
            ]);
            
            $_SESSION['feedback_message'] = "Your post '{$title}' has been submitted for review!";
            $_SESSION['feedback_type'] = "success";
            header("Location: profile.php#my-blog-posts-section");
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/create_post.css"> <!-- New CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
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
                <h1>Create a New Blog Post</h1>
                <p>Share your knowledge, experiences, and tips with the ErythroMotion community. All posts are submitted for review before publishing.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="create_post.php" method="POST" class="post-form">
                <div class="form-group">
                    <label for="title">Post Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="slug">URL Slug (Optional)</label>
                    <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($form_data['slug'] ?? ''); ?>" placeholder="auto-generated-if-blank">
                     <small>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</small>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="15" placeholder="Write your article here... Markdown is not yet supported." required><?php echo htmlspecialchars($form_data['content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="featured_image_url">Featured Image URL (Optional)</label>
                    <input type="text" id="featured_image_url" name="featured_image_url" value="<?php echo htmlspecialchars($form_data['featured_image_url'] ?? ''); ?>" placeholder="e.g., https://example.com/image.jpg">
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                     <a href="profile.php#my-blog-posts-section" class="button-secondary">Cancel</a>
                    <button type="submit" name="create_post" class="button-primary">Submit for Review</button>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

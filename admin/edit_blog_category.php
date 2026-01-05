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
$page_title = "Edit Blog Category - Admin";
$errors = [];
$category_data = [];

// Function to create a URL-friendly slug from a string (same as in manage_blog_categories.php)
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

$category_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$category_id_to_edit && isset($_POST['category_id'])) {
    $category_id_to_edit = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
}

if (!$category_id_to_edit) {
    header("Location: manage_blog_categories.php?message=" . urlencode("Invalid Category ID.") . "&type=error");
    exit();
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
        $category_data = $_POST;
        $name = trim($category_data['name'] ?? '');
        $slug = trim($category_data['slug'] ?? '');
        $description = trim($category_data['description'] ?? '');

        if (empty($name)) {
            $errors[] = "Category name is required.";
        }
        if (empty($slug)) {
            $slug = createSlug($name);
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens.";
        }
        
        if (empty($errors)) {
            // Check for unique name or slug, excluding the current category
            $stmt_check = $pdo->prepare("SELECT category_id FROM blog_categories WHERE (name = :name OR slug = :slug) AND category_id != :category_id");
            $stmt_check->execute([':name' => $name, ':slug' => $slug, ':category_id' => $category_id_to_edit]);
            if ($stmt_check->fetch()) {
                $errors[] = "Another category with this name or slug already exists.";
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE blog_categories SET name = :name, slug = :slug, description = :description WHERE category_id = :category_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':name' => $name, ':slug' => $slug, ':description' => $description, ':category_id' => $category_id_to_edit]);
            
            $_SESSION['admin_message'] = "Category '{$name}' updated successfully!";
            header("Location: manage_blog_categories.php");
            exit();
        }
    } else {
        // Fetch existing category data for the form on initial load
        $stmt_fetch = $pdo->prepare("SELECT * FROM blog_categories WHERE category_id = :category_id");
        $stmt_fetch->execute([':category_id' => $category_id_to_edit]);
        $category_data = $stmt_fetch->fetch();

        if (!$category_data) {
            header("Location: manage_blog_categories.php?message=" . urlencode("Category not found.") . "&type=error");
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_add_equipment.css"> <!-- Reusing form styles -->
    <style> body { display: flex; flex-direction: column; min-height: 100vh; padding-top: var(--navbar-height); } </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container form-container">
                 <div class="admin-header">
                    <h1 class="admin-page-title">Edit Category</h1>
                    <a href="manage_blog_categories.php" class="admin-button plain-button">Back to Categories</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="edit_blog_category.php?id=<?php echo htmlspecialchars($category_id_to_edit); ?>" method="POST" class="admin-form">
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_data['category_id']); ?>">
                    <div class="form-group">
                        <label for="name">Category Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category_data['name'] ?? ''); ?>" required>
                    </div>
                     <div class="form-group">
                        <label for="slug">Slug:</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($category_data['slug'] ?? ''); ?>" required>
                        <small>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</small>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($category_data['description'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_category" class="admin-button add-new-button">Update Category</button>
                </form>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

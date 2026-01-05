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
$page_title = "Manage Blog Categories - Admin";
$errors = [];
$form_data = $_POST;
$categories = [];

// Function to create a URL-friendly slug from a string
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string); // Remove special characters
    $string = preg_replace('/[\s-]+/', '-', $string);      // Replace spaces and hyphens with a single hyphen
    $string = trim($string, '-');                         // Trim hyphens from ends
    return $string;
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle form submission for adding a new category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
        $name = trim($form_data['name'] ?? '');
        $slug = trim($form_data['slug'] ?? '');
        $description = trim($form_data['description'] ?? '');

        if (empty($name)) {
            $errors[] = "Category name is required.";
        }

        if (empty($slug)) {
            $slug = createSlug($name);
        } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens.";
        }
        
        if(empty($errors)) {
            $stmt_check = $pdo->prepare("SELECT * FROM blog_categories WHERE name = :name OR slug = :slug");
            $stmt_check->execute([':name' => $name, ':slug' => $slug]);
            if ($stmt_check->fetch()) {
                $errors[] = "A category with this name or slug already exists.";
            }
        }

        if (empty($errors)) {
            $sql = "INSERT INTO blog_categories (name, slug, description) VALUES (:name, :slug, :description)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':name' => $name, ':slug' => $slug, ':description' => $description]);
            
            $_SESSION['admin_message'] = "Category '{$name}' created successfully!";
            header("Location: manage_blog_categories.php");
            exit();
        }
    }

    // Fetch all existing categories to display
    $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll();

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

$feedback_message = $_SESSION['admin_message'] ?? null;
if (isset($_SESSION['admin_message'])) unset($_SESSION['admin_message']);

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
                    <h1 class="admin-page-title">Manage Blog Categories</h1>
                </div>

                <?php if ($feedback_message): ?>
                    <div class="message success-message global-message"><p><?php echo htmlspecialchars($feedback_message); ?></p></div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="admin-layout-split">
                    <div class="form-container-split">
                        <h2 class="form-title">Add New Category</h2>
                        <form action="manage_blog_categories.php" method="POST" class="admin-form">
                            <div class="form-group">
                                <label for="name">Category Name:</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                            </div>
                             <div class="form-group">
                                <label for="slug">Slug:</label>
                                <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($form_data['slug'] ?? ''); ?>" placeholder="auto-generated-if-blank">
                                <small>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</small>
                            </div>
                            <div class="form-group">
                                <label for="description">Description:</label>
                                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="add_category" class="admin-button add-new-button">Add Category</button>
                        </form>
                    </div>

                    <div class="table-container-split">
                        <h2 class="form-title">Existing Categories</h2>
                         <?php if (empty($categories)): ?>
                            <p>No categories found.</p>
                        <?php else: ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['category_id']); ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                            <td class="actions-cell">
                                                <a href="edit_blog_category.php?id=<?php echo $category['category_id']; ?>" class="admin-button edit-button">Edit</a>
                                                <form action="delete_blog_category.php" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this category? This might affect existing posts.');">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                    <button type="submit" class="admin-button delete-button">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>

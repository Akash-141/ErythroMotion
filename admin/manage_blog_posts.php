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
$page_title = "Manage Blog Posts - Admin";
$errors = [];
$posts = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- Pagination Logic ---
    $records_per_page = 10;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $records_per_page;

    $stmt_total = $pdo->query("SELECT COUNT(*) FROM blog_posts");
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch paginated posts with author and category names
    $sql = "SELECT 
                bp.post_id, 
                bp.title, 
                bp.status, 
                bp.created_at, 
                u.username AS author_name, 
                bc.name AS category_name
            FROM 
                blog_posts bp
            JOIN 
                users u ON bp.user_id = u.user_id
            LEFT JOIN 
                blog_categories bc ON bp.category_id = bc.category_id
            ORDER BY 
                bp.created_at DESC 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_equipments.css"> <!-- For .admin-table styles (can be shared) -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_blog.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="add_blog_post.php" class="admin-button add-new-button">Add New Post</a>
                </div>

                <?php if ($feedback_message): ?>
                    <div class="message success-message global-message">
                        <p><?php echo htmlspecialchars($feedback_message); ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($posts) && empty($errors)): ?>
                    <p>No blog posts found. <a href="add_blog_post.php" class="admin-button add-new-button">Create the first one!</a></p>
                <?php else: ?>
                    <div class="table-container-full">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr class="status-<?php echo htmlspecialchars($post['status']); ?>">
                                        <td><strong><?php echo htmlspecialchars($post['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                        <td><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><span class="status-badge"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $post['status']))); ?></span></td>
                                        <td><?php echo date("M j, Y", strtotime($post['created_at'])); ?></td>
                                        <td class="actions-cell">
                                            <a href="edit_blog_post.php?id=<?php echo $post['post_id']; ?>" class="admin-button edit-button">Edit</a>
                                            <form action="delete_blog_post.php" method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                <button type="submit" class="admin-button delete-button">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">&laquo; Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next &raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>
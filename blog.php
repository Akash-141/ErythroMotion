<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "ErythroMotion Blog - Health & Fitness Articles";

include __DIR__ . '/includes/db_connect.php';

$posts = [];
$categories = [];
$recent_posts = [];
$page_errors = [];
$current_user_id = $_SESSION['user_id'] ?? null; // Check for logged-in user

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- Pagination Logic ---
    $records_per_page = 5; 
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Get total number of published posts for pagination
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch paginated posts that are 'published'
    $sql = "SELECT bp.post_id, bp.title, bp.slug, bp.content, bp.featured_image_url, bp.created_at, u.username AS author_name, bc.name AS category_name, bc.slug AS category_slug FROM blog_posts bp JOIN users u ON bp.user_id = u.user_id LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id WHERE bp.status = 'published' ORDER BY bp.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    // Fetch categories for the sidebar
    $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll();

    // Fetch recent posts for the sidebar
    $recent_posts = $pdo->query("SELECT title, slug FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    $page_errors[] = "Database error: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/blog.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: var(--spacing-lg) 0; background-color: #f4f7f6; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <div class="blog-container">
            <header class="blog-header">
                <h1>ErythroMotion Blog</h1>
                <p>Your source for expert advice on fitness, health, nutrition, and wellness.</p>
            </header>

            <div class="blog-layout">
                <!-- Main Blog Posts Content -->
                <div class="main-content">
                    <?php if (!empty($page_errors)): ?>
                        <div class="message error-message global-message">
                            <?php foreach ($page_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                        </div>
                    <?php elseif (empty($posts)): ?>
                        <div class="no-posts-message">
                            <h2>No Posts Found</h2>
                            <p>There are currently no articles to display. Please check back soon!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <article class="post-card">
                                <?php if (!empty($post['featured_image_url'])): ?>
                                    <a href="single_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                        <img src="<?php echo $root_path_prefix . htmlspecialchars($post['featured_image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-card-image">
                                    </a>
                                <?php endif; ?>
                                <div class="post-card-content">
                                    <div class="post-meta">
                                        <span class="post-category"><a href="blog.php?category=<?php echo htmlspecialchars($post['category_slug'] ?? ''); ?>"><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></a></span>
                                        <span class="post-date"><?php echo date("F j, Y", strtotime($post['created_at'])); ?></span>
                                    </div>
                                    <h2 class="post-title">
                                        <a href="single_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                                    </h2>
                                    <p class="post-excerpt">
                                        <?php 
                                            $excerpt = strip_tags($post['content']);
                                            echo htmlspecialchars(substr($excerpt, 0, 150)) . (strlen($excerpt) > 150 ? '...' : ''); 
                                        ?>
                                    </p>
                                    <div class="post-author">
                                        <small>By <?php echo htmlspecialchars($post['author_name']); ?></small>
                                    </div>
                                    <a href="single_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="read-more-link">Read More &raquo;</a>
                                </div>
                            </article>
                        <?php endforeach; ?>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="pagination-nav">
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

                <!-- Sidebar -->
                <aside class="sidebar">
                    <?php if ($current_user_id): // Show this widget only to logged-in users ?>
                    <div class="widget">
                        <h3 class="widget-title">Contribute</h3>
                        <p>Have something to share? Write your own post and contribute to the community!</p>
                        <a href="<?php echo $root_path_prefix; ?>create_post.php" class="button-primary create-post-sidebar-btn">Create New Post</a>
                    </div>
                    <?php endif; ?>

                    <div class="widget">
                        <h3 class="widget-title">Categories</h3>
                        <ul class="widget-list">
                            <?php foreach ($categories as $category): ?>
                                <li><a href="blog.php?category=<?php echo htmlspecialchars($category['slug']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="widget">
                        <h3 class="widget-title">Recent Posts</h3>
                        <ul class="widget-list">
                             <?php foreach ($recent_posts as $post): ?>
                                <li><a href="single_post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>



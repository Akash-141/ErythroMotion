<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Blog Post - ErythroMotion"; 
$current_user_id = $_SESSION['user_id'] ?? null;

include __DIR__ . '/includes/db_connect.php';

$post = null;
$comments = [];
$categories = [];
$recent_posts = [];
$page_errors = [];

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    $page_errors[] = "No post specified.";
} else {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Fetch the specific post
        $sql = "SELECT bp.*, u.username AS author_name, bc.name AS category_name, bc.slug AS category_slug FROM blog_posts bp JOIN users u ON bp.user_id = u.user_id LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id WHERE bp.slug = :slug AND bp.status = 'published'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $post = $stmt->fetch();

        if (!$post) {
            $page_errors[] = "The post you are looking for could not be found.";
            $page_title = "Post Not Found - ErythroMotion";
        } else {
            $page_title = htmlspecialchars($post['title']);

            // Handle Comment Submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
                if (!$current_user_id) {
                    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
                    exit();
                }
                $comment_text = trim($_POST['comment_text'] ?? '');
                $parent_comment_id = !empty($_POST['parent_comment_id']) ? filter_var($_POST['parent_comment_id'], FILTER_VALIDATE_INT) : null;

                if (!empty($comment_text)) {
                    $sql_insert = "INSERT INTO blog_comments (post_id, user_id, parent_comment_id, comment_text) VALUES (:post_id, :user_id, :parent_comment_id, :comment_text)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([
                        ':post_id' => $post['post_id'],
                        ':user_id' => $current_user_id,
                        ':parent_comment_id' => $parent_comment_id,
                        ':comment_text' => $comment_text
                    ]);
                    // Redirect to prevent form resubmission
                    header("Location: single_post.php?slug=" . $slug . "#comments-section");
                    exit();
                } else {
                    $page_errors[] = "Comment cannot be empty.";
                }
            }

            // Fetch comments for the post
            $stmt_comments = $pdo->prepare("SELECT c.*, u.username FROM blog_comments c JOIN users u ON c.user_id = u.user_id WHERE c.post_id = :post_id AND c.is_approved = 1 ORDER BY c.created_at ASC");
            $stmt_comments->execute([':post_id' => $post['post_id']]);
            $all_comments = $stmt_comments->fetchAll();

            // Arrange comments into a nested structure
            $comments_by_id = [];
            foreach ($all_comments as $comment) {
                $comments_by_id[$comment['comment_id']] = $comment;
            }
            foreach ($all_comments as $key => $comment) {
                if ($comment['parent_comment_id'] !== null) {
                    $comments_by_id[$comment['parent_comment_id']]['replies'][] =& $comments_by_id[$key];
                }
            }
            $comments = array_filter($all_comments, function($comment) {
                return $comment['parent_comment_id'] === null;
            });
        }
        
        $categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll();
        $recent_posts = $pdo->query("SELECT title, slug FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5")->fetchAll();

    } catch (PDOException $e) {
        $page_errors[] = "Database error: " . $e->getMessage();
    }
}

// Function to display comments and their replies recursively
function display_comments($comments, $level = 0) {
    foreach ($comments as $comment) {
        echo '<div class="comment-item" id="comment-' . $comment['comment_id'] . '">';
        echo '<div class="comment-header">';
        echo '<span class="comment-author">' . htmlspecialchars($comment['username']) . '</span>';
        echo '<span class="comment-date">' . date("M j, Y", strtotime($comment['created_at'])) . '</span>';
        echo '</div>';
        echo '<div class="comment-body">' . nl2br(htmlspecialchars($comment['comment_text'])) . '</div>';
        echo '<div class="comment-footer">';
        echo '<button class="reply-button" data-comment-id="' . $comment['comment_id'] . '">Reply</button>';
        echo '</div>';
        
        // Hidden reply form for this comment
        echo '<div class="reply-form-container" id="reply-form-for-' . $comment['comment_id'] . '" style="display:none;">';
        if (isset($_SESSION['user_id'])) {
            echo '<form action="single_post.php?slug=' . htmlspecialchars($_GET['slug']) . '#comment-' . $comment['comment_id'] . '" method="POST">';
            echo '<input type="hidden" name="parent_comment_id" value="' . $comment['comment_id'] . '">';
            echo '<textarea name="comment_text" rows="3" placeholder="Write a reply..." required></textarea>';
            echo '<button type="submit" name="submit_comment" class="button-primary">Submit Reply</button>';
            echo '</form>';
        }
        echo '</div>';

        if (!empty($comment['replies'])) {
            echo '<div class="comment-replies">';
            display_comments($comment['replies'], $level + 1);
            echo '</div>';
        }
        echo '</div>';
    }
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/single_post.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <div class="blog-container">
            <div class="blog-layout">
                <div class="main-content">
                    <?php if (!empty($page_errors)): ?>
                        <div class="message error-message global-message">
                            <?php foreach ($page_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                             <a href="blog.php" class="button-primary">Back to Blog</a>
                        </div>
                    <?php elseif ($post): ?>
                        <article class="single-post">
                            <header class="post-header-single">
                                <?php if (!empty($post['category_name'])): ?>
                                    <div class="post-meta single-post-meta">
                                        <span class="post-category"><a href="blog.php?category=<?php echo htmlspecialchars($post['category_slug']); ?>"><?php echo htmlspecialchars($post['category_name']); ?></a></span>
                                    </div>
                                <?php endif; ?>
                                <h1 class="post-title-single"><?php echo htmlspecialchars($post['title']); ?></h1>
                                <div class="post-meta single-post-meta">
                                    <span class="post-author">By <?php echo htmlspecialchars($post['author_name']); ?></span>
                                    <span class="post-date"><?php echo date("F j, Y", strtotime($post['created_at'])); ?></span>
                                </div>
                            </header>

                            <?php if (!empty($post['featured_image_url'])): ?>
                                <img src="<?php echo $root_path_prefix . htmlspecialchars($post['featured_image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="featured-image-single">
                            <?php endif; ?>

                            <div class="article-body">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                        </article>

                        <section class="comments-section" id="comments-section">
                            <h2 class="section-title"><?php echo count($all_comments); ?> Comment(s)</h2>
                            
                            <div class="comment-form-container">
                                <h3>Leave a Comment</h3>
                                <?php if ($current_user_id): ?>
                                    <form action="single_post.php?slug=<?php echo htmlspecialchars($slug); ?>#comments-section" method="POST" class="comment-form">
                                        <textarea name="comment_text" rows="5" placeholder="Write your comment here..." required></textarea>
                                        <button type="submit" name="submit_comment" class="button-primary">Post Comment</button>
                                    </form>
                                <?php else: ?>
                                    <p>Please <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">login</a> to post a comment.</p>
                                <?php endif; ?>
                            </div>

                            <div class="comments-list">
                                <?php if (!empty($comments)): ?>
                                    <?php display_comments($comments); ?>
                                <?php else: ?>
                                    <p>Be the first to comment on this post!</p>
                                <?php endif; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <aside class="sidebar">
                   <!-- Sidebar content remains the same -->
                </aside>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const replyButtons = document.querySelectorAll('.reply-button');
            replyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const commentId = this.dataset.commentId;
                    const replyFormContainer = document.getElementById('reply-form-for-' .concat(commentId));
                    if (replyFormContainer) {
                        replyFormContainer.style.display = replyFormContainer.style.display === 'none' ? 'block' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>

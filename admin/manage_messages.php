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
$page_title = "Manage Contact Messages - Admin";
$page_errors = [];
$messages = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle actions (Mark as Read, Delete)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

        if ($message_id) {
            if ($_POST['action'] === 'mark_read') {
                $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE message_id = :message_id");
                $stmt->execute([':message_id' => $message_id]);
                $_SESSION['admin_message'] = "Message marked as read.";
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE message_id = :message_id");
                $stmt->execute([':message_id' => $message_id]);
                $_SESSION['admin_message'] = "Message deleted successfully.";
            }
        }
        header("Location: manage_messages.php"); // Redirect to avoid re-posting
        exit();
    }


    // --- Pagination Logic ---
    $records_per_page = 15;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $records_per_page;

    $stmt_total = $pdo->query("SELECT COUNT(*) FROM contact_messages");
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
    
    // Fetch paginated messages
    $stmt = $pdo->prepare("SELECT * FROM contact_messages ORDER BY received_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    $page_errors[] = "Database error: " . $e->getMessage();
}

// Get and clear session feedback message
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_messages.css"> <!-- New CSS file -->
    <style> body { display: flex; flex-direction: column; min-height: 100vh; padding-top: var(--navbar-height); } </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                </div>

                <?php if ($feedback_message): ?>
                    <div class="message success-message global-message"><p><?php echo htmlspecialchars($feedback_message); ?></p></div>
                <?php endif; ?>
                <?php if (!empty($page_errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($page_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($messages) && empty($page_errors)): ?>
                    <p>No contact messages found.</p>
                <?php else: ?>
                    <div class="messages-list">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message-card <?php echo $msg['is_read'] ? 'is-read' : 'is-unread'; ?>">
                                <div class="message-header">
                                    <div class="sender-info">
                                        <strong>From:</strong> <?php echo htmlspecialchars($msg['name']); ?> 
                                        (<a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a>)
                                    </div>
                                    <div class="message-meta">
                                        <span class="status-indicator"><?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?></span>
                                        <span class="timestamp"><?php echo date("M j, Y, g:i a", strtotime($msg['received_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?>
                                </div>
                                <div class="message-body">
                                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                </div>
                                <div class="message-actions">
                                    <?php if (!$msg['is_read']): ?>
                                    <form action="manage_messages.php" method="POST" class="action-form">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                        <input type="hidden" name="action" value="mark_read">
                                        <button type="submit" class="admin-button mark-read-btn">Mark as Read</button>
                                    </form>
                                    <?php endif; ?>
                                    <form action="manage_messages.php" method="POST" class="action-form" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="admin-button delete-button">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

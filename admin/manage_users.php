<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../"; // Path to access root-level files like CSS, JS, main includes

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "login.php"); // Redirect to login if not admin
    exit();
}

require __DIR__ . '/../includes/db_connect.php'; // Database connection [cite: db_connect.php]
$page_title = "Manage Users - Admin";
$page_errors = [];
$users = [];

// --- Pagination Settings ---
$records_per_page = 10; // Number of users to display per page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $records_per_page;
$total_records = 0;
$total_pages = 0;
// --- End Pagination Settings ---

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Get total number of users for pagination
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM users"); // [cite: DDL.sql]
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    if ($current_page > $total_pages && $total_pages > 0) { // If current page is out of bounds
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $records_per_page; // Recalculate offset
    } elseif ($current_page < 1 && $total_pages > 0) {
        $current_page = 1;
        $offset = 0;
    }

    // Fetch users for the current page
    $sql = "SELECT user_id, full_name, username, email, role, status, created_at FROM users ORDER BY user_id ASC LIMIT :limit OFFSET :offset"; // [cite: DDL.sql]
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_layout.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_equipments.css"> <!-- For .admin-table styles (can be shared) -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_users.css"> <!-- Specific styles for this page -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height); /* [cite: variables.css] */
        }
        /* Inline styles for status and pagination were moved to admin_manage_users.css */
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; // [cite: navbar.php] ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; // [cite: admin_sidebar.php] ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="add_user.php" class="admin-button add-new-button">Add New User</a>
                </div>

                <?php if (!empty($page_errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($page_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['message'])): ?>
                    <div class="message <?php echo (isset($_GET['type']) && $_GET['type'] === 'error') ? 'error-message' : 'success-message'; ?> global-message">
                        <p><?php echo htmlspecialchars(urldecode($_GET['message'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (empty($users) && empty($page_errors) && $total_records === 0): ?>
                    <p>No users found. <a href="add_user.php">Add the first one!</a></p>
                <?php elseif (!empty($users)): ?>
                    <table class="admin-table"> <!-- [cite: admin_manage_equipments.css] -->
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td>
                                        <span class="status-<?php echo htmlspecialchars(strtolower($user['status'])); ?>">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $user['status']))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(date("M j, Y, g:i a", strtotime($user['created_at']))); ?></td>
                                    <td class="actions-cell"> <!-- [cite: admin_manage_equipments.css] -->
                                        <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="admin-button edit-button">Edit</a> <!-- [cite: admin_manage_equipments.css] -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination Links -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo; Previous</span>
                                </a>
                            </li>
                            <?php 
                            $range = 2; 
                            $start_page = max(1, $current_page - $range);
                            $end_page = min($total_pages, $current_page + $range);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor;
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'">'.$total_pages.'</a></li>';
                            }
                            ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">Next &raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <!-- End Pagination Links -->

                <?php elseif ($total_records > 0 && empty($users) && empty($page_errors)): ?>
                     <p>No users found for this page. <a href="?page=1">Go to the first page.</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>
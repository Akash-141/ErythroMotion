<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php'; // [cite: db_connect.php]
$page_title = "Manage Equipment - Admin";

$page_errors = [];
$equipments = [];

// --- Pagination Settings ---
$records_per_page = 10; // Number of equipment items to display per page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $records_per_page;
$total_records = 0;
$total_pages = 0;
// --- End Pagination Settings ---

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Get total number of equipment items for pagination
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM equipments"); // [cite: DDL.sql]
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
    
    if ($current_page > $total_pages && $total_pages > 0) { // If current page is out of bounds
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $records_per_page; // Recalculate offset
    } elseif ($current_page < 1 && $total_pages > 0) {
        $current_page = 1;
        $offset = 0;
    }

    // Fetch equipment for the current page
    $sql = "SELECT equipment_id, name, category, price, stock_quantity, is_featured 
            FROM equipments 
            ORDER BY name ASC, equipment_id ASC 
            LIMIT :limit OFFSET :offset"; // [cite: DDL.sql]
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $equipments = $stmt->fetchAll();

} catch (PDOException $e) {
    $page_errors[] = "Error fetching equipment: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_equipments.css"> <!-- Updated CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height); /* [cite: variables.css] */
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; // [cite: navbar.php] ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; // [cite: admin_sidebar.php] ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container"> <!-- Ensure this class is styled or uses .admin-container if needed -->
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="add_equipment.php" class="admin-button add-new-button">Add New Equipment</a>
                </div>

                <?php if (!empty($page_errors)): ?>
                    <div class="message error-message global-message"> <!-- Added global-message for consistency -->
                        <?php foreach ($page_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php // Consistent message display structure
                if (isset($_GET['status'])):
                    $messageText = '';
                    $messageTypeClass = 'success-message'; // Default to success

                    if ($_GET['status'] === 'deleted') $messageText = "Equipment deleted successfully.";
                    elseif ($_GET['status'] === 'added') $messageText = "Equipment added successfully.";
                    elseif ($_GET['status'] === 'updated') $messageText = "Equipment updated successfully.";
                    elseif ($_GET['status'] === 'error' && isset($_GET['msg'])) {
                        $messageText = htmlspecialchars(urldecode($_GET['msg']));
                        $messageTypeClass = 'error-message';
                    } elseif (isset($_GET['msg'])) { // For other general messages
                        $messageText = htmlspecialchars(urldecode($_GET['msg']));
                        // Determine type if a 'type' GET param is also passed, otherwise default
                        $messageTypeClass = (isset($_GET['type']) && $_GET['type'] === 'error') ? 'error-message' : 'success-message';
                    }
                    
                    if (!empty($messageText)): ?>
                        <div class="message <?php echo $messageTypeClass; ?> global-message">
                            <p><?php echo $messageText; ?></p>
                        </div>
                    <?php endif; 
                endif; ?>


                <?php if (empty($equipments) && empty($page_errors) && $total_records === 0): ?>
                    <p>No equipment found. <a href="add_equipment.php">Add the first one!</a></p>
                <?php elseif (!empty($equipments)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipments as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['equipment_id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($item['stock_quantity']); ?></td>
                                    <td><?php echo $item['is_featured'] ? 'Yes' : 'No'; ?></td>
                                    <td class="actions-cell">
                                        <a href="edit_equipment.php?id=<?php echo $item['equipment_id']; ?>" class="admin-button edit-button">Edit</a>
                                        <form method="POST" action="delete_equipment.php" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                            <button type="submit" class="admin-button delete-button">Delete</button>
                                        </form>
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

                <?php elseif ($total_records > 0 && empty($equipments) && empty($page_errors)): ?>
                     <p>No equipment found for this page. <a href="?page=1">Go to the first page.</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>

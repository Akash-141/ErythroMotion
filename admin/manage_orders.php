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
$page_title = "Manage Orders - Admin";

$page_errors = [];
$orders = [];

// --- Pagination Settings ---
$records_per_page = 10; // Number of orders to display per page
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

    // Get total number of orders for pagination
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM orders"); // [cite: DDL.sql]
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    if ($current_page > $total_pages && $total_pages > 0) { // If current page is out of bounds
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $records_per_page; // Recalculate offset
    } elseif ($current_page < 1 && $total_pages > 0) {
        $current_page = 1;
        $offset = 0;
    }
    
    // Fetch orders for the current page
    $sql = "SELECT o.order_id, o.order_date, o.total_amount, o.order_status, o.payment_method, u.username AS customer_username, o.shipping_name 
            FROM orders o
            JOIN users u ON o.user_id = u.user_id
            ORDER BY o.order_date DESC
            LIMIT :limit OFFSET :offset"; // [cite: DDL.sql]
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();

} catch (PDOException $e) {
    $page_errors[] = "Error fetching orders: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_orders.css"> <!-- Links to updated CSS -->
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
            <div class="admin-content-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <!-- No "Add New Order" button typically on this page -->
                </div>

                <?php if (!empty($page_errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($page_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php // Consistent message display
                if (isset($_GET['status']) && isset($_GET['msg'])): ?>
                    <div class="message global-message <?php echo $_GET['status'] === 'success' ? 'success-message' : 'error-message'; ?>">
                        <p><?php echo htmlspecialchars(urldecode($_GET['msg'])); ?></p>
                    </div>
                <?php endif; ?>


                <?php if (empty($orders) && empty($page_errors) && $total_records === 0): ?>
                    <p>No orders found.</p>
                <?php elseif (!empty($orders)): ?>
                    <table class="admin-table orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Order Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['shipping_name']); ?> (<?php echo htmlspecialchars($order['customer_username']); ?>)</td>
                                    <td><?php echo htmlspecialchars(date("M j, Y, g:i a", strtotime($order['order_date']))); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                    <td><span class="order-status status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $order['order_status']))); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($order['payment_method'] ?? 'N/A'); ?></td>
                                    <td class="actions-cell">
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="admin-button view-details-button">View Details</a>
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

                <?php elseif ($total_records > 0 && empty($orders) && empty($page_errors)): ?>
                     <p>No orders found for this page. <a href="?page=1">Go to the first page.</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>

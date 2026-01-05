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
$page_title = "Admin Dashboard - ErythroMotion";

// Initialize counts
$total_users = 0;
$total_exercises = 0;
$total_equipments = 0;
$total_orders = 0;
$pending_orders = 0;
$dashboard_errors = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get total users
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM users"); // [cite: DDL.sql]
    $total_users = (int)$stmt_users->fetchColumn();

    // Get total exercises
    $stmt_exercises = $pdo->query("SELECT COUNT(*) FROM exercises"); // [cite: DDL.sql]
    $total_exercises = (int)$stmt_exercises->fetchColumn();

    // Get total equipment items
    $stmt_equipments = $pdo->query("SELECT COUNT(*) FROM equipments"); // [cite: DDL.sql]
    $total_equipments = (int)$stmt_equipments->fetchColumn();

    // Get total orders
    $stmt_total_orders = $pdo->query("SELECT COUNT(*) FROM orders"); // [cite: DDL.sql]
    $total_orders = (int)$stmt_total_orders->fetchColumn();

    // Get pending orders
    $stmt_pending_orders = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_status = :status"); // [cite: DDL.sql]
    $pending_status = 'Pending';
    $stmt_pending_orders->bindParam(':status', $pending_status);
    $stmt_pending_orders->execute();
    $pending_orders = (int)$stmt_pending_orders->fetchColumn();


} catch (PDOException $e) {
    $dashboard_errors[] = "Failed to fetch dashboard statistics: " . $e->getMessage();
    // Keep default counts (0) or set to 'Error' if preferred
    $total_users = 'Error';
    $total_exercises = 'Error';
    $total_equipments = 'Error';
    $total_orders = 'Error';
    $pending_orders = 'Error';
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_layout.css"> <!-- [cite: admin_layout_css_updated_summary_card] -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height); /* [cite: variables.css] */
        }
        /* Styling for clickable summary cards will be in admin_layout.css */
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; // [cite: navbar.php] ?>

    <div class="admin-area-layout">
        
        <?php include __DIR__ . '/includes/admin_sidebar.php'; // [cite: admin_sidebar.php] ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container">
                <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                
                <?php if (!empty($dashboard_errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($dashboard_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p>This is your central hub for managing ErythroMotion. Select an option from the sidebar to get started.</p>
                
                <div class="dashboard-summary">
                    <a href="manage_users.php" class="summary-card-link">
                        <div class="summary-card">
                            <h4>Total Users</h4>
                            <p class="summary-value"><?php echo htmlspecialchars($total_users); ?></p>
                        </div>
                    </a>
                    <a href="manage_exercises.php" class="summary-card-link">
                        <div class="summary-card">
                            <h4>Listed Exercises</h4>
                            <p class="summary-value"><?php echo htmlspecialchars($total_exercises); ?></p>
                        </div>
                    </a>
                    <a href="manage_equipments.php" class="summary-card-link">
                        <div class="summary-card">
                            <h4>Equipment Items</h4>
                            <p class="summary-value"><?php echo htmlspecialchars($total_equipments); ?></p>
                        </div>
                    </a>
                    <a href="manage_orders.php" class="summary-card-link">
                        <div class="summary-card">
                            <h4>Total Orders</h4>
                            <p class="summary-value"><?php echo htmlspecialchars($total_orders); ?></p>
                        </div>
                    </a>
                    <a href="manage_orders.php?status_filter=Pending" class="summary-card-link"> <!-- Example: Link to filtered page -->
                        <div class="summary-card">
                            <h4>Pending Orders</h4>
                            <p class="summary-value"><?php echo htmlspecialchars($pending_orders); ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // [cite: footer.php] ?>
</body>

</html>

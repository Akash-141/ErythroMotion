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
$page_title = "Manage Donors - Admin";
$errors = [];
$donors = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle status update action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_donor_status'])) {
        $user_id_to_update = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $new_status = $_POST['donor_status'] ?? '';
        $valid_statuses = ['unverified', 'verified', 'unavailable'];

        if ($user_id_to_update && in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE users SET donor_status = :donor_status WHERE user_id = :user_id AND is_donor = 1");
            $stmt->execute([':donor_status' => $new_status, ':user_id' => $user_id_to_update]);
            
            $_SESSION['admin_message'] = "Donor status updated successfully.";
            header("Location: manage_donors.php");
            exit();
        } else {
            $errors[] = "Invalid data provided for status update.";
        }
    }

    // Pagination Logic
    $records_per_page = 15;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $records_per_page;

    $stmt_total = $pdo->query("SELECT COUNT(*) FROM users WHERE is_donor = 1");
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch all users who have registered as donors
    $stmt_donors = $pdo->prepare("SELECT user_id, full_name, username, email, phone_number, blood_group, donor_status FROM users WHERE is_donor = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt_donors->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt_donors->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt_donors->execute();
    $donors = $stmt_donors->fetchAll();

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_donors.css"> <!-- New CSS file -->
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
                <?php if (!empty($errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($donors) && empty($errors)): ?>
                    <p>No users have registered as donors yet.</p>
                <?php else: ?>
                    <div class="table-container-full">
                        <table class="admin-table donors-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Blood Group</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donors as $donor): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($donor['full_name']); ?></strong><br>
                                            <small>(@<?php echo htmlspecialchars($donor['username']); ?>)</small>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($donor['email']); ?>"><?php echo htmlspecialchars($donor['email']); ?></a><br>
                                            <a href="tel:<?php echo htmlspecialchars($donor['phone_number']); ?>"><?php echo htmlspecialchars($donor['phone_number']); ?></a>
                                        </td>
                                        <td class="blood-group-cell"><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                                        <td><span class="status-badge status-<?php echo htmlspecialchars($donor['donor_status']); ?>"><?php echo htmlspecialchars(ucfirst($donor['donor_status'])); ?></span></td>
                                        <td class="actions-cell">
                                            <form action="manage_donors.php" method="POST" class="status-update-form">
                                                <input type="hidden" name="user_id" value="<?php echo $donor['user_id']; ?>">
                                                <select name="donor_status">
                                                    <option value="unverified" <?php echo ($donor['donor_status'] === 'unverified') ? 'selected' : ''; ?>>Unverified</option>
                                                    <option value="verified" <?php echo ($donor['donor_status'] === 'verified') ? 'selected' : ''; ?>>Verified</option>
                                                    <option value="unavailable" <?php echo ($donor['donor_status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                                                </select>
                                                <button type="submit" name="update_donor_status" class="admin-button edit-button">Update</button>
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

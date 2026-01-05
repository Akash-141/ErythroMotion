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
$page_title = "Manage Blood Requests - Admin";
$errors = [];
$requests = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle status update action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request_status'])) {
        $request_id_to_update = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $new_status = $_POST['request_status'] ?? '';
        $valid_statuses = ['pending', 'viewed_by_admin', 'donor_contacted', 'fulfilled', 'closed', 'cancelled'];

        if ($request_id_to_update && in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE blood_requests SET request_status = :request_status WHERE request_id = :request_id");
            $stmt->execute([':request_status' => $new_status, ':request_id' => $request_id_to_update]);
            
            $_SESSION['admin_message'] = "Request status updated successfully.";
            header("Location: manage_blood_requests.php");
            exit();
        } else {
            $errors[] = "Invalid data provided for status update.";
        }
    }

    // Pagination Logic
    $records_per_page = 10;
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $records_per_page;

    $stmt_total = $pdo->query("SELECT COUNT(*) FROM blood_requests");
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch paginated requests with JOINs to get names
    $stmt_requests = $pdo->prepare("
        SELECT 
            br.*, 
            requester.full_name AS requester_name,
            donor.full_name AS donor_name
        FROM 
            blood_requests br
        JOIN 
            users AS requester ON br.requester_user_id = requester.user_id
        JOIN 
            users AS donor ON br.donor_user_id = donor.user_id
        ORDER BY 
            br.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt_requests->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt_requests->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt_requests->execute();
    $requests = $stmt_requests->fetchAll();

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_manage_requests.css"> <!-- New CSS file -->
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

                <?php if (empty($requests) && empty($errors)): ?>
                    <p>No blood requests have been made yet.</p>
                <?php else: ?>
                    <div class="table-container-full">
                        <table class="admin-table requests-table">
                            <thead>
                                <tr>
                                    <th>Patient & Requester</th>
                                    <th>Donor</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr class="status-<?php echo htmlspecialchars($request['request_status']); ?>">
                                        <td>
                                            <strong>Patient: <?php echo htmlspecialchars($request['patient_name']); ?></strong> (Age: <?php echo htmlspecialchars($request['patient_age']); ?>)<br>
                                            <small>Requested by: <?php echo htmlspecialchars($request['requester_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['donor_name']); ?></td>
                                        <td>
                                            <strong class="blood-group-cell"><?php echo htmlspecialchars($request['patient_blood_group']); ?></strong><br>
                                            <small>Needed by: <?php echo date("M j, Y", strtotime($request['required_date'])); ?></small>
                                        </td>
                                        <td><span class="status-badge"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $request['request_status']))); ?></span></td>
                                        <td class="actions-cell">
                                            <form action="manage_blood_requests.php" method="POST" class="status-update-form">
                                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                <select name="request_status">
                                                    <option value="pending" <?php echo ($request['request_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="viewed_by_admin" <?php echo ($request['request_status'] === 'viewed_by_admin') ? 'selected' : ''; ?>>Viewed</option>
                                                    <option value="donor_contacted" <?php echo ($request['request_status'] === 'donor_contacted') ? 'selected' : ''; ?>>Donor Contacted</option>
                                                    <option value="fulfilled" <?php echo ($request['request_status'] === 'fulfilled') ? 'selected' : ''; ?>>Fulfilled</option>
                                                    <option value="closed" <?php echo ($request['request_status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                                                     <option value="cancelled" <?php echo ($request['request_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                                <button type="submit" name="update_request_status" class="admin-button edit-button">Update</button>
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
                             <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo $current_page - 1; ?>">&laquo; Previous</a></li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next &raquo;</a></li>
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

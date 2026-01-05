<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Find a Blood Donor - ErythroMotion";

include __DIR__ . '/includes/db_connect.php';

$donors = [];
$page_errors = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // --- Pagination Logic ---
    $records_per_page = 12; 
    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) $current_page = 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Get total number of VERIFIED donors for pagination
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM users WHERE is_donor = 1 AND donor_status = 'verified'");
    $total_records = (int)$stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Fetch paginated donors who are verified
    $stmt_donors = $pdo->prepare("
        SELECT user_id, full_name, blood_group, location 
        FROM users 
        WHERE is_donor = 1 AND donor_status = 'verified' 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt_donors->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt_donors->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt_donors->execute();
    $donors = $stmt_donors->fetchAll();

} catch (PDOException $e) {
    $page_errors[] = "Database error: Could not retrieve donor list. " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/donor_list.css">
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
        <div class="donor-list-container">
            <header class="donor-list-header">
                <h1>Verified Blood Donors</h1>
                <p>Browse our community of registered donors. Click "Request Blood" to initiate contact for your needs.</p>
                <div class="search-bar-placeholder">
                    <p>Looking for a specific blood group or location? <a href="search_donor.php">Use our advanced search.</a></p>
                </div>
            </header>

            <?php if (!empty($page_errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($page_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php elseif (empty($donors)): ?>
                <div class="no-donors-message">
                    <h2>No Verified Donors Available</h2>
                    <p>There are currently no verified donors in the list. Please check back later or consider becoming a donor yourself!</p>
                    <a href="blood_donation.php" class="button-primary">Learn About Donation</a>
                </div>
            <?php else: ?>
                <div class="donor-grid">
                    <?php foreach ($donors as $donor): ?>
                        <div class="donor-card">
                            <div class="donor-blood-group">
                                <span><?php echo htmlspecialchars($donor['blood_group']); ?></span>
                            </div>
                            <div class="donor-details">
                                <h3 class="donor-name"><?php echo htmlspecialchars($donor['full_name']); ?></h3>
                                <p class="donor-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($donor['location'] ?? 'Not specified'); ?></p>
                            </div>
                            <div class="donor-actions">
                                <!-- UPDATED LINK -->
                                <a href="request_blood.php?donor_id=<?php echo $donor['user_id']; ?>" class="button-primary request-btn">Request Blood</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

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
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

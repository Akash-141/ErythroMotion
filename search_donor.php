<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Search for a Blood Donor - ErythroMotion";

include __DIR__ . '/includes/db_connect.php';

$donors = [];
$search_results = [];
$page_errors = [];
$search_performed = false;

// Define available blood groups for the dropdown
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Handle Search Request
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
        $search_performed = true;
        $blood_group_query = trim($_GET['blood_group'] ?? '');
        $location_query = trim($_GET['location'] ?? '');

        $sql = "SELECT user_id, full_name, blood_group, location FROM users WHERE is_donor = 1 AND donor_status = 'verified'";
        $params = [];

        if (!empty($blood_group_query)) {
            $sql .= " AND blood_group = :blood_group";
            $params[':blood_group'] = $blood_group_query;
        }
        if (!empty($location_query)) {
            // Using LIKE for partial location matching
            $sql .= " AND location LIKE :location";
            $params[':location'] = '%' . $location_query . '%';
        }

        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll();
    }

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/donor_list.css"> <!-- Reusing styles from donor_list -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/search_donor.css"> <!-- New CSS file for search specific styles -->
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
        <div class="search-donor-container">
            <header class="search-donor-header">
                <h1>Find a Donor</h1>
                <p>Use the form below to search for verified donors by blood group and location.</p>
            </header>

            <div class="search-form-container">
                <form action="search_donor.php" method="GET" class="search-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_group">Blood Group</label>
                            <select id="blood_group" name="blood_group">
                                <option value="">Any</option>
                                <?php foreach ($blood_groups as $group): ?>
                                    <option value="<?php echo $group; ?>" <?php echo (isset($_GET['blood_group']) && $_GET['blood_group'] === $group) ? 'selected' : ''; ?>>
                                        <?php echo $group; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location (City)</label>
                            <input type="text" id="location" name="location" placeholder="Enter a city name" value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                         <button type="submit" name="search" value="1" class="button-primary search-btn">
                            <i class="fas fa-search"></i> Search Donors
                        </button>
                    </div>
                </form>
            </div>

            <div class="search-results-section">
                <?php if ($search_performed): ?>
                    <h2 class="results-title">Search Results</h2>
                    <?php if (!empty($page_errors)): ?>
                         <div class="message error-message global-message">
                            <?php foreach ($page_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                        </div>
                    <?php elseif (empty($search_results)): ?>
                        <div class="no-donors-message">
                            <h3>No Donors Found</h3>
                            <p>No verified donors matched your search criteria. Please try a different search or view our full list.</p>
                            <a href="donor_list.php" class="button-secondary">View Full Donor List</a>
                        </div>
                    <?php else: ?>
                        <p class="results-count"><?php echo count($search_results); ?> donor(s) found matching your criteria.</p>
                        <div class="donor-grid">
                            <?php foreach ($search_results as $donor): ?>
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
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-search-message">
                        <p>Please use the form above to find donors.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

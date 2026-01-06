<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

include __DIR__ . '/includes/db_connect.php';
$page_title = "Blood Requests Received - ErythroMotion";
$current_user_id = $_SESSION['user_id'];
$requests_received = [];
$errors = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Handle status update from the donor
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request_status'])) {
        $request_id_to_update = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $new_status = $_POST['request_status'] ?? '';
        $valid_statuses = ['fulfilled', 'closed']; // Statuses a donor can set

        if ($request_id_to_update && in_array($new_status, $valid_statuses)) {
            // Security check: ensure the request being updated was actually made to the current user
            $stmt = $pdo->prepare("UPDATE blood_requests SET request_status = :request_status WHERE request_id = :request_id AND donor_user_id = :donor_user_id");
            $stmt->execute([':request_status' => $new_status, ':request_id' => $request_id_to_update, ':donor_user_id' => $current_user_id]);

            $_SESSION['feedback_message'] = "Request status updated successfully.";
            $_SESSION['feedback_type'] = "success";
        } else {
             $_SESSION['feedback_message'] = "Invalid data for status update.";
             $_SESSION['feedback_type'] = "error";
        }
        header("Location: request_received.php");
        exit();
    }


    // Fetch all requests where the current user is the donor
    $stmt_requests = $pdo->prepare("
        SELECT br.*, u.username AS requester_username, u.full_name AS requester_full_name 
        FROM blood_requests br
        JOIN users u ON br.requester_user_id = u.user_id
        WHERE br.donor_user_id = :donor_user_id
        ORDER BY br.created_at DESC
    ");
    $stmt_requests->execute([':donor_user_id' => $current_user_id]);
    $requests_received = $stmt_requests->fetchAll();

} catch (PDOException $e) {
    $errors[] = "Database error: Could not retrieve your received requests. " . $e->getMessage();
}

// Get and clear session feedback message
$feedback_message = $_SESSION['feedback_message'] ?? null;
$feedback_type = $_SESSION['feedback_type'] ?? null;
if (isset($_SESSION['feedback_message'])) unset($_SESSION['feedback_message']);
if (isset($_SESSION['feedback_type'])) unset($_SESSION['feedback_type']);

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/request_received.css"> <!-- New CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: var(--spacing-lg) var(--spacing-md); background-color: #f4f7f6; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <div class="requests-container">
            <header class="requests-header">
                <h1>Blood Requests Received</h1>
                <p>Here is a list of all blood donation requests you have received. Please respond responsibly.</p>
            </header>

            <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'error' ? 'error-message' : 'success-message'; ?> global-message">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="requests-list">
                <?php if (empty($requests_received) && empty($errors)): ?>
                    <div class="no-requests-message">
                        <h3>No Requests Yet</h3>
                        <p>You have not received any blood donation requests at this time. Thank you for being a registered donor!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests_received as $request): ?>
                        <div class="request-card status-<?php echo htmlspecialchars($request['request_status']); ?>">
                            <div class="request-card-header">
                                <span class="status-badge"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $request['request_status']))); ?></span>
                                <span class="request-date">Requested on: <?php echo date("M j, Y", strtotime($request['created_at'])); ?></span>
                            </div>
                            <div class="request-card-body">
                                <h3>Request from <?php echo htmlspecialchars($request['requester_full_name']); ?></h3>
                                <div class="request-details-grid">
                                    <div class="detail-item"><strong>Patient Name:</strong> <?php echo htmlspecialchars($request['patient_name']); ?></div>
                                    <div class="detail-item"><strong>Patient Age:</strong> <?php echo htmlspecialchars($request['patient_age']); ?></div>
                                    <div class="detail-item"><strong>Blood Group Needed:</strong> <span class="blood-group-tag"><?php echo htmlspecialchars($request['patient_blood_group']); ?></span></div>
                                    <div class="detail-item"><strong>Needed By:</strong> <?php echo date("F j, Y", strtotime($request['required_date'])); ?></div>
                                    <div class="detail-item detail-full-width"><strong>Hospital:</strong> <?php echo htmlspecialchars($request['hospital_name']); ?></div>
                                    <div class="detail-item detail-full-width"><strong>Reason:</strong> <?php echo htmlspecialchars($request['reason'] ?: 'Not specified'); ?></div>
                                    <div class="detail-item detail-full-width requester-contact">
                                        <strong>Requester Contact:</strong> 
                                        <span><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($request['contact_phone']); ?></span>
                                    </div>
                                </div>
                            </div>
                             <?php if ($request['request_status'] === 'pending' || $request['request_status'] === 'viewed_by_admin' || $request['request_status'] === 'donor_contacted'): ?>
                            <div class="request-card-actions">
                                <form action="request_received.php" method="POST">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <label for="request_status_<?php echo $request['request_id']; ?>">Update Status:</label>
                                    <select id="request_status_<?php echo $request['request_id']; ?>" name="request_status">
                                        <option value="fulfilled">Donation Fulfilled</option>
                                        <option value="closed">Close Request</option>
                                    </select>
                                    <button type="submit" name="update_request_status" class="button-primary">Update</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

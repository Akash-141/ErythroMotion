<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 

// User must be logged in to make a request
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

include __DIR__ . '/includes/db_connect.php';
$page_title = "Request Blood - ErythroMotion";
$current_user_id = $_SESSION['user_id'];
$errors = [];
$form_data = $_POST;
$donor = null;

$donor_id = filter_input(INPUT_GET, 'donor_id', FILTER_VALIDATE_INT);

if (!$donor_id) {
    header("Location: donor_list.php"); // Redirect if no donor is specified
    exit();
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Fetch donor details to display on the page
    $stmt_donor = $pdo->prepare("SELECT user_id, full_name, blood_group, location FROM users WHERE user_id = :user_id AND is_donor = 1 AND donor_status = 'verified'");
    $stmt_donor->execute([':user_id' => $donor_id]);
    $donor = $stmt_donor->fetch();

    if (!$donor) {
        $errors[] = "The requested donor could not be found or is not currently available.";
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request']) && $donor) {
        $patient_name = trim($form_data['patient_name'] ?? '');
        $patient_age = filter_var($form_data['patient_age'] ?? '', FILTER_VALIDATE_INT);
        $reason = trim($form_data['reason'] ?? '');
        $contact_phone = trim($form_data['contact_phone'] ?? '');
        $hospital_name = trim($form_data['hospital_name'] ?? '');
        $required_date_str = trim($form_data['required_date'] ?? '');

        // Validation
        if (empty($patient_name)) $errors[] = "Patient's name is required.";
        if ($patient_age === false || $patient_age <= 0) $errors[] = "A valid patient age is required.";
        if (empty($contact_phone)) $errors[] = "A contact phone number is required.";
        if (empty($hospital_name)) $errors[] = "Hospital name and location are required.";
        if (empty($required_date_str)) {
            $errors[] = "Please specify when the blood is needed.";
        } else {
            try {
                $required_date = new DateTime($required_date_str);
                if ($required_date < new DateTime('today')) {
                    $errors[] = "The required date cannot be in the past.";
                }
            } catch (Exception $e) {
                $errors[] = "Invalid date format for required date.";
            }
        }

        if (empty($errors)) {
            $sql = "INSERT INTO blood_requests (requester_user_id, donor_user_id, patient_name, patient_age, patient_blood_group, reason, contact_phone, hospital_name, required_date) 
                    VALUES (:requester_user_id, :donor_user_id, :patient_name, :patient_age, :patient_blood_group, :reason, :contact_phone, :hospital_name, :required_date)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':requester_user_id' => $current_user_id,
                ':donor_user_id' => $donor['user_id'],
                ':patient_name' => $patient_name,
                ':patient_age' => $patient_age,
                ':patient_blood_group' => $donor['blood_group'], // The blood group being requested
                ':reason' => $reason,
                ':contact_phone' => $contact_phone,
                ':hospital_name' => $hospital_name,
                ':required_date' => $required_date->format('Y-m-d')
            ]);
            
            $_SESSION['feedback_message'] = "Your request for blood has been submitted successfully. Our team will review it and get in touch with you shortly.";
            $_SESSION['feedback_type'] = "success";
            header("Location: profile.php"); // Redirect to profile page
            exit();
        }
    }

} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/request_blood.css"> <!-- New CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: var(--spacing-lg) var(--spacing-md); background-color: #f4f7f6; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <main>
        <div class="request-blood-container">
            <header class="request-blood-header">
                <h1>Blood Donation Request</h1>
            </header>

            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($donor): ?>
            <div class="request-layout">
                <div class="donor-info-summary">
                    <h3>Requesting To Donor:</h3>
                    <p class="donor-name-display"><?php echo htmlspecialchars($donor['full_name']); ?></p>
                    <div class="donor-details-display">
                        <span class="blood-group-display"><?php echo htmlspecialchars($donor['blood_group']); ?></span>
                        <span class="location-display"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($donor['location']); ?></span>
                    </div>
                    <div class="process-note">
                        <p><strong>Note:</strong> For privacy and safety, all requests are moderated. Your contact information will only be shared with the donor after administrative review. You will be contacted by our team.</p>
                    </div>
                </div>

                <div class="request-form-wrapper">
                    <h3>Patient & Request Details</h3>
                    <form action="request_blood.php?donor_id=<?php echo $donor_id; ?>" method="POST" class="request-form">
                        <div class="form-group">
                            <label for="patient_name">Patient's Full Name</label>
                            <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($form_data['patient_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="patient_age">Patient's Age</label>
                            <input type="number" id="patient_age" name="patient_age" value="<?php echo htmlspecialchars($form_data['patient_age'] ?? ''); ?>" required>
                        </div>
                         <div class="form-group">
                            <label for="required_date">When is Blood Needed By?</label>
                            <input type="date" id="required_date" name="required_date" value="<?php echo htmlspecialchars($form_data['required_date'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hospital_name">Hospital Name & City</label>
                            <input type="text" id="hospital_name" name="hospital_name" value="<?php echo htmlspecialchars($form_data['hospital_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="contact_phone">Your Contact Phone Number</label>
                            <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($form_data['contact_phone'] ?? ''); ?>" required>
                        </div>
                         <div class="form-group">
                            <label for="reason">Reason for Request (e.g., Surgery, Accident)</label>
                            <textarea id="reason" name="reason" rows="3"><?php echo htmlspecialchars($form_data['reason'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="submit_request" class="button-primary submit-request-btn">Submit Request</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="message error-message global-message">
                    <p>The donor you are trying to request from is not available or does not exist.</p>
                    <a href="donor_list.php" class="button-secondary">Return to Donor List</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
